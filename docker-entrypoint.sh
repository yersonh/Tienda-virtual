#!/bin/bash
set -e

WALLET_DIR="/app/wallet"
mkdir -p "$WALLET_DIR"

[ -n "$WALLET_TNSNAMES_B64" ] && echo "$WALLET_TNSNAMES_B64" | base64 -d > "$WALLET_DIR/tnsnames.ora"
[ -n "$WALLET_SQLNET_B64" ] && echo "$WALLET_SQLNET_B64" | base64 -d > "$WALLET_DIR/sqlnet.ora"
[ -n "$WALLET_CWALLET_B64" ] && echo "$WALLET_CWALLET_B64" | base64 -d > "$WALLET_DIR/cwallet.sso"
[ -n "$WALLET_EWALLET_B64" ] && echo "$WALLET_EWALLET_B64" | base64 -d > "$WALLET_DIR/ewallet.p12"

chmod 600 "$WALLET_DIR"/* 2>/dev/null || true

PORT=${PORT:-8080}
exec php -S "0.0.0.0:$PORT" -t /app/public
