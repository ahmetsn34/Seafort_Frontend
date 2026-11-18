"""Exercise production email fallback and retained preview forms in isolated storage."""
import hashlib
import http.cookiejar
import json
import os
from pathlib import Path
import re
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


def check(condition, message):
    if not condition:
        raise AssertionError(message)


with tempfile.TemporaryDirectory(prefix="seafort-mail-fallback-qa-") as temporary:
    root = Path(temporary)
    private, public = root / "seafort", root / "public_html"
    storage = private / "storage"
    storage.mkdir(parents=True)
    public.mkdir()
    (root / "tools").mkdir()
    (root / "sessions").mkdir()
    shutil.copytree(SOURCE / "seafort/app", private / "app", ignore=shutil.ignore_patterns("config.php"))
    shutil.copytree(SOURCE / "seafort/seed", private / "seed")
    shutil.copy2(SOURCE / "public_html/index.php", public / "index.php")
    shutil.copy2(SOURCE / "tools/router.php", root / "tools/router.php")
    config = private / "app/config.php"

    def configure(value="true"):
        config.write_text("<?php return ['base_url'=>'https://seafortservice.com','company'=>'Seafort','email'=>'contact@seafortservice.com','indexable'=>" + value + "];", encoding="utf-8")

    def cli(code):
        environment = dict(os.environ, SEAFORT_STORAGE_DIR=str(storage))
        bootstrap = json.dumps(str(private / "app/bootstrap.php").replace("\\", "/"))
        return subprocess.run([PHP], input="<?php require " + bootstrap + ";" + code, env=environment, text=True, check=True, capture_output=True).stdout

    configure("false")
    defaults = json.loads(cli("echo json_encode(sf_enquiry_defaults());"))
    settings = json.loads(json.dumps(defaults))

    def save_settings():
        (storage / "enquiry-settings.json").write_text(json.dumps(settings), encoding="utf-8")

    pages = json.loads((private / "app/pages.json").read_text(encoding="utf-8"))
    for page in pages["pages"]:
        if page["slug"] == "careers":
            page["sections"][0]["body"] = "OWNER CAREER INTRODUCTION"
    (storage / "pages.json").write_text(json.dumps(pages), encoding="utf-8")
    save_settings()
    with socket.socket() as available:
        available.bind(("127.0.0.1", 0))
        port = available.getsockname()[1]
    base = f"http://127.0.0.1:{port}"
    browser = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def request(path, fields=None, attachment=False):
        headers = {}
        data = None
        if fields is not None:
            if attachment:
                boundary = "SeafortFallbackQaBoundary"
                parts = []
                for key, value in fields.items():
                    parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="attachment"; filename="fixture.pdf"\r\nContent-Type: application/pdf\r\n\r\n'.encode() + b'%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\r\n')
                parts.append(f"--{boundary}--\r\n".encode())
                data = b"".join(parts)
                headers["Content-Type"] = "multipart/form-data; boundary=" + boundary
            else:
                data = urllib.parse.urlencode(fields).encode()
                headers["Content-Type"] = "application/x-www-form-urlencoded"
        try:
            response = browser.open(urllib.request.Request(base + path, data=data, headers=headers), timeout=8)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            return response.status, response.read().decode(), response.headers

    def contact(html):
        return html.split('id="contact-details"', 1)[1].split("</section>", 1)[0]

    def snapshot():
        return {str(p.relative_to(storage)): hashlib.sha256(p.read_bytes()).hexdigest() for p in storage.rglob("*") if p.is_file()}

    log = (root / "server.log").open("w", encoding="utf-8")
    environment = dict(os.environ, SEAFORT_STORAGE_DIR=str(storage))
    server = subprocess.Popen([PHP, "-d", f'session.save_path="{(root / "sessions").as_posix()}"', "-S", f"127.0.0.1:{port}", "-t", str(public), str(root / "tools/router.php")], env=environment, stdout=log, stderr=log)
    try:
        for attempt in range(50):
            try:
                status, quote, _ = request("/get-a-quote/")
                break
            except OSError:
                time.sleep(0.05)
        check(status == 200 and 'id="enquiry-form"' in quote and "Preview mode." in quote, "Preview must retain its test form")
        csrf = re.search(r'name="csrf" value="([a-f0-9]+)"', quote).group(1)
        nonce = re.search(r'name="submission_token" value="([a-f0-9]+)"', quote).group(1)
        fields = {"csrf": csrf, "submission_token": nonce, "name": "Isolated QA", "email": "qa@example.test", "subject": "Fixture", "message": "Isolated preview test", "consent": "1", "website": ""}
        status, response, _ = request("/get-a-quote/", fields, attachment=True)
        check(status == 200 and "Preview submission saved." in response and "No email has been sent." in response, "Preview submission must still save privately without sending")
        check(len(json.loads((storage / "enquiries.json").read_text())["items"]) == 1, "Preview inbox must contain one record")
        check(len(list((storage / "enquiry-files").glob("*.pdf"))) == 1, "Preview PDF must be stored privately")

        configure("true")
        before = snapshot()
        for path, recipient, label in [("/get-a-quote/", "contact@seafortservice.com", "Email your project details"), ("/careers/", "careers@seafortservice.com", "Email your CV")]:
            status, html, headers = request(path)
            panel = contact(html)
            check(status == 200 and label in panel and 'mailto:' + urllib.parse.quote(recipient, safe="") in panel, "Production fallback must use the right email link")
            check('id="enquiry-form"' not in panel and 'type="file"' not in panel and "Preview mode." not in panel and "Thank you for getting in touch." not in panel, "Production fallback must not render a form or success")
            check("Set-Cookie" not in headers, "Fallback GET must not start a form session")
            check("Use the application form on this page" not in panel and "Use the project enquiry form on this page" not in panel, "Known seeded form directions must adapt to email")
            if path == "/careers/":
                check("OWNER CAREER INTRODUCTION" in panel, "Owner-written copy must survive")
            status, html, headers = request(path, fields, attachment=True)
            check(status == 405 and headers.get("Allow") == "GET, HEAD", "Crafted fallback POST must be rejected")
            check("Preview submission saved." not in html and "Thank you for getting in touch." not in html and not re.search(r'Reference <strong', html), "Old nonce success must not replay in production")
            check(before == snapshot(), "Fallback GET/POST must not mutate inbox, attachments, rate limits or other storage")

        complete_smtp = {"host": "mail.example.test", "username": "fixture", "password": "fixture-not-real", "from_email": "sender@example.test"}
        settings["mode"] = "live"
        settings["smtp"].update(complete_smtp)
        for missing in complete_smtp:
            settings["smtp"].update(complete_smtp)
            settings["smtp"][missing] = ""
            save_settings()
            _, html, _ = request("/get-a-quote/")
            check('id="enquiry-form"' not in contact(html) and "Email your project details" in html, "Each missing SMTP prerequisite must retain fallback")
        settings["smtp"].update(complete_smtp)
        save_settings()
        _, html, _ = request("/get-a-quote/")
        check('id="enquiry-form"' in contact(html) and "Preview mode." not in contact(html), "Configured live mode must retain the existing form")
        before = snapshot()
        status, html, _ = request("/get-a-quote/", {"csrf": "invalid"})
        check("Your form session has expired." in html and before == snapshot(), "Configured live form must retain CSRF rejection without outbound mail")
        settings["mode"] = "preview"
        settings["forms"]["quote"].update(recipient="custom?tag@example.test", email_title="OWNER EMAIL TITLE", email_intro="OWNER EMAIL INSTRUCTIONS", email_label="OWNER EMAIL BUTTON")
        settings["forms"]["career"]["recipient"] = "crew#team@example.test"
        save_settings()
        for path, recipient in [("/get-a-quote/", "custom?tag@example.test"), ("/careers/", "crew#team@example.test")]:
            _, html, _ = request(path)
            panel = contact(html)
            check('mailto:' + urllib.parse.quote(recipient, safe="") in panel and recipient.replace("&", "&amp;") in panel, "Custom recipients must remain distinct and URI-encoded")
        check("OWNER EMAIL TITLE" in request("/get-a-quote/")[1], "Owner fallback copy must render")
        for invalid in ["", "invalid-address", "injected@example.test\r\nBcc:other@example.test"]:
            settings["forms"]["quote"]["recipient"] = invalid
            save_settings()
            _, html, _ = request("/get-a-quote/")
            panel = contact(html)
            check('id="enquiry-form"' not in panel and "mailto:" not in panel and "currently unavailable" in panel, "Blank/invalid explicit recipient must remain unavailable")
        settings["forms"]["quote"]["recipient"] = "contact@seafortservice.com"

        for indexable, mode in [("false", "preview"), ("true", "preview"), ("true", "live")]:
            configure(indexable)
            settings["mode"] = mode
            settings["forms"]["quote"]["enabled"] = False
            save_settings()
            _, html, _ = request("/get-a-quote/")
            panel = contact(html)
            check("currently unavailable" in panel and 'id="enquiry-form"' not in panel and "mailto:" not in panel, "Disabled forms must not regain a CTA")
            before = snapshot()
            status, html, _ = request("/get-a-quote/", fields, attachment=True)
            check(status == 405 and before == snapshot() and "Preview submission saved." not in html, "Disabled POST must not mutate data or replay success")

        for literal, expected in [("true", True), ("false", False), ("null", False), ("1", False), ('"true"', False)]:
            configure(literal)
            settings["mode"] = "preview"
            save_settings()
            check(json.loads(cli("echo json_encode(sf_enquiry_email_only(sf_enquiry_settings()));")) is expected, "Production detection must require boolean true")
        configure("false")
        settings["mode"] = "preview"
        settings["forms"]["quote"]["enabled"] = True
        save_settings()
        saved = json.loads(cli("$s=sf_enquiry_settings();$i=$s;foreach(['quote','career'] as $k){$i['forms'][$k]['enabled']='1';$i['forms'][$k]['attachments']='1';foreach(['email_title','email_intro','email_label'] as $f)unset($i['forms'][$k][$f]);}sf_enquiry_save_settings($i,sf_enquiry_settings_revision());echo json_encode(sf_enquiry_settings());"))
        check(saved["forms"]["quote"]["email_title"] == "OWNER EMAIL TITLE" and saved["forms"]["quote"]["email_label"] == "OWNER EMAIL BUTTON", "Old loaded admin forms must preserve saved optional email copy")
        print("PASS: production email links, URI-safe custom recipients, per-form disabled/blank states, preview PDF/inbox, no cached-success replay or fallback storage writes, retained configured-live form/CSRF, and admin-copy preservation. No external email sent.")
    finally:
        server.terminate()
        server.wait(timeout=5)
        log.close()
