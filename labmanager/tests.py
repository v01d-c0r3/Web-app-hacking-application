"""Tests for the lab manager, run with `python -m unittest tests` in labmanager/.

Covered: one instance per student, each on its own network that the lab proxy
joins; the cap on running instances; extending and expiry; nothing left
behind when a start fails or after a restart; when Docker fails part-way
through removing an instance, it stays tracked until a retry succeeds, and
anything untracked is swept up; and the HTTP API refusing anyone without the
token. Docker is replaced by a fake that records calls.
"""

import json
import threading
import unittest
import urllib.error
import urllib.request
from http.server import ThreadingHTTPServer

from docker_api import DockerError
from manager import LabError, Manager, make_handler

CATALOGUE = {'fellside': {'image': 'fellside:latest', 'memory_mb': 192, 'cpus': 0.5}}


class FakeDocker:
    def __init__(self, fail_run=False):
        self.containers = set()
        self.networks = {}  # name -> set of connected containers
        self.fail_run = fail_run
        # Containers Docker fails to remove, until a test clears this.
        self.stuck = set()

    def create_network(self, name, labels):
        self.networks[name] = set()

    def remove_network(self, name):
        self.networks.pop(name, None)

    def connect(self, network, container):
        self.networks[network].add(container)

    def disconnect(self, network, container):
        self.networks.get(network, set()).discard(container)

    def run(self, name, image, network, labels, memory_mb, cpus):
        if self.fail_run:
            raise DockerError(500, 'no such image')
        self.containers.add(name)
        self.networks[network].add(name)

    def remove_container(self, name):
        if name in self.stuck:
            raise DockerError(500, 'removal failed')
        self.containers.discard(name)
        for members in self.networks.values():
            members.discard(name)

    def containers_with_label(self, label):
        return sorted(self.containers)

    def networks_with_label(self, label):
        return sorted(self.networks)


class Clock:
    def __init__(self):
        self.now = 1_000_000.0

    def __call__(self):
        return self.now


def make_manager(docker=None, max_instances=10):
    ids = iter(f'{n:08x}' for n in range(1, 1000))
    clock = Clock()
    manager = Manager(docker or FakeDocker(), CATALOGUE, 'pwnshop.uk',
                      'pwnshop-labproxy', lifetime_seconds=3600,
                      max_instances=max_instances, clock=clock,
                      make_id=lambda: next(ids))
    return manager, clock


class ManagerTests(unittest.TestCase):
    def test_start_gives_the_student_their_own_address(self):
        manager, _ = make_manager()

        instance = manager.start('7', 'fellside')

        self.assertEqual(instance.as_json('pwnshop.uk')['url'],
                         'https://fellside-00000001.pwnshop.uk/')

    def test_each_instance_has_its_own_network_shared_only_with_the_proxy(self):
        docker = FakeDocker()
        manager, _ = make_manager(docker)

        first = manager.start('1', 'fellside')
        second = manager.start('2', 'fellside')

        self.assertEqual(docker.networks[first.network],
                         {first.name, 'pwnshop-labproxy'})
        self.assertEqual(docker.networks[second.network],
                         {second.name, 'pwnshop-labproxy'})

    def test_starting_the_same_lab_again_returns_the_running_instance(self):
        manager, _ = make_manager()

        first = manager.start('7', 'fellside')
        again = manager.start('7', 'fellside')

        self.assertIs(again, first)

    def test_a_student_cannot_run_two_labs_at_once(self):
        catalogue = dict(CATALOGUE, other=CATALOGUE['fellside'])
        manager, _ = make_manager()
        manager.catalogue = catalogue
        manager.start('7', 'fellside')

        with self.assertRaises(LabError) as caught:
            manager.start('7', 'other')

        self.assertEqual(caught.exception.status, 409)

    def test_unknown_labs_are_refused(self):
        manager, _ = make_manager()

        with self.assertRaises(LabError) as caught:
            manager.start('7', 'not-a-lab')

        self.assertEqual(caught.exception.status, 404)

    def test_refuses_new_instances_when_every_slot_is_taken(self):
        manager, _ = make_manager(max_instances=2)
        manager.start('1', 'fellside')
        manager.start('2', 'fellside')

        with self.assertRaises(LabError) as caught:
            manager.start('3', 'fellside')

        self.assertEqual(caught.exception.status, 503)

    def test_instances_stop_after_their_lifetime(self):
        docker = FakeDocker()
        manager, clock = make_manager(docker)
        instance = manager.start('7', 'fellside')

        clock.now += 3599
        manager.stop_expired()
        self.assertIsNotNone(manager.get('7'))

        clock.now += 1
        manager.stop_expired()
        self.assertIsNone(manager.get('7'))
        self.assertNotIn(instance.name, docker.containers)
        self.assertNotIn(instance.network, docker.networks)

    def test_extending_restarts_the_full_lifetime_from_now(self):
        manager, clock = make_manager()
        manager.start('7', 'fellside')

        clock.now += 3000
        manager.extend('7')
        clock.now += 3599
        manager.stop_expired()

        self.assertIsNotNone(manager.get('7'))

    def test_stopping_removes_the_container_and_its_network(self):
        docker = FakeDocker()
        manager, _ = make_manager(docker)
        instance = manager.start('7', 'fellside')

        manager.stop('7')

        self.assertNotIn(instance.name, docker.containers)
        self.assertNotIn(instance.network, docker.networks)
        self.assertIsNone(manager.get('7'))

    def test_a_failed_start_leaves_nothing_behind(self):
        docker = FakeDocker(fail_run=True)
        manager, _ = make_manager(docker)

        with self.assertRaises(DockerError):
            manager.start('7', 'fellside')

        self.assertEqual(docker.networks, {})
        self.assertIsNone(manager.get('7'))

    def test_startup_removes_everything_a_previous_run_left(self):
        docker = FakeDocker()
        make_manager(docker)[0].start('7', 'fellside')

        fresh, _ = make_manager(docker)
        fresh.clean_up_leftovers()

        self.assertEqual(docker.containers, set())
        self.assertEqual(docker.networks, {})

    def test_a_failed_stop_keeps_the_instance_so_stopping_again_finishes_it(self):
        docker = FakeDocker()
        manager, _ = make_manager(docker)
        instance = manager.start('7', 'fellside')
        docker.stuck.add(instance.name)

        with self.assertRaises(DockerError):
            manager.stop('7')
        self.assertIs(manager.get('7'), instance)
        self.assertIn(instance.name, docker.containers)

        docker.stuck.clear()
        manager.stop('7')
        self.assertIsNone(manager.get('7'))
        self.assertNotIn(instance.name, docker.containers)
        self.assertNotIn(instance.network, docker.networks)

    def test_an_expired_instance_that_fails_to_stop_is_retried_next_pass(self):
        docker = FakeDocker()
        manager, clock = make_manager(docker)
        stuck = manager.start('1', 'fellside')
        other = manager.start('2', 'fellside')
        docker.stuck.add(stuck.name)
        clock.now += 3600

        with self.assertRaises(DockerError):
            manager.stop_expired()
        # One failure doesn't hold up the others.
        self.assertIsNone(manager.get('2'))
        self.assertNotIn(other.name, docker.containers)
        self.assertIs(manager.get('1'), stuck)

        docker.stuck.clear()
        manager.stop_expired()
        self.assertIsNone(manager.get('1'))
        self.assertNotIn(stuck.name, docker.containers)

    def test_a_failed_start_whose_clean_up_fails_reports_the_start_error(self):
        docker = FakeDocker()
        manager, _ = make_manager(docker)
        # Fails after the container is created, so there is something to
        # clean up - and the clean-up fails too.
        def failing_connect(network, container):
            raise DockerError(500, 'connect failed')
        docker.connect = failing_connect
        docker.stuck.add('fellside-00000001')

        with self.assertRaisesRegex(DockerError, 'connect failed'):
            manager.start('7', 'fellside')

        self.assertIsNone(manager.get('7'))
        self.assertIn('fellside-00000001', docker.containers)

    def test_the_sweep_removes_untracked_leftovers_but_spares_running_labs(self):
        docker = FakeDocker()
        manager, _ = make_manager(docker)
        running = manager.start('7', 'fellside')
        # As left by a failed start whose clean-up also failed.
        docker.create_network('pwnshop-net-fellside-deadbeef', {})
        docker.run('fellside-deadbeef', 'fellside:latest',
                   'pwnshop-net-fellside-deadbeef', {}, 192, 0.5)

        manager.clean_up_leftovers()

        self.assertEqual(docker.containers, {running.name})
        self.assertEqual(set(docker.networks), {running.network})
        self.assertIs(manager.get('7'), running)


class ApiTests(unittest.TestCase):
    TOKEN = 'test-token'

    def setUp(self):
        self.manager, _ = make_manager()
        self.server = ThreadingHTTPServer(
            ('127.0.0.1', 0), make_handler(self.manager, self.TOKEN))
        self.server.RequestHandlerClass.log_message = lambda *args: None
        threading.Thread(target=self.server.serve_forever, daemon=True).start()
        self.base = f'http://127.0.0.1:{self.server.server_port}'

    def tearDown(self):
        self.server.shutdown()
        self.server.server_close()

    def request(self, method, path, body=None, token=TOKEN):
        data = json.dumps(body).encode() if body is not None else None
        req = urllib.request.Request(self.base + path, data=data, method=method)
        if token:
            req.add_header('Authorization', f'Bearer {token}')
        try:
            with urllib.request.urlopen(req) as response:
                raw = response.read()
                return response.status, json.loads(raw) if raw else {}
        except urllib.error.HTTPError as error:
            return error.code, json.loads(error.read() or b'{}')

    def test_requests_without_the_token_are_refused(self):
        status, _ = self.request('PUT', '/instances/7', {'lab': 'fellside'},
                                 token=None)

        self.assertEqual(status, 401)
        self.assertIsNone(self.manager.get('7'))

    def test_requests_with_a_wrong_token_are_refused(self):
        status, _ = self.request('GET', '/instances/7', token='guess')

        self.assertEqual(status, 401)

    def test_start_then_look_up_then_stop(self):
        status, started = self.request('PUT', '/instances/7', {'lab': 'fellside'})
        self.assertEqual(status, 200)
        self.assertEqual(started['url'], 'https://fellside-00000001.pwnshop.uk/')

        status, found = self.request('GET', '/instances/7')
        self.assertEqual((status, found), (200, started))

        status, _ = self.request('DELETE', '/instances/7')
        self.assertEqual(status, 204)
        status, _ = self.request('GET', '/instances/7')
        self.assertEqual(status, 404)

    def test_a_failed_stop_is_reported_and_the_lab_stays_listed(self):
        self.request('PUT', '/instances/7', {'lab': 'fellside'})
        self.manager.docker.stuck.add('fellside-00000001')

        status, body = self.request('DELETE', '/instances/7')

        self.assertEqual(status, 502)
        self.assertIn('could not be stopped', body['error'])
        status, _ = self.request('GET', '/instances/7')
        self.assertEqual(status, 200)

    def test_user_ids_must_be_numeric(self):
        status, _ = self.request('PUT', '/instances/../x', {'lab': 'fellside'})

        self.assertEqual(status, 404)


if __name__ == '__main__':
    unittest.main()
