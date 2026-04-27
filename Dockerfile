FROM php:8.0-cli

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    wget \
    unzip \
    libaio1 \
    libzip-dev \
    libpng-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP estándar
RUN docker-php-ext-install gd zip curl xml

# Oracle Instant Client 19c (LTS, más estable)
RUN mkdir -p /opt/oracle && cd /opt/oracle && \
    wget -q https://download.oracle.com/otn_software/linux/instantclient/1923000/instantclient-basic-linux.x64-19.23.0.0.0dbru.zip && \
    wget -q https://download.oracle.com/otn_software/linux/instantclient/1923000/instantclient-sdk-linux.x64-19.23.0.0.0dbru.zip && \
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

# Copiar app y config
COPY . /app/
COPY php.ini /usr/local/etc/php/php.ini
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_19_23 \
    TNS_ADMIN=/app/wallet

WORKDIR /app
EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public"]
