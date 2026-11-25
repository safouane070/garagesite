"""Playwright-regressies voor merklinks, smalle filters, galerij en diensten.

Draai met een gevulde lokale voorraad en draaiende server:
    python scripts/review-check.py
REVIEW_URL wijzigt het basisadres. REVIEW_BROWSERS=chromium beperkt de browsers.
Er worden geen aanvragen verstuurd of beheerwijzigingen opgeslagen.
"""
import json
import os
import re
import tempfile
from pathlib import Path

from playwright.sync_api import expect, sync_playwright

BASE = os.environ.get('REVIEW_URL', 'http://localhost:8000').rstrip('/')
OUT = Path(tempfile.mkdtemp(prefix='garagesite-review-fixes-'))
print(f'OUTPUT: {OUT}', flush=True)
results = []
errors = []


def record(name, detail=None):
    results.append({'check': name, 'detail': detail})
    print(json.dumps(results[-1], ensure_ascii=False), flush=True)
    (OUT / 'results.json').write_text(json.dumps(results, ensure_ascii=False, indent=2), encoding='utf-8')


with sync_playwright() as p:
    for engine in os.environ.get('REVIEW_BROWSERS', 'chromium,webkit').split(','):
        browser = getattr(p, engine).launch(headless=True)
        for width, height in [(320, 740), (390, 844), (768, 1024), (1440, 900)]:
            if os.environ.get('REVIEW_WIDTHS') and str(width) not in os.environ['REVIEW_WIDTHS'].split(','):
                continue
            label = f'{engine}-{width}'
            ctx = browser.new_context(viewport={'width': width, 'height': height}, locale='nl-NL',
                                      is_mobile=width < 768, has_touch=width < 768)
            page = ctx.new_page()
            page.on('pageerror', lambda e: errors.append(str(e)))
            page.goto(BASE + '/volkswagen-specialist', wait_until='networkidle')
            page.get_by_role('link', name='CUPRA Bekijken', exact=True).click()
            page.wait_for_url(re.compile(r'/aanbod\?brand='))
            page.wait_for_load_state('networkidle')
            brand = page.locator('#lade-flt-brand' if width < 1024 else '#zijbalk-flt-brand')
            if width < 1024:
                page.get_by_role('button', name=re.compile(r'^Filters(?: \d+)?$')).click()
            expect(brand).to_have_value('Cupra')
            expected = page.locator('[data-car][data-brand="Cupra"]').count()
            assert expected > 0, 'Deze regressiecontrole heeft Cupra-voorraad nodig.'
            if width < 1024:
                page.get_by_role('button', name='Filters sluiten', exact=True).click()
            expect(page.locator('[data-car]:visible')).to_have_count(min(expected, 24))
            record(label + ' CUPRA link and selected brand', expected)
            page.screenshot(path=str(OUT / f'{label}-catalogue.png'))

            if width < 1024:
                page.goto(BASE + '/aanbod', wait_until='networkidle')
                expected = page.locator('[data-car][data-brand="Porsche"]').count()
                assert expected > 0, 'Deze regressiecontrole heeft Porsche-voorraad nodig.'
                page.get_by_role('button', name=re.compile(r'^Filters(?: \d+)?$')).click()
                search = page.get_by_role('searchbox', name='Zoeken op merk of model')
                expect(search).to_be_visible()
                search.fill('Porsche')
                apply = page.get_by_role('button', name=re.compile(rf'^Toon {expected} auto'))
                expect(apply).to_be_visible()
                apply.click()
                expect(search).to_be_hidden()
                expect(page.locator('[data-car]:visible')).to_have_count(expected)
                record(label + ' filters applied with reachable button', expected)
                page.get_by_role('button', name=re.compile(r'^Filters(?: \d+)?$')).click()
                expect(search).to_be_visible()
                page.screenshot(path=str(OUT / f'{label}-filters.png'))
                page.get_by_role('button', name='Filters sluiten', exact=True).click()
                expect(search).to_be_hidden()
                page.keyboard.press('Tab')
                record(label + ' labelled filter close')

            await_link = page.locator('[data-car]:visible').first
            await_link.click()
            expect(page.get_by_role('button', name="Vergroot foto's", exact=True)).to_be_visible()
            trigger = page.get_by_role('button', name="Vergroot foto's", exact=True)
            trigger.click()
            dialog = page.get_by_role('dialog')
            expect(dialog).to_be_visible()
            assert dialog.evaluate("e => e.matches(':modal')")
            expect(dialog.get_by_role('button', name='Sluiten', exact=True)).to_be_focused()
            for key in ['Tab'] * 12 + ['Shift+Tab'] * 12:
                page.keyboard.press(key)
                assert page.evaluate('''() => document.activeElement === document.body ||
                    !!document.activeElement.closest('dialog[open]')'''), 'Focus ontsnapt naar een achtergrondknop.'
            page.keyboard.press('ArrowRight')
            expect(dialog.locator('img')).to_have_attribute('alt', re.compile(r'foto 2$'))
            page.keyboard.press('Escape')
            expect(dialog).to_be_hidden()
            expect(trigger).to_be_focused()
            trigger.click()
            expect(dialog).to_be_visible()
            dialog.get_by_role('button', name='Sluiten', exact=True).click()
            expect(dialog).to_be_hidden()
            expect(trigger).to_be_focused()
            record(label + ' native gallery, keyboard, Escape and focus return')
            page.screenshot(path=str(OUT / f'{label}-detail.png'))
            assert page.evaluate('document.documentElement.scrollWidth <= innerWidth + 1')

            page.goto(BASE + '/', wait_until='networkidle')
            page.screenshot(path=str(OUT / f'{label}-home.png'))
            header = page.locator('header')
            record(label + ' header background', header.evaluate('e => getComputedStyle(e).backgroundColor'))
            if width < 640:
                multiline = page.locator('[data-car] h3 + p').evaluate_all('''es => es.some(e =>
                    e.getBoundingClientRect().height > parseFloat(getComputedStyle(e).lineHeight) + 1)''')
                assert multiline, 'Lange uitvoeringen moeten op mobiel op twee regels kunnen staan.'
                record(label + ' two-line mobile variants')

            for link_name, subject, heading in [
                (r'^Inkoop ', 'inkoop', 'Verkoop je auto'),
                (r'^Aankoop ', 'aankoop', 'Bespreek aankoopbegeleiding'),
                (r'^Zoekopdracht ', 'zoekopdracht', 'Plaats een zoekopdracht'),
            ]:
                page.goto(BASE + '/', wait_until='networkidle')
                page.get_by_role('link', name=re.compile(link_name)).click()
                expect(page.get_by_role('heading', name=heading, exact=True)).to_be_visible()
                expect(page.get_by_label('Onderwerp', exact=True)).to_have_value(subject)
                assert page.url.endswith('#contact')
                record(label + ' service route ' + subject)
                if subject == 'inkoop':
                    page.screenshot(path=str(OUT / f'{label}-service.png'))
            ctx.close()
        browser.close()

assert not errors, errors
print(f'PASS: {len(results)} controles; screenshots en meetgegevens: {OUT}', flush=True)
