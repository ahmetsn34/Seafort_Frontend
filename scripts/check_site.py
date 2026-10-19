#!/usr/bin/env python3
"""Check local page links and assets without third-party packages or requests."""

from html.parser import HTMLParser
from pathlib import Path
import os
import re
import sys
from urllib.parse import unquote, urlsplit


ROOT = Path(__file__).resolve().parents[1]
# Documented in assets/img/README.txt: the CSS supplies a dark fallback.
OPTIONAL_ASSETS = {"assets/img/hero.jpg"}
CSS_URL = re.compile(r"url\(\s*(['\"]?)(.*?)\1\s*\)", re.IGNORECASE)


class Page(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.ids = set()
        self.references = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if attrs.get("id"):
            self.ids.add(attrs["id"])
        if tag == "a" and attrs.get("name"):
            self.ids.add(attrs["name"])
        for attribute in ("href", "src", "poster"):
            value = attrs.get(attribute)
            if value:
                self.references.append((self.getpos()[0], value))
        if attrs.get("style"):
            self.references.extend(
                (self.getpos()[0], match[1])
                for match in CSS_URL.findall(attrs["style"])
            )

    handle_startendtag = handle_starttag


def exact_file(relative):
    """Also catch casing mistakes on case-insensitive developer machines."""
    current = ROOT
    for part in relative.parts:
        if not current.is_dir() or part not in {p.name for p in current.iterdir()}:
            return False
        current /= part
    return current.is_file()


def main():
    pages = {}
    references = []
    errors = []
    optional = set()
    placeholders = 0
    checked = 0

    for path in sorted(ROOT.glob("*.html")):
        page = Page()
        page.feed(path.read_text(encoding="utf-8-sig"))
        pages[path.resolve()] = page
        references.extend((path, line, url) for line, url in page.references)

    if not pages or not (ROOT / "index.html").is_file():
        errors.append("No site entry page found: index.html is required.")

    for path in sorted((ROOT / "assets").rglob("*.css")):
        css = re.sub(r"/\*.*?\*/", "", path.read_text(encoding="utf-8-sig"), flags=re.S)
        references.extend((path, 0, url) for _, url in CSS_URL.findall(css))

    for source, line, url in references:
        parsed = urlsplit(url.strip())
        if parsed.scheme or parsed.netloc:
            continue
        if not parsed.path and not parsed.fragment:
            placeholders += 1
            continue

        base = ROOT if parsed.path.startswith("/") else source.parent
        target_path = base / unquote(parsed.path).lstrip("/") if parsed.path else source
        target = target_path.resolve()
        label = f"{source.relative_to(ROOT).as_posix()}:{line}" if line else source.relative_to(ROOT).as_posix()
        try:
            target.relative_to(ROOT)
        except ValueError:
            errors.append(f"{label}: local URL escapes the site root: {url}")
            continue

        # resolve() can correct filename casing on Windows; preserve the URL spelling.
        relative = Path(os.path.relpath(target_path, ROOT))

        if not exact_file(relative):
            if relative.as_posix() in OPTIONAL_ASSETS:
                optional.add(relative.as_posix())
            else:
                errors.append(f"{label}: missing local file (or wrong case): {url}")
            continue

        checked += 1
        if parsed.fragment and target in pages:
            if unquote(parsed.fragment) not in pages[target].ids:
                errors.append(f"{label}: missing fragment target: {url}")

    for asset in sorted(optional):
        print(f"NOTE: optional {asset} is absent; the documented CSS fallback applies.")
    if placeholders:
        print(f"NOTE: {placeholders} '#' placeholder links have no destination yet.")
    for error in errors:
        print(f"ERROR: {error}", file=sys.stderr)
    print(f"Checked {len(pages)} HTML pages and {checked} local references; {len(errors)} errors.")
    return 1 if errors else 0


if __name__ == "__main__":
    sys.exit(main())
