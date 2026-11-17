"""Project test runner."""

from django.test.runner import DiscoverRunner
from django.test.utils import override_settings


class PortalTestRunner(DiscoverRunner):
    """Runs the tests with WhiteNoise in autorefresh mode.

    STATIC_ROOT only exists once collectstatic has run, which happens in the
    Docker build. Outside Docker, WhiteNoise would warn that it is missing on
    every test run - noise that teaches everyone to ignore warnings.
    Autorefresh mode looks files up per request instead of scanning STATIC_ROOT
    at startup, so the warning never fires. The tests never request static
    files, so nothing else changes.

    Production keeps the default (autorefresh off), where a missing STATIC_ROOT
    is worth hearing about.
    """

    def setup_test_environment(self, **kwargs):
        super().setup_test_environment(**kwargs)
        # override_settings rather than assigning to settings directly, so the
        # change is undone cleanly in teardown.
        self._whitenoise = override_settings(WHITENOISE_AUTOREFRESH=True)
        self._whitenoise.enable()

    def teardown_test_environment(self, **kwargs):
        self._whitenoise.disable()
        super().teardown_test_environment(**kwargs)
