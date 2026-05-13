FROM php:8.1-cli-bullseye

# Actualizar índices de paquetes con reintentos
RUN apt-get update -o Acquire::http::No-Cache=True || \
    (sleep 5 && apt-get update) || \
    (sleep 10 && apt-get update)

# Instalar dependencias mínimas
RUN apt-get install -y --no-install-recommends \
    unzip curl wget ca-certificates \
    libaio1 libaio-dev \
    libcurl4-openssl-dev libxml2-dev \
    libonig-dev \
    git && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-install pdo curl xml mbstring opcache

# Descargar Oracle Instant Client
RUN mkdir -p /opt/oracle && \
    cd /opt/oracle && \
    wget --timeout=30 -q https://download.oracle.com/otn_software/linux/instantclient/2110000/instantclient-basiclite-linux.x64-21.10.0.0.0dbru.zip -O ic-basic.zip && \
    wget --timeout=30 -q https://download.oracle.com/otn_software/linux/instantclient/2110000/instantclient-sdk-linux.x64-21.10.0.0.0dbru.zip -O ic-sdk.zip && \
    unzip -q ic-basic.zip && \
    unzip -q ic-sdk.zip && \
    rm ic-basic.zip ic-sdk.zip && \
    echo /opt/oracle/instantclient_21_10 > /etc/ld.so.conf.d/oracle.conf && \
    ldconfig

# Variables de entorno Oracle
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_21_10
ENV ORACLE_HOME=/opt/oracle/instantclient_21_10

# Instalar OCI8
RUN export LDFLAGS="-Wl,-rpath,/opt/oracle/instantclient_21_10" && \
    echo 'instantclient,/opt/oracle/instantclient_21_10' | pecl install oci8-3.2.1 && \
    docker-php-ext-enable oci8 && \
    php -r "extension_loaded('oci8') or die('ERROR: oci8 no carga en build\n');"

# App
WORKDIR /app
COPY . .
COPY config/php-performance.ini /usr/local/etc/php/conf.d/performance.ini

# EntryPoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public", "/app/public/router.php"]
