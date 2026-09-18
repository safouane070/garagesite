# Kroon Automobielen — Autogarage website

Een complete autogarage-website gebouwd met **Laravel 13**, **MySQL/MariaDB**, **Blade + Tailwind CSS** en **Alpine.js**. Met een publieke etalage (homepage, aanbod met live filtering, detailpagina met foto-carousel) en een beveiligde admin-omgeving (Laravel Breeze) voor volledig voorraadbeheer.

---

## 🚀 Live demo & screenshots

> _Binnenkort online — hier komt een demo-link. Voeg hieronder een screenshot of GIF van de homepage + admin toe, bijvoorbeeld:_
>
> `![Homepage](docs/screenshot-home.png)`

---

## Functionaliteit

**Publiek**
- Homepage met uitgelichte en nieuwste occasions
- Aanbodpagina met **instant client-side filtering** (Alpine.js): zoeken, merk, brandstof, dual-range prijs- en bouwjaar-slider, sorteren, verwijderbare filter-chips, live resultatenteller en een mobiele filter-drawer
- Detailpagina met foto-carousel, alle specificaties en vergelijkbare auto's
- Volledig responsive (mobiel / tablet / desktop) en toegankelijk (focus states, alt-teksten, skip-link)

**Admin** (`/admin`, na inloggen)
- Dashboard met voorraadoverzicht en statistieken
- Auto's toevoegen / bewerken / verwijderen (CRUD)
- Meerdere foto's uploaden per auto, omslagfoto instellen, foto's verwijderen
- Snelle statuswijziging (beschikbaar / gereserveerd / verkocht)

---

## Vereisten

- **PHP 8.2 of hoger** (dit project is gebouwd op PHP 8.3)
- **Composer**
- **Node.js 18+** en npm
- **MySQL of MariaDB** (bv. via XAMPP)

> **Let op (XAMPP):** de PHP die met oudere XAMPP-versies meekomt kan te oud zijn (Laravel 13 vereist PHP 8.2+). Op deze machine draait een losse PHP 8.3 in `C:\php83`. Vervang in de commando's hieronder `php` desnoods door het volledige pad, bv. `C:\php83\php.exe`.

---

## Installatie

```bash
# 1. Dependencies installeren
composer install
npm install

# 2. Omgevingsbestand aanmaken en app-sleutel genereren
cp .env.example .env
php artisan key:generate

# 3. Database aanmaken (lege database met de naam 'garagesite')
#    Bijvoorbeeld via phpMyAdmin, of:
mysql -u root -e "CREATE DATABASE garagesite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
#    Controleer daarna de DB-gegevens in .env (standaard XAMPP: user root, geen wachtwoord)

# 4. Tabellen aanmaken en met voorbeelddata vullen
php artisan migrate --seed

# 5. Publieke opslag-snelkoppeling maken (voor geüploade foto's)
php artisan storage:link
```

> De seeder haalt **echte autofoto's** op via de publieke Wikimedia Commons-API en slaat ze lokaal op. Werkt dat niet (bv. geen internet), dan vallen de auto's automatisch terug op nette, in-huisstijl SVG-placeholders — de seeder faalt nooit.

---

## Draaien

Start de front-end build (Vite) en de webserver in **twee terminals**:

```bash
# Terminal 1 — Vite (bouwt Tailwind/JS met hot reload)
npm run dev
```

```bash
# Terminal 2 — Laravel webserver
php artisan serve
```

Open vervolgens **http://localhost:8000**.

Voor een productie-build (dan is `npm run dev` niet nodig):

```bash
npm run build
php artisan serve
```

---

## Inloggen (admin)

Na `php artisan migrate --seed` bestaat er een beheerdersaccount:

| E-mail | Wachtwoord |
|--------|------------|
| `admin@kroon.test` | `password` |

Log in via **http://localhost:8000/login** en beheer de voorraad op **/admin**.
Publieke registratie is bewust uitgeschakeld — extra accounts maak je via de seeder of `php artisan tinker`.

---

## Structuur (kort)

```
app/
  Enums/CarStatus.php            # Statussen + labels/kleuren op één plek
  Models/Car.php, CarImage.php   # Modellen, relaties, query-scopes
  Http/Controllers/              # Publiek (Home, Car) + Admin\CarController
  Http/Requests/CarRequest.php   # Validatieregels (toevoegen/bewerken)
  Support/CarPhotoFetcher.php    # Haalt echte foto's op bij Wikimedia
  Support/PlaceholderImage.php   # SVG-vangnet voor foto's
database/
  migrations/                    # cars, car_images
  seeders/CarSeeder.php          # 12 curated demo-auto's + foto's
  factories/CarFactory.php       # Willekeurige, plausibele auto's
resources/views/
  components/                    # car-card, status-badge, icon (Lucide), layouts, brand-mark
  home.blade.php, cars/          # Publieke pagina's
  admin/cars/                    # Dashboard + formulieren
```

## Ontwerpkeuzes

- **Signatuur:** donker "automotive-premium" thema (obsidiaan + messing accent), scherpe hoeken met hairline-scheidingen i.p.v. overal identieke cards.
- **Typografie:** Space Grotesk (display) + Inter (tekst) + JetBrains Mono (technische spec-labels).
- **Iconen:** één consistente [Lucide](https://lucide.dev)-set via een eigen `<x-icon>`-component (geen emoji).
- Kleuren en fonts staan als tokens in `tailwind.config.js` — pas ze daar aan om de hele huisstijl te wijzigen.

---

## Licentie

Uitgebracht onder de [MIT-licentie](LICENSE).

Gemaakt door **Safouane Lahoua** — [github.com/safouane070](https://github.com/safouane070)
