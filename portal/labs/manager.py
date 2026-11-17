"""Client for the lab manager, which starts and stops students' lab instances.

The portal never touches Docker: it asks the lab manager (labmanager/ at the
repo root) over the internal network, and the manager does the rest. See the
manager's module docstring for the API.
"""

import json
import urllib.error
import urllib.request
from dataclasses import dataclass
from datetime import datetime

from django.conf import settings


class LabManagerError(Exception):
    """The lab manager refused or couldn't be reached. str() is safe to show."""


@dataclass
class Instance:
    lab: str
    url: str
    expires_at: datetime


def _call(method, path, body=None, timeout=10):
    data = json.dumps(body).encode() if body is not None else None
    request = urllib.request.Request(
        settings.LAB_MANAGER_URL.rstrip('/') + path,
        data=data,
        method=method,
        headers={
            'Authorization': f'Bearer {settings.LAB_MANAGER_TOKEN}',
            'Content-Type': 'application/json',
        },
    )
    try:
        with urllib.request.urlopen(request, timeout=timeout) as response:
            raw = response.read()
    except urllib.error.HTTPError as error:
        if error.code == 404 and method == 'GET':
            return None
        try:
            message = json.loads(error.read())['error']
        except (ValueError, KeyError, TypeError):
            message = 'The lab service returned an error.'
        raise LabManagerError(message) from error
    except OSError as error:
        raise LabManagerError('The lab service is not reachable right now.') from error
    return json.loads(raw) if raw else None


def _instance(data):
    return Instance(
        lab=data['lab'],
        url=data['url'],
        expires_at=datetime.fromisoformat(data['expires_at']),
    )


def get_instance(user_id):
    data = _call('GET', f'/instances/{user_id}')
    return _instance(data) if data else None


def start_instance(user_id, lab_slug):
    # Longer timeout: starting means creating a network and a container.
    return _instance(_call('PUT', f'/instances/{user_id}', {'lab': lab_slug},
                           timeout=60))


def extend_instance(user_id):
    return _instance(_call('POST', f'/instances/{user_id}/extend'))


def stop_instance(user_id):
    _call('DELETE', f'/instances/{user_id}', timeout=30)
