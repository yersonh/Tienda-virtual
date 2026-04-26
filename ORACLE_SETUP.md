# Configuración de Oracle Cloud en la Aplicación

Esta guía explica cómo migrar y configurar la aplicación para usar Oracle Cloud Autonomous Database en lugar de PostgreSQL.

## 1. Preparación Local

### 1.1 Descargar Oracle Instant Client

La extensión PDO_OCI requiere Oracle Instant Client. Descárgalo desde:
- **URL**: https://www.oracle.com/database/technologies/instant-client/downloads.html
- **Versión recomendada**: 21.9 o 23.1 (básica)
- **Para Windows**: Descargar `instantclient-basic-windows.x64-23.9.0.0.0dbru.zip`

### 1.2 Instalar Instant Client en Windows

1. Descomprimir `instantclient-basic-windows.x64-23.9.0.0.0dbru.zip` en `C:\instantclient_23_9`
2. Agregar `C:\instantclient_23_9` a la variable de entorno `PATH`:
   - Click derecho en "Mi PC" > Propiedades
   - Variables de entorno > Nueva variable de usuario
   - Variable: `PATH`
   - Valor: `C:\instantclient_23_9` (agregarlo a la lista existente con `;`)

3. Verificar que `oci.dll` está disponible:
   ```cmd
   where oci.dll
   ```

### 1.3 Descomprimir el Wallet de Oracle Cloud

El archivo `ewallet.p12` que descargaste contiene:
1. Descomprimirlo en una carpeta accesible (ej: `C:\oracle_wallet\`)
   - Windows: Puedes usar WinRAR o 7-Zip para descomprimir el .p12

2. Debería generar estos archivos:
   - `tnsnames.ora` - Configuración de conexión
   - `sqlnet.ora` - Configuración de seguridad
   - `cwallet.sso` - Wallet cifrado
   - `ewallet.p12` - Certificado

### 1.4 Configurar Variable de Entorno TNS_ADMIN

En Windows, crear/modificar la variable de entorno:
- Variable: `TNS_ADMIN`
- Valor: `C:\oracle_wallet\`

Verificar con:
```cmd
echo %TNS_ADMIN%
```

## 2. Configuración PHP Local

### 2.1 Verificar Extensión PDO_OCI

Para Windows, descargar la extensión precompilada desde:
- **php.net**: https://windows.php.net/downloads/pecl/
- Buscar: `php_oci8` para PHP 8.1 (NTS - Non Thread Safe si usas Apache con mpm_prefork)

1. Descargar `php_oci8.dll`
2. Colocar en `C:\php\ext\` (donde esté tu instalación PHP)
3. Editar `php.ini`:
   ```ini
   extension=php_oci8.dll
   ```
4. Reiniciar Apache/PHP-FPM

Verificar con:
```bash
php -m | grep -i oci
```

## 3. Variables de Entorno para la Aplicación

El archivo `database.php` ahora lee variables de entorno. Configura en tu `.env` o en el sistema:

```
ORACLE_USER=admin
ORACLE_PASSWORD=tu_contraseña
ORACLE_TNS=bc27bncudfcgiclb_high
TNS_ADMIN=C:\oracle_wallet\
```

### 3.1 Crear archivo `.env` local (opcional pero recomendado)

Crea `.env` en la raíz del proyecto:
```
ORACLE_USER=admin
ORACLE_PASSWORD=tu_contraseña_aqui
ORACLE_TNS=bc27bncudfcgiclb_high
TNS_ADMIN=C:\oracle_wallet\
APP_ENV=development
```

Luego en `database.php` puedes cargar este archivo al inicio:
```php
$dotenv = parse_ini_file(__DIR__ . '/../../.env');
foreach ($dotenv as $key => $value) {
    putenv("{$key}={$value}");
}
```

## 4. Testing Local

### 4.1 Iniciar Servidor PHP

```bash
cd C:\ruta\al\proyecto
php -S localhost:8080 -t public
```

Acceder a: http://localhost:8080

### 4.2 Verificar Conexión a Oracle

Crear un archivo de test `test_oracle.php` en `public/`:

```php
<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = Database::getConnection();
    echo "<h1>✅ Conexión a Oracle exitosa</h1>";
    
    // Probar query simple
    $stmt = $conn->prepare("SELECT 1 as num FROM dual");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Resultado: " . print_r($result, true);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h1>❌ Error</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
```

Acceder a: http://localhost:8080/test_oracle.php

### 4.3 Solucionar Problemas Comunes

**Error: "ORA-12514 TNS:could not resolve SERVICE_NAME"**
- Verificar que TNS_ADMIN apunta a la carpeta correcta con tnsnames.ora
- Verificar que el nombre TNS `bc27bncudfcgiclb_high` existe en tnsnames.ora

**Error: "ORA-01017 invalid username/password"**
- Verificar credenciales en ORACLE_USER y ORACLE_PASSWORD
- Default para ATP es usuario: `admin`

**Error: "could not find the /opt/oracle/instantclient_*"**
- Instant Client no está instalado correctamente
- Verificar PATH incluye el directorio de Instant Client

## 5. Despliegue en Railway

Railway necesita:
1. Un Dockerfile válido (ya está incluido en este proyecto)
2. Variables de entorno configuradas
3. El Wallet accesible en el contenedor

### 5.1 Opción A: Usar Dockerfile (Recomendado)

El archivo `Dockerfile` incluido:
- Instala automáticamente Oracle Instant Client
- Configura PDO_OCI
- Establece TNS_ADMIN=/app/wallet

**En Railway:**
1. Conectar el repositorio GitHub
2. Railway detectará el `Dockerfile` automáticamente
3. Configurar variables de entorno en la sección "Variables":
   - `ORACLE_USER`: admin
   - `ORACLE_PASSWORD`: [tu contraseña]
   - `ORACLE_TNS`: bc27bncudfcgiclb_high
   - `TNS_ADMIN`: /app/wallet

### 5.2 Opción B: Usar Nixpacks (Alternativa)

Si prefieres Nixpacks, modificar `railway.json`:
```json
{
  "build": {
    "builder": "DOCKERFILE",
    "buildCommand": null
  },
  "deploy": {
    "startCommand": "apache2-foreground",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

### 5.3 Configurar Wallet en Railway

**⚠️ IMPORTANTE: Nunca incluyas el archivo ewallet.p12 en Git (tiene credenciales)**

Opciones para hacer disponible el Wallet en Railway:

#### Opción 1: Copiar manualmente (Desarrollo)
1. En Railway, en la sección "Files", subir los archivos del Wallet descomprimidos:
   - tnsnames.ora
   - sqlnet.ora
   - cwallet.sso
2. Colocar en `/app/wallet/`

#### Opción 2: Crear archivo en el build (Producción)
Agregar en el Dockerfile comandos para construir/obtener los archivos necesarios desde una fuente segura (no recomendado hardcodear en el repo).

#### Opción 3: Usar Base64 en Variables de Entorno
```bash
# En local, codificar los archivos del wallet:
base64 -i tnsnames.ora > tnsnames.b64
base64 -i sqlnet.ora > sqlnet.b64
base64 -i cwallet.sso > cwallet.b64
```

Luego en el Dockerfile:
```dockerfile
RUN echo ${TNS_NAMES_B64} | base64 -d > /app/wallet/tnsnames.ora && \
    echo ${TNS_CONFIG_B64} | base64 -d > /app/wallet/sqlnet.ora && \
    echo ${WALLET_CERT_B64} | base64 -d > /app/wallet/cwallet.sso
```

Y en Railway, agregar variables: `TNS_NAMES_B64`, `TNS_CONFIG_B64`, `WALLET_CERT_B64`

### 5.4 Monitorear el Deploy en Railway

```bash
# Ver logs en tiempo real
railway logs

# Verificar que la extensión OCI está cargada
railway run php -m | grep -i oci
```

## 6. Cambios en Consultas SQL

**IMPORTANTE**: Oracle tiene diferencias con PostgreSQL:

### Cambios Necesarios:

1. **NULL handling**
   - PostgreSQL: ISNULL()
   - Oracle: NVL() o COALESCE()

2. **Orden de registros**
   - SQL: `NEXT n ROWS` en Oracle, `LIMIT n` en PostgreSQL
   - Oracle: `ORDER BY col OFFSET n ROWS FETCH NEXT m ROWS ONLY`

3. **Secuencias/Auto-increment**
   - PostgreSQL: `SERIAL` type
   - Oracle: `CREATE SEQUENCE` + trigger

4. **Fechas**
   - Oracle: `SYSDATE` o `CURRENT_DATE`
   - PostgreSQL: `CURRENT_TIMESTAMP`

5. **Operador de concatenación**
   - PostgreSQL: `||`
   - Oracle: `||` también funciona, pero mejor usar `CONCAT()`

Estos cambios serán documentados cuando refactorices las queries de los models.

## 7. Checklist de Verificación

- [ ] Instant Client descargado e instalado
- [ ] Variable TNS_ADMIN configurada
- [ ] php_oci8.dll instalado y en php.ini
- [ ] Wallet descomprimido en la carpeta correcta
- [ ] Variables de entorno ORACLE_* configuradas
- [ ] test_oracle.php funciona localmente
- [ ] Repository conectado a Railway
- [ ] Dockerfile detectado en Railway
- [ ] Variables de entorno configuradas en Railway
- [ ] Wallet archivos subidos a Railway
- [ ] Deploy exitoso en Railway
- [ ] Test endpoint en Railway funciona

## 8. Próximos Pasos

1. **Refactorizar queries SQL** en los models para sintaxis Oracle
2. **Adaptaciones de tipos de datos** si es necesario
3. **Testing completo** del flujo de registro, login, carrito
4. **Migración de datos** de PostgreSQL a Oracle (si es aplicable)

---

**Soporte**: Si tienes problemas, revisa los logs:
- Local: `php -S localhost:8080 -t public` muestra errores en consola
- Railway: `railway logs` muestra los logs del contenedor
