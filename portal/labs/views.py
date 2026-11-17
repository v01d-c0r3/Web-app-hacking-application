"""Views for the lab catalogue and students' lab instances."""

from django.conf import settings
from django.contrib import messages
from django.contrib.auth.decorators import login_required
from django.contrib.auth.mixins import LoginRequiredMixin
from django.http import Http404
from django.shortcuts import get_object_or_404, redirect
from django.views.decorators.http import require_POST
from django.views.generic import ListView

from . import manager
from .models import Lab


class LabListView(LoginRequiredMixin, ListView):
    """Every lab a logged-in student can open, in the order set in the admin.

    With a lab manager configured, each lab gets a Start button and the
    student's running instance, if any; without one, a plain link to the lab.
    """

    queryset = Lab.objects.filter(is_active=True)
    template_name = 'labs/lab_list.html'
    context_object_name = 'labs'

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context['spawning'] = bool(settings.LAB_MANAGER_URL)
        context['instance'] = None
        if context['spawning']:
            # The list stays usable if the manager is down; only the
            # instance controls are lost.
            try:
                context['instance'] = manager.get_instance(self.request.user.pk)
            except manager.LabManagerError as error:
                messages.error(self.request, str(error))
        return context


def _require_lab_manager():
    if not settings.LAB_MANAGER_URL:
        raise Http404('Labs are not started from the portal here.')


@require_POST
@login_required
def start_lab(request, slug):
    _require_lab_manager()
    lab = get_object_or_404(Lab, slug=slug, is_active=True)
    try:
        manager.start_instance(request.user.pk, lab.slug)
    except manager.LabManagerError as error:
        messages.error(request, str(error))
    return redirect('labs:lab_list')


@require_POST
@login_required
def extend_lab(request):
    _require_lab_manager()
    try:
        manager.extend_instance(request.user.pk)
    except manager.LabManagerError as error:
        messages.error(request, str(error))
    return redirect('labs:lab_list')


@require_POST
@login_required
def stop_lab(request):
    _require_lab_manager()
    try:
        manager.stop_instance(request.user.pk)
    except manager.LabManagerError as error:
        messages.error(request, str(error))
    return redirect('labs:lab_list')
