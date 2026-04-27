FROM php:8.1-cli

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

# Oracle Instant Client: Basic Lite + SDK
RUN mkdir -p /opt/oracle && \
    cd /opt/oracle && \
    wget -q "https://download.oracle.com/otn_software/linux/instantclient/2110000/instantclient-basiclite-linux.x64-21.10.0.0.0dbru.zip" -O ic-basic.zip && \
    wget -q "https://download.oracle.com/otn_software/linux/instantclient/2110000/instantclient-sdk-linux.x64-21.10.0.0.0dbru.zip" -O ic-sdk.zip && \
    unzip ic-basic.zip && \
    unzip ic-sdk.zip && \
    rm ic-basic.zip ic-sdk.zip && \
    # Copiar librerías a /usr/lib para que SIEMPRE se encuentren en runtime
    cp /opt/oracle/instantclient_21_10/*.so* /usr/lib/ && \
    echo /opt/oracle/instantclient_21_10 > /etc/ld.so.conf.d/oracle-instantclient.conf && \
    # Preload libclntsh para que se cargue antes que cualquier otra cosa
    echo /usr/lib/libclntsh.so.21.1 > /etc/ld.so.preload && \
    ldconfig

# Instalar oci8
RUN export LD_LIBRARY_PATH=/opt/oracle/instantclient_21_10 && \
    echo "instantclient,/opt/oracle/instantclient_21_10" | pecl install oci8-3.2.1 && \
    docker-php-ext-enable oci8 && \
    php -r "extension_loaded('oci8') or die('ERROR: oci8 no carga en build\n');" && \
    echo "=== oci8 verificado OK ==="

COPY . /app/

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_21_10 \
    TNS_ADMIN=/app/wallet

WORKDIR /app

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public"]

