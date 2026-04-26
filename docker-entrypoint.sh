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

# Diagnóstico de dependencias en runtime
echo "=== DIAGNÓSTICO OCI8 ==="
EXT_DIR=$(php -r 'echo ini_get("extension_dir");')
echo "Extension dir: $EXT_DIR"
ls "$EXT_DIR/oci8.so" 2>/dev/null && echo "oci8.so: EXISTE" || echo "oci8.so: NO EXISTE"
echo "Dependencias de oci8.so:"
ldd "$EXT_DIR/oci8.so" 2>&1 | grep -i "not found" || echo "Todas las dependencias OK"
echo "Oracle libs en /opt/oracle:"
ls /opt/oracle/instantclient_21_10/*.so* 2>/dev/null | head -5 || echo "NO encontradas"
echo "ld.so.preload:"
cat /etc/ld.so.preload 2>/dev/null || echo "No existe"
echo "========================"

echo "Arrancando PHP server en puerto $PORT..."
exec php -d "extension=oci8.so" -S "0.0.0.0:$PORT" -t /app/public
