# Deploy-checklist — Autobedrijf Rijswijk (Laravel 13)

Doorloop dit vóór elke livegang. De meeste stappen zijn eenmalig instellen;
daarna is het afvinken.

## 0. Eerst beslissen: waar komt de voorraad vandaan na de overstap?
De voorraad-sync leest nu de WordPress-site op `autobedrijfrijswijk.nl`. Neemt deze site dat domein
over, dan is die bron weg. Kies vóór de livegang één van de twee:
- [ ] **WordPress-site blijft draaien op een ander adres** (bv. `voorraad.autobedrijfrijswijk.nl`, alleen
      voor de import) → `DEALER_SITE_URL=https://voorraad.autobedrijfrijswijk.nl`. Daar blijft de eigenaar
      auto's invoeren; deze site neemt ze elke ochtend over.
- [ ] **Voorraad voortaan alleen via /admin** → `DEALER_SITE_URL=` (leeg): de sync staat dan uit.

Wijst `DEALER_SITE_URL` naar dit domein zelf, dan weigert de sync (en krijg je een foutmail).
Oude WordPress-adressen (`/occasions/`, `/privacy-policy/`, `/voertuig/…`) worden automatisch
doorgestuurd; de juridische pdf's staan in `public/docs/`.

## 1. Omgeving (`.env` op de server)
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`  ← **belangrijk**: anders lekken stacktraces naar bezoekers
- [ ] `APP_URL=https://autobedrijfrijswijk.nl` (echte domein, met https)
- [ ] `APP_KEY` gezet (`php artisan key:generate` als die leeg is)
- [ ] Database-gegevens (`DB_*`) van de productie-database ingevuld
- [ ] `QUEUE_CONNECTION=database` (aanvraag-mails via de wachtrij, zie 5b)
- [ ] `LOG_STACK=daily` (logbestanden per dag, 14 dagen bewaard) en `LOG_LEVEL=warning`
- [ ] `SESSION_SECURE_COOKIE=true` (login-cookie alleen over https)
- [ ] `ERROR_ALERT_EMAIL=` het adres dat een mail krijgt bij een fout of een mislukte voorraad-sync
- [ ] `ADMIN_EMAIL=` een **echt** mailadres van de eigenaar — anders werkt "wachtwoord vergeten" niet
      (de standaard `admin@autobedrijfrijswijk.test` bestaat niet). `ADMIN_PASSWORD` optioneel (zie stap 4)

## 1a. Webserver (Hostinger)
- [ ] De map `public/` moet de webroot zijn (hPanel → Websites → domein → documentroot op
      `…/public` zetten). Staat de hele projectmap in `public_html`, dan zijn `.env` en de code
      van buitenaf op te vragen.
- [ ] SSL aan + "HTTPS forceren" aan (hPanel → Beveiliging → SSL).

## 1b. PHP-instellingen (anders falen foto-uploads vanaf een telefoon)
Bij Hostinger: hPanel → Geavanceerd → PHP-configuratie.
- [ ] `upload_max_filesize` ≥ `16M` en `post_max_size` ≥ `64M` (telefoonfoto's zijn 5–10 MB)
- [ ] `memory_limit` ≥ `256M` (verkleinen van grote foto's)
- [ ] Extensies `gd` en `exif` aan (verkleinen + rechtop draaien; zonder `exif` wordt de foto ongewijzigd opgeslagen)

## 2. Mail (anders komen aanvragen niet of in de spam aan)
- [ ] `MAIL_MAILER=smtp` + `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_ENCRYPTION`
- [ ] `MAIL_FROM_ADDRESS` op het **eigen domein** (bv. `noreply@autobedrijfrijswijk.nl`) en `MAIL_FROM_NAME`
- [ ] **SPF, DKIM en DMARC** in de DNS van het domein — de exacte waarden geeft je mailprovider.
      Voorbeeld bij Hostinger-mail (hPanel → E-mails → DNS-instellingen toont ze kant-en-klaar):
  ```
  TXT  @                  v=spf1 include:_spf.mail.hostinger.com ~all
  TXT  hostingermail._domainkey   (DKIM-sleutel uit hPanel)
  TXT  _dmarc             v=DMARC1; p=quarantine; rua=mailto:info@autobedrijfrijswijk.nl
  ```
  Verstuurt het domein al mail via een andere dienst (bv. de huidige site), voeg die dan samen in
  één SPF-record — nooit twee SPF-records.
- [ ] Testen: contactformulier invullen → de zaak krijgt de aanvraag, de klant de bevestiging.
      Controleer de score op <https://www.mail-tester.com> (doel: 9/10 of hoger).

## 3. Merk-/echte data (`.env`, zie `config/brand.php`)
- [ ] `BRAND_REVIEW_URL` = de exacte Google "Reviews delen"-link van de zaak
- [ ] `BRAND_REVIEW_RATING` / `BRAND_REVIEW_COUNT` gelijk aan het Google-profiel
- [ ] `BRAND_FL_STOCK_ID` = de FinancialLease.nl dealer-feed van de zaak (nu 1262)
- [ ] Openingstijden in `config/brand.php` (`opening_hours`) kloppen
- [ ] `BRAND_KVK` (en `BRAND_VAT`) ingevuld — wettelijk verplicht, verschijnt in de footer

## 4. Build, database en voorraad (op de server)
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link          # voor de autofoto's
php artisan db:seed --force       # alleen bij een lege database: maakt het beheeraccount
```
> In productie maakt de seeder **alleen** het beheeraccount aan. Zonder `ADMIN_PASSWORD` verschijnt
> een willekeurig wachtwoord éénmalig in de console — bewaar het.

Voorraad ophalen van de dealersite:
```bash
php artisan cars:sync --dry-run   # eerst kijken wat er gebeurt (wijzigt niets)
php artisan cars:sync             # eerste keer ±10–15 min (alle auto's + foto's)
```

## 5. Cachen voor snelheid
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
> Let op: na élke `.env`- of config-wijziging opnieuw `config:cache` draaien.

## 5b. Scheduler (cron) — zonder dit gebeurt er niets automatisch
Eén cronjob elke minuut (bij Hostinger: hPanel → Geavanceerd → Cron Jobs):
```
* * * * * cd /pad/naar/project && php artisan schedule:run >> /dev/null 2>&1
```
Die start (zie `routes/console.php`):
- elke minuut de **wachtrij** (aanvraag-mails naar zaak en klant);
- dagelijks 06:00 **`cars:sync`** (nieuwe auto's, verkochte eraf, prijzen bij) — mislukt het, dan mail naar `ERROR_ALERT_EMAIL`;
- dagelijks **`model:prune`** (AVG: oude aanvragen wissen);
- wekelijks **`reviews:fetch`** (Google-reviews verversen).

- [ ] Eenmalig handmatig: `php artisan reviews:fetch` (vult direct de review-cache)

## 6. Verifiëren (smoke test)
- [ ] Vanaf je eigen pc de automatische live-check tegen de echte site:
  ```bash
  LIVE_CHECK_URL=https://autobedrijfrijswijk.nl LIVE_CHECK_EMAIL=… LIVE_CHECK_PASSWORD=… python scripts/live-check.py
  ```
  (controleert o.a. alle pagina's, lightbox, kaart-na-klik, aanbodfilter en het beheer; verwacht `live check passed`)
- [ ] Contactformulier verstuurt, beide mails komen aan, en de aanvraag staat in **/admin/aanvragen**
- [ ] Een foto uploaden vanaf een telefoon lukt (wordt verkleind opgeslagen als `.webp`)
- [ ] Security headers aanwezig: `curl -I https://…` toont `Content-Security-Policy` + `X-Content-Type-Options`

## 7. Na livegang
- [ ] `APP_DEBUG=false` nog eens dubbelchecken op de live-URL (geen debug-scherm bij een fout)
- [ ] Admin-wachtwoord sterk en uniek (Beheer → Profiel)
- [ ] **Backups** aan: Hostinger hPanel → Bestanden → Back-ups. Business/Cloud-pakketten maken dagelijks
      automatisch een backup (bestanden én database); controleer dat het aan staat en maak vóór elke
      deploy handmatig een extra backup.
- [ ] Code naar GitHub pushen: de workflow `.github/workflows/tests.yml` draait dan bij elke push de
      tests + build (tabblad **Actions**).

## Rollback
- Fout na deploy? Zet de vorige release terug en draai `php artisan migrate:rollback` alleen als er een migratie bij zat.
- Bewaar altijd een databasedump van vlak vóór de deploy.
