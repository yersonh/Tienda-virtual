#!/bin/bash
set -e

WALLET_DIR="/var/www/html/wallet"
mkdir -p "$WALLET_DIR"

# Reconstruir archivos del Wallet desde variables de entorno Base64
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

exec "$@"
