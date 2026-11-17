"""Tests for the lab catalogue and the login in front of it.

Covered: only logged-in students reach the lab list; it shows the active labs,
Fellside included out of the box; and the one property that is invisible on
the page but matters - a lab, hostile by design, gets no handle back on the
portal tab that opened it.
"""

from django.contrib.auth import get_user_model
from django.test import TestCase
from django.urls import reverse

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

    def test_logging_out_ends_the_session(self):
        self.client.force_login(self.student)

        self.client.post(reverse('logout'))
        response = self.client.get(reverse('labs:lab_list'))

        self.assertEqual(response.status_code, 302)
