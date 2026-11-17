"""Tests for the public landing page.

Covered: anyone can see it without logging in; it has the About and How it
works sections the navigation links point at; and its calls to action follow
the visitor - "Log in" for anonymous visitors, the way to their labs for
students already signed in.
"""

from django.contrib.auth import get_user_model
from django.test import TestCase
from django.urls import reverse

User = get_user_model()

PASSWORD = 'Core-2026-test-pw'


class HomeViewTests(TestCase):
    def setUp(self):
        self.url = reverse('core:home')

    def test_is_public(self):
        response = self.client.get(self.url)

        self.assertEqual(response.status_code, 200)

    def test_has_the_sections_the_navigation_links_to(self):
        response = self.client.get(self.url)

        self.assertContains(response, 'id="about"')
        self.assertContains(response, 'id="how-it-works"')
        self.assertContains(response, f'href="{self.url}#about"')
        self.assertContains(response, f'href="{self.url}#how-it-works"')

    def test_anonymous_visitor_is_offered_the_login(self):
        response = self.client.get(self.url)

        self.assertContains(response, f'href="{reverse("login")}"')
        self.assertNotContains(response, f'href="{reverse("labs:lab_list")}"')

    def test_logged_in_student_is_offered_their_labs_instead(self):
        student = User.objects.create_user('student', password=PASSWORD)
        self.client.force_login(student)

        response = self.client.get(self.url)

        self.assertContains(response, f'href="{reverse("labs:lab_list")}"')
        self.assertNotContains(response, f'href="{reverse("login")}"')
