#!/bin/bash
set -e

# Reconstruir archivos del Wallet desde variables de entorno Base64
WALLET_DIR="/var/www/html/wallet"
mkdir -p "$WALLET_DIR"

if [ -n "$WALLET_TNSNAMES_B64" ]; then
    echo "$WALLET_TNSNAMES_B64" | base64 -d > "$WALLET_DIR/tnsnames.ora"
fi

if [ -n "$WALLET_SQLNET_B64" ]; then
    echo "$WALLET_SQLNET_B64" | base64 -d > "$WALLET_DIR/sqlnet.ora"
fi

if [ -n "$WALLET_CWALLET_B64" ]; then
    echo "$WALLET_CWALLET_B64" | base64 -d > "$WALLET_DIR/cwallet.sso"
fi

if [ -n "$WALLET_EWALLET_B64" ]; then
    echo "$WALLET_EWALLET_B64" | base64 -d > "$WALLET_DIR/ewallet.p12"
fi

chmod 700 "$WALLET_DIR"
chmod 600 "$WALLET_DIR"/* 2>/dev/null || true
chown -R www-data:www-data "$WALLET_DIR"

# Configurar Apache para escuchar en el puerto que Railway asigna ($PORT)
PORT=${PORT:-8080}
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

echo "Arrancando Apache en puerto $PORT..."

exec "$@"
