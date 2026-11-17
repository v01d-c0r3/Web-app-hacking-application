import json
import tempfile
from pathlib import Path

from django.test import SimpleTestCase, override_settings

from catalog.sites import discover_sites


class DiscoverSitesTests(SimpleTestCase):
    def test_discovers_container_site_from_manifest(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            sites_dir = Path(temporary_directory)
            fellside = sites_dir / "fellside"
            fellside.mkdir()
            (fellside / "Dockerfile").touch()
            (fellside / "site.json").write_text(
                json.dumps(
                    {
                        "name": "Fellside",
                        "description": "A demo shop.",
                        "url": "http://localhost:8080",
                    }
                ),
                encoding="utf-8",
            )

            sites = discover_sites(sites_dir)

        self.assertEqual([site.slug for site in sites], ["fellside"])
        self.assertEqual(sites[0].url, "http://localhost:8080")

    def test_rejects_non_local_site_urls(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            sites_dir = Path(temporary_directory)
            site = sites_dir / "unsafe"
            site.mkdir()
            (site / "Dockerfile").touch()
            (site / "site.json").write_text(
                json.dumps(
                    {
                        "name": "Unsafe",
                        "description": "Not local.",
                        "url": "https://example.com",
                    }
                ),
                encoding="utf-8",
            )

            with self.assertRaisesRegex(ValueError, "http://localhost"):
                discover_sites(sites_dir)

    def test_index_renders_discovered_sites(self):
        with tempfile.TemporaryDirectory() as temporary_directory:
            site = Path(temporary_directory) / "fellside"
            site.mkdir()
            (site / "Dockerfile").touch()
            (site / "site.json").write_text(
                json.dumps(
                    {
                        "name": "Fellside",
                        "description": "A demo shop.",
                        "url": "http://localhost:8080",
                    }
                ),
                encoding="utf-8",
            )

            with override_settings(VULNERABLE_SITES_DIR=Path(temporary_directory)):
                response = self.client.get("/")

        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "Fellside")
        self.assertContains(response, "http://localhost:8080")
