# Deploy-checklist — Autobedrijf Rijswijk (Laravel 13)

Doorloop dit vóór elke livegang. De meeste stappen zijn eenmalig instellen;
daarna is het afvinken.

## 1. Omgeving (`.env` op de server)
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`  ← **belangrijk**: anders lekken stacktraces naar bezoekers
- [ ] `APP_URL=https://autobedrijfrijswijk.nl` (echte domein, met https)
- [ ] `APP_KEY` gezet (`php artisan key:generate` als die leeg is)
- [ ] Database-gegevens (`DB_*`) van de productie-database ingevuld

## 2. Mail (leads komen anders niet aan)
- [ ] `MAIL_MAILER=smtp` + `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_ENCRYPTION`
- [ ] `MAIL_FROM_ADDRESS` en `MAIL_FROM_NAME` ingevuld
- [ ] Testmail sturen: contactformulier invullen → mail moet in de inbox van `BRAND_EMAIL` landen

## 3. Merk-/echte data (`.env`, zie `config/brand.php`)
- [ ] `BRAND_REVIEW_URL` = de exacte Google "Reviews delen"-link van de zaak
- [ ] `BRAND_REVIEW_RATING` / `BRAND_REVIEW_COUNT` gelijk aan het Google-profiel
- [ ] `BRAND_FL_STOCK_ID` = de FinancialLease.nl dealer-feed van de zaak (nu 1262)
- [ ] Openingstijden in `config/brand.php` (`opening_hours`) kloppen
- [ ] Echte opties per auto toegevoegd via het admin-dashboard (optioneel)

## 4. Build & database (op de server)
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link          # voor de geüploade autofoto's
php artisan db:seed --force       # alleen bij een lege database
```

## 5. Cachen voor snelheid
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
> Let op: na élke `.env`- of config-wijziging opnieuw `config:cache` draaien.

## 6. Verifiëren (smoke test)
- [ ] Homepage, /aanbod, een detailpagina, /contact, /diensten, /financial-lease laden (200)
- [ ] Een auto toevoegen/bewerken in de admin werkt (login: seeder-account)
- [ ] Contactformulier verstuurt én de mail komt aan
- [ ] Google-kaart op /contact toont de juiste locatie
- [ ] Security headers aanwezig: `curl -I https://…` toont `Content-Security-Policy` + `X-Content-Type-Options`

## 7. Na livegang
- [ ] `APP_DEBUG=false` nog eens dubbelchecken op de live-URL (geen debug-scherm bij een fout)
- [ ] Admin-wachtwoord gewijzigd naar een sterk, uniek wachtwoord
- [ ] Backup van de database ingericht

## Rollback
- Fout na deploy? Zet de vorige release terug en draai `php artisan migrate:rollback` alleen als er een migratie bij zat.
- Bewaar altijd een databasedump van vlak vóór de deploy.
