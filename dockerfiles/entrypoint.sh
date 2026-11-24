#!/bin/bash
set -e

# Entrypoint script for the LATTE web container.

# Ensure the storage directory and cache exist.
mkdir -p /var/www/html/storage/cache

# Fix storage directory permissions so Apache and PHP can read and write SQLite databases and cache.
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Generate a self-signed certificate if one is not present.
if [ ! -f /etc/ssl/certs/ssl-cert-snakeoil.pem ] || [ ! -f /etc/ssl/private/ssl-cert-snakeoil.key ]; then
    mkdir -p /etc/ssl/certs /etc/ssl/private
    openssl req -new -newkey rsa:2048 -days 365 -nodes -x509 \
        -subj "/C=BE/ST=Flanders/L=Brussels/O=LATTE/CN=localhost" \
        -keyout /etc/ssl/private/ssl-cert-snakeoil.key \
        -out /etc/ssl/certs/ssl-cert-snakeoil.pem 2>/dev/null || true
fi

# Clean up any stale worker process id or stop signal files on container boot.
rm -f /var/www/html/storage/latte_daemon.pid /var/www/html/storage/latte_daemon.stop

# Start the LATTE queue worker daemon in background as www-data user.
su -s /bin/bash www-data -c "php /var/www/html/cli/latte_queue_worker.php --daemon >> /var/www/html/storage/latte_worker.log 2>&1 &"

# Hand over execution to Apache foreground process.
exec "$@"
