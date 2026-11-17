"""Views for the lab catalogue."""

from django.contrib.auth.mixins import LoginRequiredMixin
from django.views.generic import ListView

from .models import Lab


class LabListView(LoginRequiredMixin, ListView):
    """Every lab a logged-in student can open, in the order set in the admin."""

    queryset = Lab.objects.filter(is_active=True)
    template_name = 'labs/lab_list.html'
    context_object_name = 'labs'
