from django.conf import settings
from django.shortcuts import render

from catalog.sites import discover_sites


def index(request):
    return render(
        request,
        "catalog/index.html",
        {"sites": discover_sites(settings.VULNERABLE_SITES_DIR)},
    )
