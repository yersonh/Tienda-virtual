FROM php:8.1-cli

RUN apt-get update && apt-get install -y \
    wget \
    unzip \
    libaio1 \
    libzip-dev \
    libpng-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install gd zip curl xml

# Oracle Instant Client 21.10 desde Oracle (el que funcionó antes)
RUN mkdir -p /opt/oracle && cd /opt/oracle && \
    wget https://download.oracle.com/otn_software/linux/instantclient/2110000/instantclient-basic-linux.x64-21.10.0.0.0dbru.zip && \
    wget https://download.oracle.com/otn_software/linux/instantclient/2110000/instantclient-sdk-linux.x64-21.10.0.0.0dbru.zip && \
    unzip instantclient-basic-linux.x64-21.10.0.0.0dbru.zip && \
    unzip instantclient-sdk-linux.x64-21.10.0.0.0dbru.zip && \
    rm *.zip && \
    echo /opt/oracle/instantclient_21_10 > /etc/ld.so.conf.d/oracle-instantclient.conf && \
    ldconfig

# OCI8 3.2.1 (compatible con PHP 8.1, el que funcionó antes)
RUN export LD_LIBRARY_PATH=/opt/oracle/instantclient_21_10 && \
    pecl install oci8-3.2.1 && \
    docker-php-ext-enable oci8 && \
    echo "Verificando oci8..." && \
    php -r "if (!extension_loaded('oci8')) { echo 'FAIL: oci8 no está cargado\n'; die(1); } echo 'SUCCESS: oci8 cargado\n';" && \
    php -m | grep oci8

COPY . /app/
COPY php.ini /usr/local/etc/php/php.ini
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_21_10 \
    TNS_ADMIN=/app/wallet

WORKDIR /app
EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public"]
