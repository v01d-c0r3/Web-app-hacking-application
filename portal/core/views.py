"""Public pages - everything a visitor can see before logging in."""

from django.views.generic import TemplateView


class HomeView(TemplateView):
    """The landing page: what the labs are, how they work, and the way in."""

    template_name = 'core/home.html'
