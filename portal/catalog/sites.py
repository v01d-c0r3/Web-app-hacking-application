import json
from dataclasses import dataclass
from pathlib import Path
from urllib.parse import urlsplit


LOCAL_HOSTS = {"localhost", "127.0.0.1", "::1"}
CONTAINER_MARKERS = ("Dockerfile", "docker-compose.yml", "compose.yaml", "compose.yml")


@dataclass(frozen=True)
class VulnerableSite:
    slug: str
    name: str
    description: str
    url: str


def _validate_site_url(url: str, folder: str) -> None:
    parsed = urlsplit(url)
    if (
        parsed.scheme != "http"
        or parsed.hostname not in LOCAL_HOSTS
        or parsed.username is not None
        or parsed.password is not None
    ):
        raise ValueError(f"{folder}/site.json must use an http://localhost URL")


def discover_sites(sites_dir: Path) -> list[VulnerableSite]:
    if not sites_dir.is_dir():
        return []

    sites = []
    for folder in sorted(sites_dir.iterdir(), key=lambda path: path.name.casefold()):
        has_container_config = any(
            (folder / marker).is_file() for marker in CONTAINER_MARKERS
        )
        if not folder.is_dir() or not has_container_config:
            continue

        manifest_path = folder / "site.json"
        if not manifest_path.is_file():
            continue

        with manifest_path.open(encoding="utf-8") as manifest_file:
            manifest = json.load(manifest_file)

        name = manifest.get("name")
        description = manifest.get("description")
        url = manifest.get("url")
        if not all(isinstance(value, str) and value.strip() for value in (name, description, url)):
            raise ValueError(
                f"{folder.name}/site.json requires non-empty name, description, and url fields"
            )

        _validate_site_url(url, folder.name)
        sites.append(
            VulnerableSite(
                slug=folder.name,
                name=name,
                description=description,
                url=url,
            )
        )

    return sites
