"""Live browsercheck: publieke site + beheer, op desktop- en mobielformaat, in
Chromium (Chrome/Edge) én WebKit (Safari; mobiel als iPhone met touch).

Gebruik (met de site draaiend op LIVE_CHECK_URL, standaard http://localhost:8000):

    python scripts/live-check.py

Vereist: `pip install playwright` en `playwright install chromium webkit`
(alleen Chromium: LIVE_CHECK_BROWSERS=chromium). Inloggen
gebeurt met het seeder-account (of LIVE_CHECK_EMAIL / LIVE_CHECK_PASSWORD).

Faalt (exit 1) bij: console-/CSP-fouten van onze eigen site, JavaScript-fouten,
horizontale overflow, een verkeerde statuscode of een kapotte kerninteractie.
Print "live check passed" alleen als alles klopt.
"""
import os
import sys
import tempfile

from playwright.sync_api import sync_playwright

BASE = os.environ.get("LIVE_CHECK_URL", "http://localhost:8000").rstrip("/")
ADMIN = (os.environ.get("LIVE_CHECK_EMAIL", "admin@autobedrijfrijswijk.test"),
         os.environ.get("LIVE_CHECK_PASSWORD", "password"))
BROWSERS = os.environ.get("LIVE_CHECK_BROWSERS", "chromium,webkit").split(",")
problems = []


def watch(page, label):
    def on_console(msg):
        url = (msg.location or {}).get("url", "")
        text = msg.text
        # Bewust genegeerd (geen fout van ons): "report-only"-meldingen van
        # partner-iframes, en de 404 van de opzettelijk opgevraagde niet-bestaande pagina.
        if "report-only" in text or ("status of 404" in text and "bestaat-niet" in url):
            return
        if msg.type == "error" and (url == "" or url.startswith(BASE)):
            problems.append(f"[{label}] console: {text[:160]} ({url[:80]})")

    page.on("console", on_console)
    page.on("pageerror", lambda e: problems.append(f"[{label}] JS-fout: {str(e)[:160]}"))


def check_page(page, label, path, expect_status=200):
    resp = page.goto(BASE + path, wait_until="networkidle")
    if resp is None or resp.status != expect_status:
        problems.append(f"[{label}] {path}: status {resp.status if resp else 'geen'} i.p.v. {expect_status}")
    overflow = page.evaluate("document.documentElement.scrollWidth - window.innerWidth")
    if overflow > 1:
        problems.append(f"[{label}] {path}: horizontale overflow van {overflow}px")


def expect(cond, label, what):
    if not cond:
        problems.append(f"[{label}] {what}")


def contexts(p, engine):
    """(label, context-opties) per formaat. WebKit-mobiel = een echte iPhone (touch, DPR 3, Safari-UA)."""
    mobile = dict(p.devices["iPhone 13"]) if engine == "webkit" else {"viewport": {"width": 375, "height": 812}}
    return [(f"{engine}-desktop", {"viewport": {"width": 1280, "height": 800}}), (f"{engine}-mobiel", mobile)]


with sync_playwright() as p:
  for engine in BROWSERS:
    browser = getattr(p, engine.strip()).launch()

    for name, options in contexts(p, engine.strip()):
        ctx = browser.new_context(**options)
        page = ctx.new_page()
        w = page.viewport_size["width"]
        watch(page, name)

        # --- Publiek ---
        # Hero-foto echt geladen (op een telefoon de staande uitsnede).
        check_page(page, name, "/")
        hero = page.eval_on_selector("section img[fetchpriority='high']", "i => ({ok: i.complete && i.naturalWidth > 0, src: i.currentSrc})")
        expect(hero["ok"], name, f"hero-foto laadt niet ({hero['src']})")
        if w < 768:
            expect("portrait" in hero["src"], name, f"telefoon krijgt niet de staande hero ({hero['src']})")

        for path in ["/", "/aanbod", "/diensten", "/over-ons", "/privacybeleid",
                     "/contact?onderwerp=financiering"]:
            check_page(page, name, path)
        check_page(page, name, "/aanbod/bestaat-niet-meer", expect_status=404)
        expect("bestaat niet" in page.content(), name, "404-pagina is niet de Nederlandse versie")

        # Kaart pas na klik (AVG).
        check_page(page, name, "/contact")
        expect(page.locator("iframe").count() == 0, name, "kaart laadt zonder klik")
        page.click("button:has-text('Kaart laden')")
        page.wait_for_timeout(400)
        expect(page.locator("iframe[src*='google.com/maps']").count() == 1, name, "kaart laadt niet na klik")

        check_page(page, name, "/financial-lease")
        expect(page.locator("iframe").count() == 0, name, "lease-widget laadt zonder klik")

        # Aanbod-filter (Alpine-component uit resources/js): zoeken verlaagt het
        # aantal zichtbare auto's, wissen herstelt het.
        page.goto(BASE + "/aanbod", wait_until="networkidle")
        visible = "() => [...document.querySelectorAll('[data-car]')].filter(c => c.style.display !== 'none').length"
        total = page.evaluate(visible)
        search = page.locator("input[type=search]:visible").first
        if w < 1024:  # op mobiel zit het zoekveld in de filterlade
            page.click("button:has-text('Filters')")
            page.wait_for_timeout(300)
            search = page.locator("input[type=search]:visible").first
        search.fill("porsche")
        page.wait_for_timeout(300)
        filtered = page.evaluate(visible)
        expect(0 < filtered < total, name, f"zoeken filtert niet ({total} → {filtered})")
        search.fill("")
        page.wait_for_timeout(300)
        expect(page.evaluate(visible) == total, name, "wissen herstelt het aanbod niet")

        # Detailpagina van de eerste auto uit het aanbod.
        page.goto(BASE + "/aanbod", wait_until="networkidle")
        detail = page.eval_on_selector("[data-car]", "a => new URL(a.href).pathname")
        check_page(page, name, detail)
        expect(page.eval_on_selector("img[fetchpriority='high']", "i => i.complete && i.naturalWidth > 0"),
               name, "hoofdfoto detailpagina laadt niet")

        # Lightbox: openen, bladeren, sluiten met Escape; pagina daarna weer bruikbaar.
        page.click("button[aria-label=\"Foto's schermvullend bekijken\"]")
        page.wait_for_timeout(400)
        expect(page.is_visible("[role=dialog]"), name, "lightbox opent niet")
        expect(page.evaluate("document.querySelector('main').inert"), name, "pagina achter de lightbox is niet inert")
        page.keyboard.press("ArrowRight")
        page.keyboard.press("Escape")
        page.wait_for_timeout(400)
        expect(not page.is_visible("[role=dialog]"), name, "lightbox sluit niet met Escape")
        # Touch-gebruikers hebben geen Escape: de sluitknop moet werken.
        page.click("button[aria-label=\"Foto's schermvullend bekijken\"]")
        page.wait_for_timeout(400)
        page.click("[role=dialog] button[aria-label*='luiten']")
        page.wait_for_timeout(400)
        expect(not page.is_visible("[role=dialog]"), name, "lightbox sluit niet met de sluitknop")
        expect(not page.evaluate("document.querySelector('main').inert"), name, "pagina blijft inert na sluiten")

        # Snelknop "Proefrit aanvragen" -> onderwerp proefrit, datumveld zichtbaar, focus in naamveld.
        page.click("a:has-text('Proefrit aanvragen')")
        page.wait_for_timeout(600)
        state = page.evaluate("""() => ({
            type: document.getElementById('lead-type').value,
            dateVisible: !!document.getElementById('lead-date').offsetParent,
            focus: document.activeElement && document.activeElement.id,
        })""")
        expect(state == {"type": "proefrit", "dateVisible": True, "focus": "lead-name"}, name, f"proefrit-snelknop: {state}")
        # Echt datumveld in de HTML (Safari op iPhone/Mac toont dan de eigen datumkiezer). Het
        # attribuut, niet .type: WebKit voor Windows kent zelf geen datumveld (echte Safari wel).
        expect(page.eval_on_selector("#lead-date", "e => e.getAttribute('type')") == "date", name, "datumveld is geen echt datumveld")

        # --- Beheer ---
        page.goto(BASE + "/login", wait_until="networkidle")
        page.fill("input[name=email]", ADMIN[0])
        page.fill("input[name=password]", ADMIN[1])
        # Expliciet op de navigatie wachten: in WebKit is "networkidle" direct na de klik
        # al waar vóórdat het formulier verstuurd is.
        with page.expect_navigation(wait_until="networkidle"):
            page.click("button[type=submit]")
        for path in ["/admin", "/admin/aanvragen", "/admin/aanvragen?tab=alle", "/profile"]:
            check_page(page, name, path)
            if page.evaluate("matchMedia('(pointer: coarse)').matches"):
                # iPhone: velden < 16px laten Safari inzoomen bij elke tik.
                tiny = page.evaluate("""[...document.querySelectorAll('input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=file]), select, textarea')]
                    .filter(e => e.getClientRects().length && parseFloat(getComputedStyle(e).fontSize) < 16).length""")
                expect(tiny == 0, name, f"{path}: {tiny} velden < 16px (Safari zoomt in)")
        expect("Wachtwoord wijzigen" in page.content(), name, "profiel is niet Nederlands")

        # Bewerkpagina: fotovolgorde-knoppen, en te grote foto geblokkeerd vóór versturen.
        page.goto(BASE + "/admin", wait_until="networkidle")
        edit = page.eval_on_selector("a[aria-label$='bewerken']", "a => new URL(a.href).pathname")
        check_page(page, name, edit)
        expect(page.locator("button[aria-label$='naar achteren']").count() > 0, name, "geen knoppen om fotovolgorde te wijzigen")
        big = os.path.join(tempfile.gettempdir(), "te-groot.jpg")
        with open(big, "wb") as f:
            f.write(b"\xff\xd8\xff" + os.urandom(13 * 1024 * 1024))
        page.set_input_files("input[name='images[]']", big)
        page.wait_for_timeout(300)
        warning = page.inner_text("#photo-upload [role=alert]") if page.is_visible("#photo-upload [role=alert]") else ""
        expect("te groot" in warning, name, f"geen waarschuwing bij te grote foto (kreeg: {warning!r})")
        page.click("button[type=submit]:has-text('Wijzigingen opslaan')")
        page.wait_for_timeout(500)
        expect(page.url.split("?")[0].rstrip("/") == (BASE + edit).rstrip("/"), name,
               f"formulier met te grote foto werd tóch verstuurd ({page.url})")

        ctx.close()

    browser.close()

if problems:
    print("\n".join(problems))
    sys.exit(1)
print("live check passed")
