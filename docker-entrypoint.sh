#!/bin/bash
set -e

WALLET_DIR="/app/wallet"
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

chmod 600 "$WALLET_DIR"/* 2>/dev/null || true

PORT=${PORT:-8080}
echo "Arrancando PHP server en puerto $PORT..."
exec /usr/bin/php -S "0.0.0.0:$PORT" -t /app/public
