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

# Oracle Instant Client 21c (mirror público más confiable)
RUN mkdir -p /opt/oracle && cd /opt/oracle && \
    wget -q https://yum.oracle.com/repo/OracleLinux/OL7/oracle/instantclient21/x86_64/getPackage/oracle-instantclient21.11-basic-21.11.0.0.0-1.el7.x86_64.rpm && \
    wget -q https://yum.oracle.com/repo/OracleLinux/OL7/oracle/instantclient21/x86_64/getPackage/oracle-instantclient21.11-devel-21.11.0.0.0-1.el7.x86_64.rpm && \
    apt-get install -y ./oracle-instantclient21.11-basic-*.rpm ./oracle-instantclient21.11-devel-*.rpm && \
    rm *.rpm && \
    echo /usr/lib/oracle/21/client64/lib > /etc/ld.so.conf.d/oracle-instantclient.conf && \
    ldconfig

# OCI8 2.2.0 (compatible con PHP 8.0, versión estable)
RUN export LD_LIBRARY_PATH=/usr/lib/oracle/21/client64/lib && \
    pecl install oci8-2.2.0 && \
    docker-php-ext-enable oci8 && \
    php -r "extension_loaded('oci8') or die('FAIL\n');" && \
    echo "OCI8 2.2.0 OK"

# Copiar app y config
COPY . /app/
COPY php.ini /usr/local/etc/php/php.ini
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV LD_LIBRARY_PATH=/usr/lib/oracle/21/client64/lib \
    TNS_ADMIN=/app/wallet

WORKDIR /app
EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public"]
