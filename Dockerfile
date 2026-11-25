# Portfolio-demo op Render (gratis plan). Eén container: Apache + PHP + SQLite.
# De build haalt een eerste deel van de voorraad op; de scheduler in de container houdt alles daarna elk uur bij.
# De echte livegang staat in DEPLOY.md; dit bestand is alleen voor de demo.

FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libjpeg62-turbo-dev libpng-dev libwebp-dev libfreetype6-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install gd exif zip \
    && a2enmod rewrite \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY . .
COPY --from=assets /app/public/build public/build
# Herkansing met pauze: een tijdelijke storing bij GitHub (HTTP 500 op één pakket) liet de deploy
# van 25 nov mislukken; composer zelf probeert maar een paar seconden opnieuw.
RUN for wait in 0 15 45; do sleep $wait; \
        composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress && exit 0; \
    done; exit 1

ENV APP_ENV=staging \
    APP_DEBUG=false \
    BRAND_DEMO=true \
    TRUSTED_PROXIES=* \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/database/database.sqlite \
    QUEUE_CONNECTION=sync \
    MAIL_MAILER=log \
    LOG_CHANNEL=stderr \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    FILESYSTEM_DISK=local \
    SYNC_CRON="0 * * * *" \
    SYNC_MAX_PHOTOS=12

# Database + de HELE voorraad met galerijen (12 foto's) tijdens de build. De build-machine verkleint
# foto's ±5-7x sneller dan de draaiende gratis container (gemeten: ±0,7 s vs ±4 s per foto), en Render
# houdt de oude versie live tot deze klaar is: de nieuwe versie gaat dus meteen compleet live, zonder
# uur met alleen omslagfoto's. Duur: ±2,5 s tempo + ±8 s foto's per auto, ±25 min voor 143 auto's.
# Mislukt de sync (dealersite even weg), dan gaat de build door met een lege voorraad.
RUN rm -f .env public/storage \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && touch database/database.sqlite \
    && APP_KEY=base64:$(head -c32 /dev/urandom | base64) sh -c '\
        php artisan migrate --force \
        && (php artisan cars:sync --max-photos=12 || echo "cars:sync mislukt, voorraad leeg") \
        && (php artisan reviews:fetch || echo "reviews:fetch mislukt") \
        && php artisan storage:link' \
    && chown -R www-data:www-data storage bootstrap/cache database

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh
CMD ["start.sh"]
