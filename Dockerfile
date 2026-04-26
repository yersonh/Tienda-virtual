FROM php:8.1-cli

# cache-bust: v3-symlinks
RUN apt-get update && apt-get install -y \
    wget \
    unzip \
    libaio1t64 \
    libzip-dev \
    libpng-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install gd zip curl xml

# Oracle Instant Client: Basic Lite + SDK (SDK tiene los headers .h para compilar)
RUN mkdir -p /opt/oracle && \
    cd /opt/oracle && \
    wget -q "https://download.oracle.com/otn_software/linux/instantclient/2110000/instantclient-basiclite-linux.x64-21.10.0.0.0dbru.zip" -O ic-basic.zip && \
    wget -q "https://download.oracle.com/otn_software/linux/instantclient/2110000/instantclient-sdk-linux.x64-21.10.0.0.0dbru.zip" -O ic-sdk.zip && \
    unzip ic-basic.zip && \
    unzip ic-sdk.zip && \
    rm ic-basic.zip ic-sdk.zip && \
    # Symlinks en /usr/lib para que el linker siempre los encuentre sin LD_LIBRARY_PATH
    ln -s /opt/oracle/instantclient_21_10/libclntsh.so.21.1     /usr/lib/libclntsh.so.21.1 && \
    ln -s /opt/oracle/instantclient_21_10/libclntshcore.so.21.1 /usr/lib/libclntshcore.so.21.1 && \
    ln -s /opt/oracle/instantclient_21_10/libnnz21.so            /usr/lib/libnnz21.so && \
    ln -s /opt/oracle/instantclient_21_10/libocci.so.21.1        /usr/lib/libocci.so.21.1 && \
    echo /opt/oracle/instantclient_21_10 > /etc/ld.so.conf.d/oracle-instantclient.conf && \
    ldconfig

RUN docker-php-ext-configure pdo_oci \
        --with-pdo-oci=instantclient,/opt/oracle/instantclient_21_10 && \
    docker-php-ext-install pdo_oci && \
    # Forzar ini explícito por si docker-php-ext-install no lo generó
    echo "extension=pdo_oci.so" > /usr/local/etc/php/conf.d/docker-php-ext-pdo_oci.ini && \
    php -r "extension_loaded('pdo_oci') or die('ERROR: pdo_oci no carga en build\n');" && \
    echo "pdo_oci cargado correctamente"

COPY . /app/

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_21_10 \
    TNS_ADMIN=/app/wallet

WORKDIR /app

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public"]
