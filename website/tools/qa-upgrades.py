"""Exercise the preview upgrade through HTTP using an isolated temporary installation."""
import hashlib
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

PHP = os.environ.get("SEAFORT_PHP", r"C:\xampp\php\php.exe")
SOURCE = Path(__file__).resolve().parents[1]


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def check(condition, message):
    if not condition:
        raise AssertionError(message)


with tempfile.TemporaryDirectory(prefix="seafort-upgrade-qa-") as temporary:
    root = Path(temporary)
    private = root / "seafort"
    public = root / "public_html"
    private.mkdir()
    (public / "admin").mkdir(parents=True)
    storage = private / "storage"
    storage.mkdir()
    (root / "sessions").mkdir()
    shutil.copytree(SOURCE / "seafort/app", private / "app", ignore=shutil.ignore_patterns("config.php"))
    shutil.copytree(SOURCE / "seafort/seed", private / "seed")
    shutil.copytree(SOURCE / "seafort/bin", private / "bin")
    for name in ["index.php", "upgrades.php"]:
        shutil.copy2(SOURCE / "public_html/admin" / name, public / "admin" / name)
    config = private / "app/config.php"
    config.write_text("<?php return ['indexable'=>false];", encoding="utf-8")
    pages = json.loads((private / "app/pages.json").read_text(encoding="utf-8"))
    old_about = None
    for page in pages["pages"]:
        if page["slug"] == "about":
            page["intro"] = "OWNER ORIGINAL ABOUT"
            old_about = dict(page)
        if page["slug"] == "careers":
            page["sections"][0]["body"] = "Find Human Resources in our team directory for recruitment enquiries. For a general question, use the contact address shown on this page."
        if page["slug"] == "get-a-quote":
            page["sections"][0]["body"] = "OWNER CUSTOM QUOTE COPY"
    (storage / "pages.json").write_text(json.dumps(pages), encoding="utf-8")
    seed = json.loads((private / "seed/references.json").read_text(encoding="utf-8"))
    own_logo = dict(seed["logos"][0], name="OWNER LOGO NAME")
    references = {"logos": [own_logo], "quotes": [{"id": "owner-quote", "quote": "OWNER QUOTE"}], "settings": {"page_title": "OWNER REFERENCE TITLE"}}
    (storage / "references.json").write_text(json.dumps(references), encoding="utf-8")
    (storage / "settings.json").write_text('{"email":"owner@example.test"}', encoding="utf-8")
    (storage / "enquiry-settings.json").write_text('{"smtp":{"password":"isolated-fixture"}}', encoding="utf-8")
    password = secrets.token_urlsafe(24)
    environment = dict(os.environ, SEAFORT_STORAGE_DIR=str(storage), UPGRADE_QA_PASSWORD=password)
    setup = "<?php $p=getenv('SEAFORT_STORAGE_DIR');file_put_contents($p.'/admin.json',json_encode(['username'=>'upgrade-qa','password_hash'=>password_hash(getenv('UPGRADE_QA_PASSWORD'),PASSWORD_DEFAULT),'auth_version'=>'isolated-upgrade-qa']));"
    subprocess.run([PHP], input=setup, text=True, env=environment, check=True, capture_output=True)

    def snapshot():
        return {p.name: hashlib.sha256(p.read_bytes()).hexdigest() for p in storage.glob("*.json")}

    protected = {name: (storage / name).read_bytes() for name in ["admin.json", "settings.json", "enquiry-settings.json"]}
    with socket.socket() as available:
        available.bind(("127.0.0.1", 0))
        port = available.getsockname()[1]
    base = f"http://127.0.0.1:{port}"
    cookies = http.cookiejar.CookieJar()
    browser = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies), NoRedirect())

    def request(path, data=None, method=None):
        encoded = None if data is None else urllib.parse.urlencode(data).encode()
        req = urllib.request.Request(base + path, data=encoded, method=method)
        try:
            response = browser.open(req, timeout=5)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            return response.status, response.read().decode(), response.headers

    log = (root / "server.log").open("w", encoding="utf-8")
    server = subprocess.Popen([PHP, "-d", f'session.save_path="{(root / "sessions").as_posix()}"', "-S", f"127.0.0.1:{port}", "-t", str(public)], env=environment, stdout=log, stderr=log)
    try:
        for attempt in range(50):
            try:
                status, _, headers = request("/admin/upgrades.php")
                break
            except OSError:
                time.sleep(0.05)
        check(status == 303 and headers.get("Location") == "/admin/", "Unauthenticated GET must redirect")
        before = snapshot()
        status, _, _ = request("/admin/upgrades.php", {"action": "apply_preview_release", "confirm_upgrade": "1"})
        check(status == 303 and before == snapshot(), "Unauthenticated POST must not mutate content")
        _, login, _ = request("/admin/")
        token = re.search(r'name="csrf" value="([a-f0-9]+)"', login).group(1)
        status, login_result, _ = request("/admin/", {"action": "login", "username": "upgrade-qa", "password": password, "csrf": token})
        check(status == 303, (root / "server.log").read_text(encoding="utf-8") + " Fixture login failed: " + str(status) + " " + str(re.findall(r'<div class="alert[^>]+>(.*?)</div>', login_result)))
        before = snapshot()
        status, page, _ = request("/admin/upgrades.php")
        check(status == 200 and before == snapshot(), "Authenticated GET must be read-only")
        token = re.search(r'name="csrf" value="([a-f0-9]+)"', page).group(1)
        for fields in [
            {"action": "apply_preview_release", "confirm_upgrade": "1"},
            {"action": "apply_preview_release", "confirm_upgrade": "1", "csrf": "invalid"},
            {"action": "apply_preview_release", "csrf": token},
            {"action": "arbitrary", "confirm_upgrade": "1", "csrf": token},
        ]:
            status, _, _ = request("/admin/upgrades.php", fields)
            check(status == 400 and before == snapshot(), "Invalid CSRF/action/confirmation must not mutate content")
        status, _, _ = request("/admin/upgrades.php", method="PUT")
        check(status == 405 and before == snapshot(), "Unsupported methods must not mutate content")
        config.write_text("<?php return ['indexable'=>true];", encoding="utf-8")
        status, _, _ = request("/admin/upgrades.php", {"action": "apply_preview_release", "confirm_upgrade": "1", "csrf": token})
        check(status == 404 and before == snapshot(), "Indexable installation must reject upgrades")
        config.write_text("<?php return ['indexable'=>false];", encoding="utf-8")
        status, _, _ = request("/admin/upgrades.php", {"action": "apply_preview_release", "confirm_upgrade": "1", "csrf": token})
        check(status == 303, "Valid upgrade failed")
        status, page, _ = request("/admin/upgrades.php")
        check(status == 200 and "Önizleme güncellendi" in page, "Completion notice missing")
        updated = json.loads((storage / "pages.json").read_text(encoding="utf-8"))
        about = next(p for p in updated["pages"] if p["slug"] == "about")
        check(about == json.loads((private / "app/about-layout-defaults.json").read_text(encoding="utf-8")), "About update missing")
        check(json.loads((storage / "about-before-20261117.json").read_text(encoding="utf-8")) == old_about, "Original About backup missing")
        quote = next(p for p in updated["pages"] if p["slug"] == "get-a-quote")
        check(quote["sections"][0]["body"] == "OWNER CUSTOM QUOTE COPY", "Owner page edit overwritten")
        refs = json.loads((storage / "references.json").read_text(encoding="utf-8"))
        check(len(refs["logos"]) == 33 and refs["logos"][0] == own_logo, "Missing/duplicated logos or owner edit overwritten")
        check(refs["quotes"] == references["quotes"] and refs["settings"]["page_title"] == "OWNER REFERENCE TITLE", "Owner reference changes overwritten")
        backup = json.loads((storage / "upgrade-before-20261117.json").read_text(encoding="utf-8"))
        check(set(backup["files"]) == {"pages.json", "projects.json", "references.json", "design-20261117.json"}, "Backup scope wrong")
        check(all((storage / name).read_bytes() == data for name, data in protected.items()), "Account/settings/SMTP changed")
        # After applying the release, owner About changes must survive a repeat.
        about["intro"] = "OWNER AFTER UPGRADE"
        (storage / "pages.json").write_text(json.dumps(updated), encoding="utf-8")
        before = snapshot()
        token = re.search(r'name="csrf" value="([a-f0-9]+)"', page).group(1)
        status, _, _ = request("/admin/upgrades.php", {"action": "apply_preview_release", "confirm_upgrade": "1", "csrf": token})
        # First repeat normalises JSON formatting, so compare decoded values rather than bytes for pages.
        check(status == 303, "Repeat upgrade failed")
        after = snapshot()
        check(all(before[name] == digest for name, digest in after.items() if name != "pages.json"), "Repeat modified another content record")
        final_pages = json.loads((storage / "pages.json").read_text(encoding="utf-8"))
        check(final_pages == updated, "Repeat overwrote owner About changes")
        before = snapshot()
        for command in ["upgrade-preview.php", "upgrade-design.php"]:
            subprocess.run([PHP, str(private / "bin" / command)], env=environment, check=True, capture_output=True)
        check(before == snapshot(), "CLI wrappers no longer idempotent")
        check(all((storage / name).read_bytes() == data for name, data in protected.items()), "Protected data changed")
        print(json.dumps({"passed": True, "auth_required": True, "csrf_required": True, "get_read_only": True, "preview_only": True, "private_backups": True, "owner_edits_preserved": True, "idempotent": True, "cli_wrappers_passed": True, "external_mail_sent": False}))
    finally:
        server.terminate()
        server.wait(timeout=10)
        log.close()
