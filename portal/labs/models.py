"""Models for the lab catalogue.

A lab is one deliberately vulnerable app running in its own container under
vulnerable-sites/. The portal only ever *links* to a lab - it never starts,
stops or talks to the container - so all it needs to know is what the lab is
called and where a student's browser should go.
"""

from django.db import models


class Lab(models.Model):
    name = models.CharField(max_length=100, unique=True)
    slug = models.SlugField(
        max_length=50,
        unique=True,
        help_text='Matches the lab folder name under vulnerable-sites/.',
    )
    description = models.TextField(
        help_text='Shown to students on the lab list. One or two sentences.',
    )
    # On the VPS this must be a different hostname from the portal - not just a
    # different port. Browsers share cookies across ports on the same host, so
    # a lab there would be sent every student's portal session cookie, and the
    # labs are exploitable by design. Locally, sharing localhost is fine: the
    # only session at stake is your own.
    url = models.URLField(
        verbose_name='URL',
        help_text='The address students open the lab at, e.g. '
                  'http://localhost:8080 on a laptop. On the VPS this must be '
                  'a different hostname from the portal.',
    )
    is_active = models.BooleanField(
        default=True,
        help_text='Uncheck to hide the lab from students without deleting it.',
    )
    sort_order = models.PositiveIntegerField(
        default=0,
        help_text='Lower numbers appear first.',
    )

    class Meta:
        ordering = ['sort_order', 'name']

    def __str__(self):
        return self.name
