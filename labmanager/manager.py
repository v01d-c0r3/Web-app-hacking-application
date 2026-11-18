"""The lab manager: starts and stops one lab instance per student.

The portal never touches Docker - whatever holds the Docker socket is root on
the VPS, and the portal faces the internet. This service holds the socket
instead, and offers the portal only this narrow, token-protected API on a
private network:

    GET    /instances/<user>         the student's running instance, or 404
    PUT    /instances/<user>         start one: {"lab": "<slug>"}
    POST   /instances/<user>/extend  push the expiry back to a full lifetime
    DELETE /instances/<user>         stop it

So a compromised portal can start and stop labs from the catalogue, and
nothing else.

Each instance is a container named <lab>-<8 hex>, on a network of its own
with no route off the host. The lab proxy is connected to that network so it
can forward https://<lab>-<8 hex>.<LAB_DOMAIN>/ to it - and reconnected
within 30 seconds if the proxy container is recreated and loses it.

State is held in memory only. On startup every container and network this
service created is removed, so a restart stops all running labs - acceptable
while instances last an hour, and it means nothing is ever left running that
the manager has forgotten about. The same sweep, sparing tracked instances,
runs every 30 seconds for anything a failed Docker call left behind.
"""

import hmac
import json
import os
import re
import secrets
import sys
import threading
import time
from dataclasses import dataclass
from datetime import datetime, timezone
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path

from docker_api import Docker, DockerError

MANAGED_LABEL = 'pwnshop.managed'


class LabError(Exception):
    """A request the manager refuses; status is the HTTP status to answer with."""

    def __init__(self, status, message):
        super().__init__(message)
        self.status = status


@dataclass
class Instance:
    user: str
    lab: str
    name: str
    network: str
    expires_at: float

    def as_json(self, domain):
        return {
            'lab': self.lab,
            'url': f'https://{self.name}.{domain}/',
            'expires_at': datetime.fromtimestamp(
                self.expires_at, timezone.utc).isoformat(),
        }


class Manager:
    def __init__(self, docker, catalogue, domain, proxy_container,
                 lifetime_seconds=3600, max_instances=10,
                 clock=time.time, make_id=lambda: secrets.token_hex(4)):
        self.docker = docker
        self.catalogue = catalogue
        self.domain = domain
        self.proxy_container = proxy_container
        self.lifetime = lifetime_seconds
        self.max_instances = max_instances
        self._clock = clock
        self._make_id = make_id
        self._instances = {}
        # One lock around every change, Docker calls included: starts take a
        # second or two and there are at most a handful of students, so
        # serialising them is simpler than reasoning about races.
        self._lock = threading.Lock()

    def clean_up_leftovers(self):
        """Remove every container and network this service made but isn't tracking.

        At startup that is everything a previous run left. After that it is
        whatever a failed Docker call stranded - a start whose own clean-up
        failed, say - so the reaper runs this on every pass too, as a safety
        net: nothing the manager created stays running unaccounted for.
        """
        label = f'{MANAGED_LABEL}=true'
        # Under the lock so a start in progress, whose container exists before
        # its instance is recorded, isn't mistaken for a leftover.
        with self._lock:
            names = {i.name for i in self._instances.values()}
            networks = {i.network for i in self._instances.values()}
            # Containers first: Docker won't remove a network a container is
            # still on. If one fails, its network waits for the next pass.
            _for_each(
                [c for c in self.docker.containers_with_label(label)
                 if c not in names],
                self.docker.remove_container)
            _for_each(
                [n for n in self.docker.networks_with_label(label)
                 if n not in networks],
                self._remove_network)

    def reconnect_proxy(self):
        """Join the lab proxy to any running instance's network it isn't on.

        The proxy joins each instance's network at runtime, and Docker drops
        those connections whenever the proxy container is recreated - by a
        `docker compose up` after a Caddyfile change, say. Every running lab
        would then answer 502 while still listed as running. The reaper runs
        this on every pass, so the proxy heals itself within one interval.
        """
        with self._lock:
            # Only the missing ones: Docker refuses to connect a container to
            # a network it is already on.
            joined = set(self.docker.container_networks(self.proxy_container))
            _for_each(
                [i.network for i in self._instances.values()
                 if i.network not in joined],
                lambda network: self.docker.connect(network, self.proxy_container))

    def get(self, user):
        with self._lock:
            return self._instances.get(user)

    def start(self, user, lab):
        if lab not in self.catalogue:
            raise LabError(404, f'Unknown lab: {lab}')
        with self._lock:
            current = self._instances.get(user)
            if current is not None:
                if current.lab == lab:
                    return current
                raise LabError(409, f'Stop your {current.lab} lab first.')
            if len(self._instances) >= self.max_instances:
                raise LabError(503, 'Every lab slot is in use. Try again later.')

            spec = self.catalogue[lab]
            name = f'{lab}-{self._make_id()}'
            network = f'pwnshop-net-{name}'
            labels = {MANAGED_LABEL: 'true', 'pwnshop.user': user,
                      'pwnshop.lab': lab}
            self.docker.create_network(network, labels)
            try:
                self.docker.run(name, spec['image'], network, labels,
                                spec['memory_mb'], spec['cpus'],
                                spec.get('writable', {}))
                self.docker.connect(network, self.proxy_container)
            except DockerError:
                # Best effort. If this fails too, clean_up_leftovers sweeps up
                # what's left, and the student still sees why the start failed
                # rather than an error about the clean-up.
                try:
                    self._remove(name, network)
                except DockerError:
                    pass
                raise

            instance = Instance(user, lab, name, network,
                                self._clock() + self.lifetime)
            self._instances[user] = instance
            return instance

    def extend(self, user):
        with self._lock:
            instance = self._instances.get(user)
            if instance is None:
                raise LabError(404, 'No lab is running.')
            instance.expires_at = self._clock() + self.lifetime
            return instance

    def stop(self, user):
        with self._lock:
            if user in self._instances:
                self._stop_locked(user)

    def stop_expired(self):
        now = self._clock()
        with self._lock:
            expired = [u for u, i in self._instances.items()
                       if i.expires_at <= now]
            # Every expired instance gets its attempt, even after one fails;
            # the ones that failed stay listed and are retried next pass.
            _for_each(expired, self._stop_locked)

    def _stop_locked(self, user):
        # Forget the instance only once it is gone. If Docker fails part-way,
        # it stays listed - still counted against the cap, still shown to the
        # student - and the next stop or reaper pass finishes the job, instead
        # of a container running on that nothing will ever remove. Every
        # removal step treats "already gone" as success, so retrying is safe.
        instance = self._instances[user]
        self._remove(instance.name, instance.network)
        del self._instances[user]

    def _remove(self, name, network):
        self.docker.remove_container(name)
        self._remove_network(network)

    def _remove_network(self, network):
        self.docker.disconnect(network, self.proxy_container)
        self.docker.remove_network(network)


def _for_each(items, action):
    """Apply action to every item, then raise the first DockerError, if any.

    For clean-up: giving up at the first failure would leave everything after
    it running too.
    """
    first_error = None
    for item in items:
        try:
            action(item)
        except DockerError as error:
            first_error = first_error or error
    if first_error is not None:
        raise first_error


USER_PATH = re.compile(r'^/instances/(\d+)(/extend)?$')


def make_handler(manager, token):
    class Handler(BaseHTTPRequestHandler):
        def _authorised(self):
            given = self.headers.get('Authorization', '')
            return hmac.compare_digest(given.encode(),
                                       f'Bearer {token}'.encode())

        def _send(self, status, body=None):
            data = json.dumps(body if body is not None else {}).encode()
            self.send_response(status)
            self.send_header('Content-Type', 'application/json')
            self.send_header('Content-Length', str(len(data)))
            self.end_headers()
            self.wfile.write(data)

        def _dispatch(self, method):
            if not self._authorised():
                return self._send(401, {'error': 'Unauthorised'})
            match = USER_PATH.match(self.path)
            if match is None:
                return self._send(404, {'error': 'Not found'})
            user, extend = match.group(1), match.group(2)
            try:
                if method == 'GET' and not extend:
                    instance = manager.get(user)
                    if instance is None:
                        return self._send(404, {'error': 'No lab is running.'})
                    return self._send(200, instance.as_json(manager.domain))
                if method == 'PUT' and not extend:
                    length = int(self.headers.get('Content-Length') or 0)
                    lab = json.loads(self.rfile.read(length) or b'{}').get('lab')
                    instance = manager.start(user, str(lab))
                    return self._send(200, instance.as_json(manager.domain))
                if method == 'POST' and extend:
                    instance = manager.extend(user)
                    return self._send(200, instance.as_json(manager.domain))
                if method == 'DELETE' and not extend:
                    manager.stop(user)
                    return self._send(204)
                return self._send(405, {'error': 'Method not allowed'})
            except LabError as error:
                return self._send(error.status, {'error': str(error)})
            except (ValueError, AttributeError):
                return self._send(400, {'error': 'Bad request'})
            except DockerError as error:
                self.log_error('%s', error)
                # Stopping can fail now too, and the student is told so: their
                # lab is still listed, and pressing Stop again retries.
                action = 'stopped' if method == 'DELETE' else 'started'
                return self._send(502, {
                    'error': f'The lab could not be {action}. Try again in a moment.'})

        def do_GET(self):
            self._dispatch('GET')

        def do_PUT(self):
            self._dispatch('PUT')

        def do_POST(self):
            self._dispatch('POST')

        def do_DELETE(self):
            self._dispatch('DELETE')

    return Handler


def _reap_forever(manager, interval=30):
    while True:
        time.sleep(interval)
        # Each in its own try, so a failure in one doesn't skip the others.
        for task in (manager.stop_expired, manager.clean_up_leftovers,
                     manager.reconnect_proxy):
            try:
                task()
            except DockerError as error:
                print(f'Lab clean-up ({task.__name__}) failed, retrying in '
                      f'{interval}s: {error}', file=sys.stderr)


def main():
    # Required settings fail at startup rather than defaulting: an empty
    # token would let anything on the network start labs.
    token = os.environ.get('LAB_MANAGER_TOKEN', '')
    domain = os.environ.get('LAB_DOMAIN', '')
    if not token or not domain:
        sys.exit('LAB_MANAGER_TOKEN and LAB_DOMAIN must both be set.')

    catalogue = json.loads(
        (Path(__file__).parent / 'labs.json').read_text())
    manager = Manager(
        Docker(),
        catalogue,
        domain,
        proxy_container=os.environ.get('LAB_PROXY_CONTAINER', 'pwnshop-labproxy'),
        lifetime_seconds=int(os.environ.get('LAB_LIFETIME_MINUTES', '60')) * 60,
        max_instances=int(os.environ.get('LAB_MAX_INSTANCES', '10')),
    )
    manager.clean_up_leftovers()
    threading.Thread(target=_reap_forever, args=(manager,), daemon=True).start()

    server = ThreadingHTTPServer(('0.0.0.0', 8000), make_handler(manager, token))
    print('Lab manager listening on :8000', flush=True)
    server.serve_forever()


if __name__ == '__main__':
    main()
