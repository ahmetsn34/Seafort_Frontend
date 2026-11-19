"""Exercise canonical routes, sitemap eligibility, legacy URLs and bot policy in isolation."""
import hashlib
import http.client
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.request
import xml.etree.ElementTree as ET

PHP = os.environ.get("SEAFORT_PHP", r"C:\xampp\php\php.exe")
SOURCE = Path(__file__).resolve().parents[1]


def check(condition, message):
    if not condition:
        raise AssertionError(message)


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


with tempfile.TemporaryDirectory(prefix="seafort-discovery-qa-") as temporary:
    root = Path(temporary)
    private, public = root / "seafort", root / "public_html"
    storage = private / "storage"
    storage.mkdir(parents=True)
    public.mkdir()
    (root / "tools").mkdir()
    shutil.copytree(SOURCE / "seafort/app", private / "app", ignore=shutil.ignore_patterns("config.php"))
    shutil.copytree(SOURCE / "seafort/seed", private / "seed")
    shutil.copy2(SOURCE / "public_html/index.php", public / "index.php")
    shutil.copy2(SOURCE / "public_html/robots.txt", public / "robots.txt")
    shutil.copy2(SOURCE / "tools/router.php", root / "tools/router.php")
    shutil.copytree(SOURCE / "public_html/assets/references", public / "assets/references")
    shutil.copytree(SOURCE / "public_html/assets/locales", public / "assets/locales")
    config = private / "app/config.php"

    def configure(indexable=True):
        origin = "https://seafortservice.com" if indexable else "https://preview.seafortservice.com"
        config.write_text("<?php return " + "['base_url'=>" + repr(origin) + ", 'indexable'=>" + str(indexable).lower() + ", 'company'=>'Seafort', 'email'=>'contact@seafortservice.com'];", encoding="utf-8")

    def store(name, value):
        (storage / name).write_text(json.dumps(value), encoding="utf-8")

    configure()
    posts = []
    for number in range(8):
        slug = "about" if number == 7 else f"published-{number}"
        posts.append({"id": str(number), "slug": slug, "title": f"Published article {number}", "excerpt": "A verified public fixture description.", "body": "A public fixture body long enough for this isolated test.", "category": "Technical", "status": "published", "cover": "/assets/references/legacy-01.png", "published_at": "2026-01-02T10:00:00+00:00", "updated_at": "2026-02-03T11:22:33+00:00"})
    posts.append(dict(posts[0], id="draft", slug="private-draft", status="draft"))
    store("posts.json", {"posts": posts})
    store("projects.json", {"items": []})
    store("references.json", {"logos": [], "quotes": []})
    store("documents.json", {"items": {}})
    store("pages.json", json.loads((private / "app/pages.json").read_text(encoding="utf-8")))
    environment = dict(os.environ, SEAFORT_STORAGE_DIR=str(storage))

    def cli(code):
        bootstrap = json.dumps(str(private / "app/bootstrap.php").replace("\\", "/"))
        discovery = json.dumps(str(private / "app/discovery.php").replace("\\", "/"))
        return subprocess.run([PHP], input="<?php require " + bootstrap + ";require " + discovery + ";" + code, env=environment, text=True, capture_output=True, check=True).stdout

    check(json.loads(cli("echo json_encode(sf_discovery_lastmod(['updated_at'=>'2026-02-30T10:00:00Z','published_at'=>'2026-01-02T10:00:00Z']));")) == "2026-01-02T10:00:00+00:00", "Invalid dates must not become fabricated lastmod values")
    check(json.loads(cli("echo json_encode(sf_discovery_lastmod([]));")) is None, "Undated static content must remain undated")

    with socket.socket() as available:
        available.bind(("127.0.0.1", 0))
        port = available.getsockname()[1]
    base = f"http://127.0.0.1:{port}"
    browser = urllib.request.build_opener(NoRedirect())

    def request(path, method="GET", data=None):
        try:
            response = browser.open(urllib.request.Request(base + path, data=data, method=method), timeout=8)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            return response.status, response.read().decode(errors="replace"), response.headers

    def sitemap():
        status, body, headers = request("/sitemap.xml")
        check(status == 200, "Sitemap must be available")
        tree = ET.fromstring(body)
        ns = {"s": "http://www.sitemaps.org/schemas/sitemap/0.9"}
        return {item.find("s:loc", ns).text: item.findtext("s:lastmod", default=None, namespaces=ns) for item in tree.findall("s:url", ns)}, headers

    def snapshot():
        return {str(path.relative_to(storage)): hashlib.sha256(path.read_bytes()).hexdigest() for path in storage.rglob("*") if path.is_file()}

    log = (root / "server.log").open("w", encoding="utf-8")
    server = subprocess.Popen([PHP, "-S", f"127.0.0.1:{port}", "-t", str(public), str(root / "tools/router.php")], env=environment, stdout=log, stderr=log)
    try:
        for _ in range(50):
            try:
                status, _, _ = request("/robots.txt")
                break
            except OSError:
                time.sleep(0.05)
        check(status == 200, "Isolated PHP server did not start")

        _, robots, headers = request("/robots.txt")
        check("Allow: /\n" in robots and "Disallow: /admin/" in robots and "Disallow: /seafort/" in robots and "Sitemap: https://seafortservice.com/sitemap.xml" in robots, "Production robots must allow public discovery and protect private paths")
        check("GPTBot" not in robots and "OAI-SearchBot" not in robots, "Wildcard search access must not invent a training preference or override private exclusions")

        aliases = json.loads(cli("echo json_encode(sf_discovery_aliases());"))
        for old, target in aliases.items():
            status, _, headers = request(old)
            check(status == 301 and headers.get("Location") == target, "Known legacy URL must have the exact permanent destination: " + old)
        for old, target in [("/ABOUT", "/about/"), ("/%61bout", "/about/"), ("/about///", "/about/"), ("/robots.txt/", "/robots.txt"), ("/sitemap.xml/", "/sitemap.xml"), ("/published-0/", "/blog/published-0/")]:
            status, _, headers = request(old + "?utm_source=fixture&new=1")
            check(status == 301 and headers.get("Location") == target + "?utm_source=fixture&new=1", "Canonical path redirects must retain functional query parameters: " + old)
        # An origin-form path beginning with // must not be parsed as a URL hostname.
        connection = http.client.HTTPConnection("127.0.0.1", port, timeout=8)
        connection.request("GET", "//about///")
        response = connection.getresponse()
        check(response.status == 301 and response.getheader("Location") == "/about/", "Repeated leading slashes must keep the actual route")
        response.read()
        connection.close()

        check(request("/about/")[0] == 200, "A post with slug about must not shadow the company page")
        for path in ["/unknown-page", "/private-draft/", "/blog/private-draft/", "/ege-derin/", "/%0d%0aInjected", "/seafort/storage/pages.json"]:
            status, _, headers = request(path)
            check(status == 404 and "Location" not in headers, "Unknown, private, malformed and unapproved URLs must not receive unrelated redirects: " + path)
        status, _, headers = request("/careers?new=1", method="POST", data=b"csrf=fixture")
        check(status == 308 and headers.get("Location") == "/careers/?new=1", "POST canonicalization must preserve the method")
        before = snapshot()
        check(request("/careers/?new=1", method="POST", data=b"csrf=fixture")[0] == 405 and before == snapshot(), "Production email fallback must remain non-writing after canonicalization")

        media = json.loads((private / "app/legacy-media.json").read_text(encoding="utf-8"))
        check(len(media) == 33, "Only audited media aliases should be included")
        for old, target in media.items():
            status, _, headers = request(old)
            check(status == 301 and headers.get("Location") == target and request(target)[0] == 200, "Every exact audited legacy logo must redirect to its available replacement")

        urls, _ = sitemap()
        origin = "https://seafortservice.com"
        for hidden in ["/projects/", "/documents/", "/certificates/", "/references/", "/ege-derin/", "/contact/", "/blog/private-draft/"]:
            check(origin + hidden not in urls, "Thin, aliased, draft or unapproved pages must stay out of sitemap: " + hidden)
        check(urls[origin + "/"] is None and urls[origin + "/about/"] is None, "Static pages must not get fabricated freshness")
        check(urls[origin + "/blog/published-0/"] == "2026-02-03T11:22:33+00:00", "Article lastmod must be the actual stored content date")
        for url in urls:
            check(request(url.removeprefix(origin))[0] == 200, "Every sitemap URL must resolve without a redirect: " + url)
        paths = json.loads(cli("$_GET=['page'=>'0002'];echo json_encode(sf_discovery_canonical_path('/blog/'));"))
        check(paths == "/blog/?page=2", "Valid second archive pages need their own canonical")
        check(json.loads(cli("$_GET=['page'=>'2','q'=>'fixture'];echo json_encode(sf_discovery_canonical_path('/blog/'));")) == "/blog/", "Filtered archive canonical must remain clean")

        published_project = {"id": "public", "slug": "published-case", "title": "Published case", "summary": "Approved legacy project scope.", "body": "A published legacy project fixture with no private evidence.", "status": "published", "cover": "/assets/references/legacy-01.png", "updated_at": "2026-04-05T06:07:08Z"}
        store("projects.json", {"items": [published_project, dict(published_project, id="draft", slug="draft-case", status="draft")]})
        urls, _ = sitemap()
        check(origin + "/projects/" in urls and urls[origin + "/projects/published-case/"] == "2026-04-05T06:07:08+00:00", "Published project content must become discoverable using its real date")
        check(origin + "/projects/draft-case/" not in urls, "Project drafts must stay excluded")
        store("references.json", {"logos": [{"status": "published", "permission": True, "image": "/assets/missing.png"}], "quotes": []})
        check(origin + "/references/" not in sitemap()[0], "Missing logo files must not make an empty reference directory indexable")
        store("references.json", {"logos": [{"status": "published", "permission": True, "image": "/assets/references/legacy-01.png"}], "quotes": []})
        check(origin + "/references/" in sitemap()[0], "Real available approved reference content must become discoverable")
        status, _, headers = request("/translations/?lang=tr")
        check(status == 200 and "noindex" in headers.get("X-Robots-Tag", ""), "Translation data is not an indexable localized page")

        configure(False)
        _, robots, headers = request("/robots.txt")
        check(robots == "User-agent: *\nDisallow: /\n" and "noindex" in headers.get("X-Robots-Tag", ""), "Preview robots must block all bots even with a production static robots file present")
        urls, headers = sitemap()
        check(all(url.startswith("https://preview.seafortservice.com/") for url in urls) and "noindex" in headers.get("X-Robots-Tag", ""), "Preview sitemap must never advertise production URLs or lose noindex")
        check("https://preview.seafortservice.com/ege-derin/" not in urls and request("/ege-derin/")[0] == 200, "Preview placeholder can be reviewed but never advertised in discovery")
        check('id="enquiry-form"' in request("/careers/")[1], "Preview CV form must remain available")
        print("PASS: canonical paths and method-preserving redirects; exact legacy URL/media continuity; truthful eligible sitemap; production/preview robots isolation; filters/pagination; unchanged production email fallback and preview forms. No external email sent.")
    finally:
        server.terminate()
        server.wait(timeout=5)
        log.close()
