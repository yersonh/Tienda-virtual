#!/bin/bash
set -e

# Forzar carga de librerías Oracle antes de que PHP arranque
export LD_LIBRARY_PATH=/opt/oracle/instantclient_21_10
export LD_PRELOAD=/opt/oracle/instantclient_21_10/libclntsh.so.21.1

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
echo "LD_LIBRARY_PATH: $LD_LIBRARY_PATH"
echo "TNS_ADMIN: $TNS_ADMIN"

# -d extension fuerza la carga aunque el conf.d no esté disponible
exec php \
    -d "extension=oci8.so" \
    -d "oci8.privileged_connect=Off" \
    -S "0.0.0.0:$PORT" \
    -t /app/public
