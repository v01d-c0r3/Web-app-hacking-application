"""A minimal client for the Docker Engine API over its Unix socket.

Standard library only, so the lab manager's image needs no packages - one
less dependency on the one container that holds the Docker socket. It covers
exactly the calls the lab manager makes and nothing more.
"""

import http.client
import json
import socket
from urllib.parse import quote, urlencode


class DockerError(Exception):
    def __init__(self, status, message):
        super().__init__(f'Docker API returned {status}: {message}')
        self.status = status


class _UnixHTTPConnection(http.client.HTTPConnection):
    def __init__(self, socket_path, timeout):
        # The host name only fills the Host header; the socket decides where
        # the request actually goes.
        super().__init__('docker', timeout=timeout)
        self._socket_path = socket_path

    def connect(self):
        sock = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
        sock.settimeout(self.timeout)
        sock.connect(self._socket_path)
        self.sock = sock


class Docker:
    def __init__(self, socket_path='/var/run/docker.sock', timeout=60):
        self._socket_path = socket_path
        self._timeout = timeout

    def _request(self, method, path, body=None, ok=(200, 201, 204)):
        conn = _UnixHTTPConnection(self._socket_path, self._timeout)
        try:
            headers = {}
            data = None
            if body is not None:
                data = json.dumps(body).encode()
                headers['Content-Type'] = 'application/json'
            conn.request(method, path, body=data, headers=headers)
            response = conn.getresponse()
            raw = response.read()
        finally:
            conn.close()
        if response.status not in ok:
            raise DockerError(response.status, raw.decode(errors='replace'))
        return json.loads(raw) if raw else None

    @staticmethod
    def _label_filter(label):
        return urlencode({'filters': json.dumps({'label': [label]})})

    def create_network(self, name, labels):
        # Internal: the network has no route off the host, so a student with
        # code execution in a lab can't use it to reach the internet.
        self._request('POST', '/networks/create', {
            'Name': name,
            'Internal': True,
            'Labels': labels,
        })

    def remove_network(self, name):
        # 404 counts as success: the goal is "gone", however it got there.
        self._request('DELETE', f'/networks/{quote(name)}', ok=(200, 204, 404))

    def connect(self, network, container):
        self._request('POST', f'/networks/{quote(network)}/connect',
                      {'Container': container})

    def disconnect(self, network, container):
        self._request('POST', f'/networks/{quote(network)}/disconnect',
                      {'Container': container, 'Force': True},
                      ok=(200, 204, 404))

    def run(self, name, image, network, labels, memory_mb, cpus, writable):
        memory = memory_mb * 1024 * 1024
        self._request('POST', f'/containers/create?{urlencode({"name": name})}', {
            'Image': image,
            'Hostname': name,
            'Labels': labels,
            'HostConfig': {
                'NetworkMode': network,
                'Memory': memory,
                # Equal to Memory: no swap on top, so the cap is a real cap.
                'MemorySwap': memory,
                'NanoCpus': int(cpus * 1_000_000_000),
                'PidsLimit': 128,
                # Raw sockets would let a student forge packets on the lab
                # network. The other defaults stay: Apache-based labs need
                # them to start as root and drop to www-data.
                'CapDrop': ['NET_RAW'],
                'SecurityOpt': ['no-new-privileges:true'],
                # Docker can only cap a container's disk use on XFS with
                # project quotas, which the VPS can't be assumed to have. So
                # the image is read-only and the paths the lab writes to are
                # tmpfs: size-capped, and held in RAM that counts against
                # Memory above - a student can't fill the host's disk.
                'ReadonlyRootfs': True,
                'Tmpfs': {path: f'rw,noexec,nosuid,nodev,size={size}'
                          for path, size in writable.items()},
                # The daemon's default keeps every log line forever, and
                # Apache logs each request to stdout. Set here rather than in
                # the daemon's config, which the VPS shares with other things.
                'LogConfig': {'Type': 'json-file',
                              'Config': {'max-size': '10m', 'max-file': '1'}},
            },
        })
        self._request('POST', f'/containers/{quote(name)}/start')

    def remove_container(self, name):
        self._request('DELETE', f'/containers/{quote(name)}?force=true',
                      ok=(200, 204, 404))

    def container_networks(self, name):
        found = self._request('GET', f'/containers/{quote(name)}/json')
        return list(found['NetworkSettings']['Networks'])

    def containers_with_label(self, label):
        found = self._request(
            'GET', f'/containers/json?all=true&{self._label_filter(label)}')
        # Docker prefixes container names with a slash.
        return [c['Names'][0].lstrip('/') for c in found]

    def networks_with_label(self, label):
        found = self._request('GET', f'/networks?{self._label_filter(label)}')
        return [n['Name'] for n in found]
