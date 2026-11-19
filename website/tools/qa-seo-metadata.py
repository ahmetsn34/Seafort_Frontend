"""Test metadata, structured-data truthfulness and authenticated SEO editing in an isolated copy."""
import base64
import hashlib
import html
import http.cookiejar
import json
import os
from pathlib import Path
import re
import secrets
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

SOURCE = Path(__file__).resolve().parents[1]
PHP = os.environ.get("SEAFORT_PHP", r"C:\xampp\php\php.exe")


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def check(value, message):
    if not value:
        raise AssertionError(message)


def meta(body, key, attribute="name"):
    found = re.search(r'<meta\s+' + attribute + r'="' + re.escape(key) + r'"\s+content="([^"]*)"', body)
    return html.unescape(found.group(1)) if found else None


def title(body):
    return html.unescape(re.search(r"<title>(.*?)</title>", body, re.S).group(1))


def graph(body, headers):
    found = re.search(r'<script type="application/ld\+json">(.*?)</script>', body, re.S)
    check(found is not None, "Structured data missing")
    encoded = found.group(1)
    digest = base64.b64encode(hashlib.sha256(encoded.encode()).digest()).decode()
    check("'sha256-" + digest + "'" in headers.get("Content-Security-Policy", ""), "JSON-LD CSP hash mismatch")
    check("unsafe-inline" not in headers.get("Content-Security-Policy", ""), "CSP weakened")
    return json.loads(encoded)["@graph"]


with tempfile.TemporaryDirectory(prefix="seafort-seo-qa-") as temporary:
    root = Path(temporary)
    private, public = root / "seafort", root / "public_html"
    storage = private / "storage"
    storage.mkdir(parents=True)
    (root / "sessions").mkdir()
    (root / "tools").mkdir()
    shutil.copytree(SOURCE / "seafort/app", private / "app", ignore=shutil.ignore_patterns("config.php"))
    shutil.copytree(SOURCE / "seafort/seed", private / "seed")
    shutil.copytree(SOURCE / "public_html/admin", public / "admin")
    shutil.copy2(SOURCE / "public_html/index.php", public / "index.php")
    shutil.copy2(SOURCE / "tools/router.php", root / "tools/router.php")
    for asset in ["archive/marine-worksite.webp", "logo-dark.png"]:
        target = public / "assets" / asset
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(SOURCE / "public_html/assets" / asset, target)
    config = private / "app/config.php"

    def configure(indexable=True):
        origin = "https://seafortservice.com" if indexable else "https://preview.seafortservice.com"
        config.write_text("<?php return ['base_url'=>'" + origin + "','indexable'=>" + ("true" if indexable else "false") + ", 'company'=>'Seafort Denizcilik A.Ş.','email'=>'contact@seafortservice.com'];", encoding="utf-8")

    def write(name, data):
        (storage / name).write_text(json.dumps(data, ensure_ascii=False), encoding="utf-8")

    def snapshot():
        return {p.name: hashlib.sha256(p.read_bytes()).hexdigest() for p in storage.glob("*.json")}

    configure()
    article = {"id": "seo-article", "slug": "repair-planning", "title": "Repair planning", "excerpt": "Preparing a repair scope before work begins.", "body": "A fixture article for this isolated technical test.", "category": "Maintenance", "status": "published", "cover": "/assets/archive/marine-worksite.webp", "published_at": "2026-11-01T10:00:00+00:00", "updated_at": "2026-11-02T11:00:00+00:00"}
    write("posts.json", {"posts": [article, dict(article, id="draft-article", slug="draft-article", title="PRIVATE DRAFT ARTICLE", status="draft")]})
    write("projects.json", {"items": [{"id": "draft-case", "slug": "draft-case", "title": "PRIVATE CASE TITLE", "status": "draft"}]})
    write("settings.json", {"email": "contact@seafortservice.com"})
    write("enquiry-settings.json", {"smtp": {"password": "isolated-fixture"}})
    password = secrets.token_urlsafe(24)
    environment = dict(os.environ, SEAFORT_STORAGE_DIR=str(storage), SEO_QA_PASSWORD=password)
    fixture = "<?php file_put_contents(getenv('SEAFORT_STORAGE_DIR').'/admin.json',json_encode(['username'=>'seo-qa','password_hash'=>password_hash(getenv('SEO_QA_PASSWORD'),PASSWORD_DEFAULT),'auth_version'=>'isolated-seo']));"
    subprocess.run([PHP], input=fixture, env=environment, text=True, check=True, capture_output=True)
    protected = {name: (storage / name).read_bytes() for name in ["admin.json", "settings.json", "enquiry-settings.json", "posts.json", "projects.json"]}
    with socket.socket() as available:
        available.bind(("127.0.0.1", 0))
        port = available.getsockname()[1]
    base = f"http://127.0.0.1:{port}"
    browser = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())

    def request(path, fields=None, method=None, host=None):
        data = None if fields is None else urllib.parse.urlencode(fields).encode()
        req = urllib.request.Request(base + path, data=data, method=method, headers={} if host is None else {"Host": host})
        try:
            response = browser.open(req, timeout=8)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            return response.status, response.read().decode(), response.headers

    log = (root / "server.log").open("w", encoding="utf-8")
    server = subprocess.Popen([PHP, "-d", f'session.save_path="{(root / "sessions").as_posix()}"', "-S", f"127.0.0.1:{port}", "-t", str(public), str(root / "tools/router.php")], env=environment, stdout=log, stderr=log)
    try:
        for attempt in range(80):
            try:
                status, body, headers = request("/")
                break
            except OSError:
                time.sleep(0.05)
        check(status == 200, "Home failed: " + body[:500])
        check("Ship Repair" in title(body) and "Coatings" in title(body), "Home title lacks verified scope")
        check(meta(body, "description") and meta(body, "robots") == "index,follow,max-image-preview:large", "Production metadata missing")
        home_graph = graph(body, headers)
        organization = next(item for item in home_graph if item["@type"] == "Organization")
        check(organization["name"] == "Seafort Denizcilik A.Ş." and organization["email"] == "contact@seafortservice.com", "Organization identity wrong")
        check("address" not in organization and "telephone" not in organization and "aggregateRating" not in organization, "Unverified organization facts")
        check(not any("/share/" in url for url in organization.get("sameAs", [])), "Share URL misrepresented as corporate profile")
        check(meta(body, "og:image", "property") == "https://seafortservice.com/assets/archive/marine-worksite.webp", "Sharing image not absolute")
        check(meta(body, "twitter:card") == "summary_large_image", "Sharing card missing")
        _, forged, _ = request("/", host="untrusted.example")
        check('rel="canonical" href="https://seafortservice.com/"' in forged and "untrusted.example" not in forged, "Canonical origin reflects request host")
        titles = []
        for path in ["/about/", "/services/", "/maintenance-repair/", "/ship-supply/", "/marine-services/", "/marine-services-2/", "/careers/", "/get-a-quote/", "/sustainability/"]:
            status, document, doc_headers = request(path)
            check(status == 200 and meta(document, "description"), "Missing page metadata: " + path)
            titles.append(title(document))
            nodes = graph(document, doc_headers)
            if path == "/maintenance-repair/":
                service = next(n for n in nodes if n["@type"] == "Service")
                check(service["name"] == "Maintenance & Repair" and "rating" not in service, "Service schema invents facts")
        check(len(titles) == len(set(titles)), "Duplicate page titles")
        for path in ["/projects/", "/documents/", "/certificates/"]:
            status, document, doc_headers = request(path)
            check(status == 200 and meta(document, "robots") == "noindex,follow" and "application/ld+json" not in document, "Empty listing advertised to search: " + path)
        for path in ["/ege-derin/", "/projects/draft-case/", "/blog/draft-article/", "/not-a-page/"]:
            status, document, _ = request(path)
            check(status == 404 and "application/ld+json" not in document and "PRIVATE CASE TITLE" not in document and "PRIVATE DRAFT ARTICLE" not in document, "Draft/placeholder metadata leaked: " + path)
        status, document, doc_headers = request("/blog/repair-planning/")
        article_node = next(n for n in graph(document, doc_headers) if n["@type"] == "Article")
        check(article_node["datePublished"] == article["published_at"] and article_node["dateModified"] == article["updated_at"] and "author" not in article_node, "Article dates/author fabricated")
        _, filtered, _ = request("/blog/?q=repair")
        check(meta(filtered, "robots") == "noindex,follow" and 'rel="canonical" href="https://seafortservice.com/blog/"' in filtered, "Filtered results indexing/canonical wrong")
        before = snapshot()
        status, _, headers = request("/admin/seo.php")
        check(status == 303 and headers.get("Location") == "/admin/" and before == snapshot(), "SEO editor authentication missing")
        status, _, _ = request("/admin/seo.php", {"action": "save"})
        check(status == 303 and before == snapshot(), "Unauthenticated SEO write accepted")
        _, login, _ = request("/admin/")
        csrf = re.search(r'name="csrf" value="([a-f0-9]+)"', login).group(1)
        status, _, _ = request("/admin/", {"action": "login", "username": "seo-qa", "password": password, "csrf": csrf})
        check(status == 303, "Fixture login failed")
        before = snapshot()
        status, editor, _ = request("/admin/seo.php?route=%2Fmaintenance-repair%2F")
        check(status == 200 and before == snapshot() and "PRIVATE CASE TITLE" not in editor, "SEO GET mutation/private route leak")
        csrf = re.search(r'name="csrf" value="([a-f0-9]+)"', editor).group(1)
        revision = re.search(r'name="revision" value="([a-f0-9]+)"', editor).group(1)
        fields = {"action": "save", "route": "/maintenance-repair/", "site_name": "Seafort", "default_image": "/assets/archive/marine-worksite.webp", "default_image_alt": "Marine worksite", "title": "Marine steel repair", "description": "Steel renewal, protective coatings and piping work for vessels and offshore assets.", "image": "", "image_alt": "", "revision": revision, "csrf": csrf}
        for changed in [{"csrf": "invalid"}, {"route": "/admin/"}, {"route": "/projects/draft-case/"}, {"image": "https://untrusted.example/a.jpg"}, {"revision": "stale"}, {"title": "x" * 121}]:
            status, _, _ = request("/admin/seo.php", dict(fields, **changed))
            check(status == 400 and before == snapshot(), "Invalid SEO update mutated data: " + str(changed))
        status, _, _ = request("/admin/seo.php", method="PUT")
        check(status == 405 and before == snapshot(), "Unsupported method mutated SEO")
        status, _, _ = request("/admin/seo.php", fields)
        check(status == 303, "Valid SEO save failed")
        stored = json.loads((storage / "seo.json").read_text())
        check(stored["pages"]["/maintenance-repair/"]["title"] == fields["title"], "Override missing")
        _, document, doc_headers = request("/maintenance-repair/")
        check(title(document) == "Marine steel repair | Seafort" and "Maintenance &amp; Repair</h1>" in document, "SEO override changed visible copy or did not render")
        check(next(n for n in graph(document, doc_headers) if n["@type"] == "Service")["name"] == "Maintenance & Repair", "Service headline mirrors metadata override")
        after = snapshot()
        status, _, _ = request("/admin/seo.php", fields)
        check(status == 400 and after == snapshot(), "Stale SEO revision overwrote newer settings")
        check(all((storage / name).read_bytes() == original for name, original in protected.items()), "SEO changed account, SMTP or editorial content")
        # Real later archive pages keep their own canonical URL and title.
        write("posts.json", {"posts": [dict(article, id="post-" + str(i), slug="post-" + str(i), title="Article " + str(i)) for i in range(7)]})
        _, second_page, page_headers = request("/blog/?page=0002")
        check('rel="canonical" href="https://seafortservice.com/blog/?page=2"' in second_page and "Page 2" in title(second_page), "Real pagination canonical/title wrong")
        graph(second_page, page_headers)
        configure(False)
        _, preview_document, preview_headers = request("/maintenance-repair/")
        check(meta(preview_document, "robots") == "noindex,nofollow" and "preview.seafortservice.com/maintenance-repair/" in preview_document and "noindex" in preview_headers.get("X-Robots-Tag", ""), "Preview indexing protection regressed")
        script = "<?php require " + json.dumps(str(private / "app/bootstrap.php").replace("\\", "/")) + ";require SF_ROOT.'/app/seo.php';echo sf_seo_json(['text'=>'</script><script>alert(1)</script>']);echo '\\n';echo json_encode(sf_seo_date('2026-02-30T10:00:00+00:00'));"
        result = subprocess.run([PHP], input=script, env=environment, text=True, check=True, capture_output=True).stdout
        check("</script>" not in result and "null" in result, "JSON-LD escaping or date validation failed")
        print(json.dumps({"passed": True, "metadata_unique_and_content_backed": True, "absolute_social_images": True, "organization_service_article_breadcrumb_schema": True, "csp_exact_jsonld_hash": True, "no_fake_identity_dates_reviews": True, "no_draft_or_placeholder_schema": True, "empty_listing_noindex": True, "canonical_host_not_reflected": True, "real_pagination_canonical": True, "admin_auth_csrf_revision_and_readonly_get": True, "private_settings_only": True, "preview_noindex_preserved": True, "external_email_sent": False}))
    finally:
        server.terminate()
        server.wait(timeout=10)
        log.close()
