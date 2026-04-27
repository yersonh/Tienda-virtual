FROM php:8.1-cli

# Dependencias
RUN apt-get update && apt-get install -y \
    unzip \
    curl \
    libaio-dev \
    libzip-dev \
    libpng-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-install gd zip curl xml

# 📦 Oracle Instant Client (mirror que sí descarga en Railway)
RUN mkdir -p /opt/oracle && \
    cd /opt/oracle && \
    curl -L -o instantclient.zip https://github.com/gharriso/oracle-instantclient/raw/master/instantclient-basiclite-linux.x64-19.8.0.0.0dbru.zip && \
    unzip instantclient.zip && \
    rm instantclient.zip && \
    echo /opt/oracle/instantclient_19_8 > /etc/ld.so.conf.d/oracle.conf && \
    ldconfig

# Variable necesaria
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_19_8

# Instalar OCI8
RUN echo "instantclient,/opt/oracle/instantclient_19_8" | pecl install oci8 \
    && docker-php-ext-enable oci8

# App
WORKDIR /app
COPY . .

# EntryPoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public"]