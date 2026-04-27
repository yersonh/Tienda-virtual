FROM php:8.1-cli

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    unzip \
    curl \
    libaio-dev \
    libzip-dev \
    libpng-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    git \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-install gd zip curl xml

# 🔥 Instalar Oracle Instant Client desde repositorio válido
RUN mkdir -p /opt/oracle && \
    cd /opt/oracle && \
    curl -L -o instantclient.zip https://download.oracle.com/otn_software/linux/instantclient/instantclient-basiclite-linux.x64-21.10.0.0.0dbru.zip && \
    unzip instantclient.zip && \
    rm instantclient.zip && \
    echo /opt/oracle/instantclient_21_10 > /etc/ld.so.conf.d/oracle.conf && \
    ldconfig

# 🔥 Variable necesaria
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_21_10

# 🔥 Instalar OCI8 correctamente
RUN echo "instantclient,/opt/oracle/instantclient_21_10" | pecl install oci8 \
    && docker-php-ext-enable oci8

# Proyecto
WORKDIR /app
COPY . .

# EntryPoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public"]