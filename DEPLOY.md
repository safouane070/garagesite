# Livegang — Autobedrijf Rijswijk (Laravel 13)

Doorloop dit van boven naar beneden. De volgorde is bewust: eerst een **testadres**, dan pas het
echte domein omzetten, met de oude WordPress-site als terugvaloptie.

## De uitgangssituatie (gemeten op 2026-09-23)
| Wat | Waar |
|---|---|
| Domein + DNS | **TransIP** (`ns0/ns1/ns2.transip.*`) |
| Huidige site (WordPress) | TransIP-webhosting, `85.10.159.104` + IPv6 `2a01:7c8:f0:1091:0:1:42f2:3b17` |
| `www.` | CNAME naar het hoofddomein (gaat vanzelf mee) |
| E-mail (ontvangen) | **Google Workspace** (MX `aspmx.l.google.com`) |
| SPF | `v=spf1 include:_spf.transip.email include:_spf.google.com ~all` — Google mag al versturen |
| DKIM | alleen TransIP (`transip-A/B/C._domainkey`); **Google-DKIM ontbreekt** |
| DMARC | `p=none` (alleen meten) |
| Bedrijf | BS Rijswijk Automotive B.V. · KvK 95760733 (BOVAG-register) · btw NL867282368B01 (EU VIES) |

## 0. Beslissingen vooraf (met de eigenaar)
- [ ] **Voorraadbron na de overstap.** De dagelijkse sync leest nu de WordPress-site. Kies:
  - **A — WordPress blijft, op een subdomein** (aanbevolen als de eigenaar daar al auto's invoert):
    maak `voorraad.autobedrijfrijswijk.nl` aan op het huidige TransIP-pakket, laat de WordPress-site
    daarop draaien (zet in WordPress het site-adres op het subdomein) en zet
    `DEALER_SITE_URL=https://voorraad.autobedrijfrijswijk.nl`. Blijft WordPress intern nog het oude
    adres gebruiken in z'n links, dan corrigeert de sync dat zelf. Zet het subdomein daarna op
    "niet indexeren" (WordPress → Instellingen → Lezen).
  - **B — alleen nog het beheer van deze site**: `DEALER_SITE_URL=` (leeg) — sync staat uit. De
    eigenaar voert auto's in via /admin (zie **Beheer → Hulp**).
- [ ] **Hosting.** De huidige TransIP-webhosting kan ook deze site draaien als het pakket PHP 8.3,
      cronjobs en een MySQL-database heeft (controlepaneel → Webhosting). Anders een ander pakket;
      de stappen hieronder zijn hetzelfde.
- [ ] Eigenaar bevestigt KvK/btw/juridische naam in de footer (afwijkend? `BRAND_KVK`, `BRAND_VAT`,
      `BRAND_LEGAL_NAME` in `.env`).

## 1. Code op de server
Makkelijkst zonder Node/Composer op de server: het **releasepakket** van GitHub.
GitHub → Actions → laatste groene run op `main` → Artifacts → `release` (zip met `vendor/` en de
gebouwde front-end). Uitpakken in de map van de site.

Met SSH + Composer + Node kan het ook zelf:
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

**Webroot:** zet die op de map `public/` als het controlepaneel dat toestaat. Kan dat niet (bij
TransIP-webhosting is de webroot vast `www/`), zet dan de hele projectmap in `www/`: de
meegeleverde `.htaccess` in de projectmap stuurt alles naar `public/`, zodat `.env` en de code
nooit op te vragen zijn. Controle na het uploaden: `https://<adres>/.env` moet een 404 geven.

## 2. `.env` op de server
Begin met `.env.example` en vul in:
- [ ] `APP_ENV=production` · `APP_DEBUG=false` · `APP_URL=https://<adres>` · `APP_KEY` (`php artisan key:generate`)
      — op het testadres `APP_ENV=staging`: dan staat de site dicht voor Google (robots + `noindex`).
- [ ] `DB_*` van de productie-database
- [ ] `QUEUE_CONNECTION=database` · `LOG_STACK=daily` · `LOG_LEVEL=warning`
- [ ] `DEALER_SITE_URL` volgens keuze 0
- [ ] `ERROR_ALERT_EMAIL=` wie een mail krijgt bij een fout of mislukte sync
- [ ] Optioneel `ADMIN_EMAIL` (standaard in productie: `info@autobedrijfrijswijk.nl`, zodat
      "wachtwoord vergeten" werkt) en `ADMIN_PASSWORD`

Vast ingebouwd in productie (niets voor nodig): cookies alleen over https, links via https, HSTS,
en `www.` → hoofddomein (301).

**Schijfruimte:** reken op ±1,2 GB voor de foto's (±2500 foto's in 4 maten). Dat groeit niet mee met
de verkopen: na 60 dagen verkocht blijft alleen de omslagfoto. 3 GB of meer is ruim voldoende.

**PHP-instellingen** (anders falen foto-uploads vanaf een telefoon):
- [ ] `upload_max_filesize` ≥ 16M, `post_max_size` ≥ 64M, `memory_limit` ≥ 256M
- [ ] extensies `gd` en `exif` aan

## 3. Mail via Google Workspace
De mail van de zaak zit al bij Google en SPF staat Google al toe — dus via Google versturen:
- [ ] In het Google-account van `info@autobedrijfrijswijk.nl`: tweestapsverificatie aan, dan
      **App-wachtwoord** maken (Google-account → Beveiliging → App-wachtwoorden).
- [ ] In `.env`:
  ```
  MAIL_MAILER=smtp
  MAIL_HOST=smtp.gmail.com
  MAIL_PORT=587
  MAIL_SCHEME=null
  MAIL_USERNAME=info@autobedrijfrijswijk.nl
  MAIL_PASSWORD=<app-wachtwoord>
  MAIL_FROM_ADDRESS=info@autobedrijfrijswijk.nl
  MAIL_FROM_NAME="Autobedrijf Rijswijk"
  ```
- [ ] **DKIM voor Google aanzetten** (ontbreekt nu): Google Admin → Apps → Google Workspace → Gmail →
      E-mail verifiëren → "Nieuw record genereren" → het TXT-record `google._domainkey` toevoegen bij
      TransIP (Domein → DNS) → terug in Google Admin "Verificatie starten".
- [ ] SPF: **niets wijzigen** (Google staat er al in). Nooit een tweede SPF-record toevoegen.
- [ ] DMARC: laat `p=none` staan tot DKIM werkt en mail-tester 9/10+ geeft; daarna eventueel
      `v=DMARC1; p=quarantine; rua=mailto:info@autobedrijfrijswijk.nl`.
- [ ] Controle in één opdracht (instellingen + SPF/DKIM/DMARC + beide mails echt versturen):
  ```bash
  php artisan mail:test jouw@adres.nl        # verwacht: MAIL TEST OK
  php artisan mail:test test-xxxx@srv1.mail-tester.com   # adres van mail-tester.com → spamscore (doel 9/10+)
  ```
- [ ] Daarna nog één keer echt: formulier invullen op de site → aanvraag bij de zaak, bevestiging bij de klant.

## 4. Database, account en voorraad
```bash
php artisan migrate --force
php artisan storage:link
php artisan db:seed --force      # lege database: alleen het beheeraccount
php artisan reviews:fetch        # reviews + actuele Google-score
php artisan cars:sync --dry-run  # alleen bij keuze A: eerst kijken
php artisan cars:sync            # eerste keer ±45–60 min: ±100 auto's × 15–40 foto's
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
> Zonder `ADMIN_PASSWORD` toont de seeder éénmalig een willekeurig wachtwoord — bewaar het.
> Na élke `.env`-wijziging opnieuw `php artisan config:cache`.

**Cron** (controlepaneel → Cronjobs), elke minuut:
```
* * * * * cd /pad/naar/project && php artisan schedule:run >> /dev/null 2>&1
```
Die draait de wachtrij (mails), een hartslag voor de bewaking, dagelijks 06:00 `cars:sync`,
dagelijks het inkorten van galerijen van auto's die >60 dagen verkocht zijn (opslag), het wissen
van oude aanvragen (AVG) en wekelijks de reviews + Google-score.

## 5. Eerst op een testadres
- [ ] Maak `nieuw.autobedrijfrijswijk.nl` (TransIP → DNS, A/AAAA naar de nieuwe hosting) en zet de
      site daar met `APP_ENV=staging` en `APP_URL=https://nieuw.autobedrijfrijswijk.nl`.
- [ ] Automatische controle vanaf je eigen pc:
  ```bash
  LIVE_CHECK_URL=https://nieuw.autobedrijfrijswijk.nl LIVE_CHECK_EMAIL=… LIVE_CHECK_PASSWORD=… python scripts/live-check.py
  ```
  verwacht `live check passed`.
- [ ] Handmatig: formulier + beide mails, een foto uploaden vanaf een telefoon, `/.env` geeft 404.
- [ ] De eigenaar werkt een week mee in het beheer (Beheer → **Hulp** legt alles uit).

## 6. Het domein omzetten
- [ ] Vooraf: **backup** van de WordPress-site (bestanden + database) en een dump van de nieuwe database.
- [ ] Nieuwe site: `APP_ENV=production`, `APP_URL=https://autobedrijfrijswijk.nl`, `config:cache`.
- [ ] Bij TransIP → Domein → DNS, **alleen** deze records wijzigen:
  - `@` **A** → IPv4 van de nieuwe hosting
  - `@` **AAAA** → IPv6 van de nieuwe hosting, of verwijderen als die er geen heeft.
    ⚠️ Vergeet je deze, dan komen IPv6-bezoekers (veel mobiel) nog op de oude site.
  - `www` blijft de CNAME — gaat vanzelf mee en wordt naar het hoofddomein doorgestuurd.
- [ ] **Niet** de nameservers verhuizen en **niet** aan MX/TXT komen: dan valt de Google-mail uit.
- [ ] SSL-certificaat voor het domein op de nieuwe hosting aanvragen (Let's Encrypt).
- [ ] TTL staat al op 300 s: binnen ±5 minuten is iedereen over.
- [ ] Oude adressen blijven werken: `/occasions/`, `/privacy-policy/` en `/voertuig/…` sturen 301 door.
- [ ] Google Search Console: sitemap `https://autobedrijfrijswijk.nl/sitemap.xml` indienen.

**Terugval:** gaat er iets mis, zet dan het A- en AAAA-record terug naar de oude waarden
(zie de tabel bovenaan). Binnen ±5 minuten staat de WordPress-site er weer. Laat die dus
minstens een maand bestaan.

## 7. Bewaking (ingebouwd, gratis, geen extra account)
De GitHub-workflow `.github/workflows/uptime.yml` controleert elke 10 minuten `/up`. Die faalt zodra
de site onbereikbaar is of de database, de **cron** of de **mail** hapert (hartslag van de scheduler,
vastzittende of mislukte mails). GitHub mailt dan automatisch wie de workflow het laatst wijzigde.
- [ ] Na de livegang één keer aanzetten:
  ```bash
  gh variable set SITE_URL --body https://autobedrijfrijswijk.nl
  ```
  (of GitHub → repo → Settings → Secrets and variables → Actions → Variables). Zonder `SITE_URL` doet hij niets.
- [ ] Testen: GitHub → Actions → *bereikbaarheid* → **Run workflow** → groen = in orde.
- [ ] Mailmeldingen van Actions staan aan: GitHub → Settings → Notifications → Actions.
- Kanttekeningen: GitHub start geplande runs soms 5–15 minuten later dan gepland, en zet ze
  in een openbare repo uit na 60 dagen zonder commits (weer aan via Actions → *Enable workflow*).
  Wil je sneller of via sms/app gewaarschuwd worden: UptimeRobot (gratis) op dezelfde `/up`.
- [ ] Vastzittende mails na het oplossen opnieuw versturen: `php artisan queue:retry all`.
- Hetzelfde probleem verschijnt ook als rode melding bovenaan het beheer.

## 8. Daarna
- [ ] `https://autobedrijfrijswijk.nl/.env` → 404 · een foutpagina toont geen debug-info
- [ ] Backups: dagelijks, bestanden **en** database (controlepaneel van de hosting); vóór elke update extra
- [ ] Updates: push naar GitHub → Actions draait tests + build en maakt een nieuw releasepakket
