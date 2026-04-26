#!/bin/bash
set -e

# Asegurar que las librerías de Oracle sean visibles para PHP en runtime
export LD_LIBRARY_PATH=/opt/oracle/instantclient_21_10:${LD_LIBRARY_PATH}
ldconfig 2>/dev/null || true

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

# Reemplazar el puerto en el CMD si viene como argumento
exec php -S "0.0.0.0:$PORT" -t /app/public
