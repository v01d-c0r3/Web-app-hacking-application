"""Tests for the lab catalogue, lab instances and the login in front of them.

Covered: only logged-in students reach the lab list; it shows the active labs,
Fellside included out of the box; and the one property that is invisible on
the page but matters - a lab, hostile by design, gets no handle back on the
portal tab that opened it.

With a lab manager configured: students start, extend and stop their own
instance, only by POST and only for themselves; refusals from the manager are
shown rather than crashing; and the client sends the manager its token. The
lab manager itself is mocked - it has its own tests in labmanager/tests.py.
"""

import json
from datetime import timedelta
from unittest import mock

from django.contrib.auth import get_user_model
from django.test import TestCase, override_settings
from django.urls import reverse
from django.utils import timezone

from labs import manager
from labs.models import Lab

User = get_user_model()

PASSWORD = 'Labs-2026-test-pw'


class LabListViewTests(TestCase):
    def setUp(self):
        self.url = reverse('labs:lab_list')
        self.student = User.objects.create_user('student', password=PASSWORD)

    def test_anonymous_visitor_is_sent_to_log_in(self):
        response = self.client.get(self.url)

        self.assertRedirects(response, f"{reverse('login')}?next={self.url}")

    def test_lists_fellside_out_of_the_box(self):
        """Seeded by migration 0002, so a fresh install is never empty."""
        self.client.force_login(self.student)

        response = self.client.get(self.url)

        self.assertContains(response, 'Fellside')
        self.assertContains(response, 'href="http://localhost:8080"')

    def test_hides_inactive_labs(self):
        Lab.objects.create(
            name='Retired Lab',
            slug='retired',
            description='No longer offered.',
            url='http://localhost:8090',
            is_active=False,
        )
        self.client.force_login(self.student)

        response = self.client.get(self.url)

        self.assertNotContains(response, 'Retired Lab')

    def test_lab_links_give_the_lab_no_handle_on_the_portal(self):
        """Labs run attacker-written script by design (stored XSS is one of
        the lessons). With window.opener, that script could quietly swap the
        portal tab for a fake login page."""
        self.client.force_login(self.student)

        response = self.client.get(self.url)

        self.assertContains(response, 'rel="noopener noreferrer"')


class LoginTests(TestCase):
    def setUp(self):
        self.student = User.objects.create_user('student', password=PASSWORD)

    def test_student_can_log_in_and_lands_on_the_lab_list(self):
        response = self.client.post(
            reverse('login'), {'username': 'student', 'password': PASSWORD}
        )

        self.assertRedirects(response, reverse('labs:lab_list'))

    def test_wrong_password_is_refused(self):
        response = self.client.post(
            reverse('login'), {'username': 'student', 'password': 'wrong'}
        )

        self.assertEqual(response.status_code, 200)
        self.assertNotIn('_auth_user_id', self.client.session)

    def test_a_logged_in_student_skips_the_login_page(self):
        self.client.force_login(self.student)

        response = self.client.get(reverse('login'))

        self.assertRedirects(response, reverse('labs:lab_list'))

    def test_logging_out_ends_the_session_and_returns_home(self):
        self.client.force_login(self.student)

        response = self.client.post(reverse('logout'))
        self.assertRedirects(response, reverse('core:home'))

        response = self.client.get(reverse('labs:lab_list'))
        self.assertEqual(response.status_code, 302)


def running_instance(lab='fellside'):
    return manager.Instance(
        lab=lab,
        url='https://fellside-1a2b3c4d.pwnshop.uk/',
        expires_at=timezone.now() + timedelta(minutes=45),
    )


@override_settings(LAB_MANAGER_URL='http://labmanager:8000',
                   LAB_MANAGER_TOKEN='test-token')
class LabInstanceTests(TestCase):
    def setUp(self):
        self.student = User.objects.create_user('student', password=PASSWORD)
        self.client.force_login(self.student)
        self.list_url = reverse('labs:lab_list')
        self.start_url = reverse('labs:start', args=['fellside'])

    @mock.patch.object(manager, 'get_instance', return_value=None)
    def test_lab_list_offers_to_start_a_lab(self, get_instance):
        response = self.client.get(self.list_url)

        self.assertContains(response, f'action="{self.start_url}"')
        self.assertNotContains(response, 'href="http://localhost:8080"')
        get_instance.assert_called_once_with(self.student.pk)

    @mock.patch.object(manager, 'get_instance', return_value=running_instance())
    def test_running_instance_is_linked_at_its_own_address(self, get_instance):
        response = self.client.get(self.list_url)

        self.assertContains(
            response,
            'href="https://fellside-1a2b3c4d.pwnshop.uk/" target="_blank" '
            'rel="noopener noreferrer"',
        )
        self.assertContains(response, f'action="{reverse("labs:extend")}"')
        self.assertContains(response, f'action="{reverse("labs:stop")}"')

    @mock.patch.object(manager, 'get_instance',
                       side_effect=manager.LabManagerError('Not reachable.'))
    def test_lab_list_still_loads_when_the_lab_manager_is_down(self, get_instance):
        response = self.client.get(self.list_url)

        self.assertContains(response, 'Fellside')
        self.assertContains(response, 'Not reachable.')

    @mock.patch.object(manager, 'start_instance', return_value=running_instance())
    def test_start_asks_for_an_instance_for_this_student(self, start_instance):
        response = self.client.post(self.start_url)

        self.assertRedirects(response, self.list_url, fetch_redirect_response=False)
        start_instance.assert_called_once_with(self.student.pk, 'fellside')

    @mock.patch.object(manager, 'start_instance')
    def test_start_only_accepts_post(self, start_instance):
        """A GET could be triggered by a link or an image on a lab page."""
        response = self.client.get(self.start_url)

        self.assertEqual(response.status_code, 405)
        start_instance.assert_not_called()

    @mock.patch.object(manager, 'start_instance')
    def test_anonymous_visitor_cannot_start_a_lab(self, start_instance):
        self.client.logout()

        response = self.client.post(self.start_url)

        self.assertEqual(response.status_code, 302)
        start_instance.assert_not_called()

    @mock.patch.object(manager, 'start_instance')
    def test_inactive_labs_cannot_be_started(self, start_instance):
        Lab.objects.filter(slug='fellside').update(is_active=False)

        response = self.client.post(self.start_url)

        self.assertEqual(response.status_code, 404)
        start_instance.assert_not_called()

    @mock.patch.object(manager, 'get_instance', return_value=None)
    @mock.patch.object(manager, 'start_instance',
                       side_effect=manager.LabManagerError('Every lab slot is in use.'))
    def test_a_refused_start_is_explained(self, start_instance, get_instance):
        response = self.client.post(self.start_url, follow=True)

        self.assertContains(response, 'Every lab slot is in use.')

    @mock.patch.object(manager, 'extend_instance', return_value=running_instance())
    def test_extend_asks_for_more_time_for_this_student(self, extend_instance):
        self.client.post(reverse('labs:extend'))

        extend_instance.assert_called_once_with(self.student.pk)

    @mock.patch.object(manager, 'stop_instance')
    def test_stop_stops_this_students_instance(self, stop_instance):
        self.client.post(reverse('labs:stop'))

        stop_instance.assert_called_once_with(self.student.pk)


@override_settings(LAB_MANAGER_URL='')
class WithoutLabManagerTests(TestCase):
    def setUp(self):
        self.student = User.objects.create_user('student', password=PASSWORD)
        self.client.force_login(self.student)

    def test_instance_actions_do_not_exist(self):
        """On a laptop the lab list links to labs directly; nothing to start."""
        for url in (reverse('labs:start', args=['fellside']),
                    reverse('labs:extend'), reverse('labs:stop')):
            with self.subTest(url=url):
                self.assertEqual(self.client.post(url).status_code, 404)


class FakeResponse:
    def __init__(self, body):
        self._body = json.dumps(body).encode()

    def read(self):
        return self._body

    def __enter__(self):
        return self

    def __exit__(self, *exc):
        return False


@override_settings(LAB_MANAGER_URL='http://labmanager:8000',
                   LAB_MANAGER_TOKEN='test-token')
class LabManagerClientTests(TestCase):
    @mock.patch('labs.manager.urllib.request.urlopen')
    def test_sends_the_token_and_the_students_id(self, urlopen):
        urlopen.return_value = FakeResponse({
            'lab': 'fellside',
            'url': 'https://fellside-1a2b3c4d.pwnshop.uk/',
            'expires_at': '2026-11-17T23:00:00+00:00',
        })

        instance = manager.start_instance(7, 'fellside')

        request = urlopen.call_args.args[0]
        self.assertEqual(request.full_url, 'http://labmanager:8000/instances/7')
        self.assertEqual(request.get_method(), 'PUT')
        self.assertEqual(request.get_header('Authorization'), 'Bearer test-token')
        self.assertEqual(instance.url, 'https://fellside-1a2b3c4d.pwnshop.uk/')

    @mock.patch('labs.manager.urllib.request.urlopen',
                side_effect=ConnectionRefusedError())
    def test_an_unreachable_manager_becomes_a_readable_error(self, urlopen):
        with self.assertRaisesMessage(manager.LabManagerError, 'not reachable'):
            manager.get_instance(7)
