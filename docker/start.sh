#!/bin/sh
set -e
cd /var/www/html

# Render geeft de poort via $PORT.
sed -ri "s/^Listen .*/Listen ${PORT:-10000}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT:-10000}>/" /etc/apache2/sites-available/000-default.conf

# Geen sleutel ingesteld? Dan per start een nieuwe (sessies vervallen bij herstart; prima voor een demo).
if [ -z "$APP_KEY" ]; then
    export APP_KEY="base64:$(head -c32 /dev/urandom | base64)"
fi

# Render zet het publieke adres in RENDER_EXTERNAL_URL (links in sitemap/mails).
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

# Beheeraccount uit ADMIN_EMAIL / ADMIN_PASSWORD (zie DatabaseSeeder).
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache database

# Het gratis plan heeft geen cron: de scheduler draait daarom in deze container (de
# uptimebot houdt hem wakker). Eerst meteen een volledige sync (de build haalt er maar
# 20), daarna elk uur (SYNC_CRON) plus de rest van routes/console.php. Als www-data,
# zodat Apache de nieuwe foto's en database-rijen kan lezen en schrijven.
su -s /bin/sh www-data -c "php artisan cars:sync --max-photos=${SYNC_MAX_PHOTOS:-12}; exec php artisan schedule:work" &

exec apache2-foreground
