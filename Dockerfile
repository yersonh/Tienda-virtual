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

# Oracle Instant Client 19c desde GitHub (público, sin autenticación)
RUN mkdir -p /opt/oracle && cd /opt/oracle && \
    wget -q https://github.com/bumpx/oracle-instantclient/raw/master/instantclient-basic-linux.x64-19.23.0.0.0dbru.zip && \
    wget -q https://github.com/bumpx/oracle-instantclient/raw/master/instantclient-sdk-linux.x64-19.23.0.0.0dbru.zip && \
    unzip -q instantclient-basic-linux.x64-19.23.0.0.0dbru.zip && \
    unzip -q instantclient-sdk-linux.x64-19.23.0.0.0dbru.zip && \
    rm *.zip && \
    echo /opt/oracle/instantclient_19_23 > /etc/ld.so.conf.d/oracle-instantclient.conf && \
    ldconfig

# OCI8 2.2.0 (compatible con PHP 8.0, versión estable)
RUN export LD_LIBRARY_PATH=/opt/oracle/instantclient_19_23 && \
    pecl install oci8-2.2.0 && \
    docker-php-ext-enable oci8 && \
    php -r "extension_loaded('oci8') or die('FAIL\n');" && \
    echo "OCI8 2.2.0 OK"

COPY . /app/

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_21_10 \
    TNS_ADMIN=/app/wallet

WORKDIR /app

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public"]

