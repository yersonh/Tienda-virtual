FROM php:8.1-cli

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    unzip curl wget \
    libaio1 libaio-dev \
    libzip-dev libpng-dev libcurl4-openssl-dev libxml2-dev \
    git \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-install gd zip curl xml mbstring pdo

# 📦 Oracle Instant Client - Instalación desde repositorio de confianza
RUN cd /tmp && \
    wget -q https://download.oracle.com/otn_software/linux/instantclient/198000/instantclient-basiclite-linux.x64-19.8.0.0.0dbru.zip && \
    unzip -q instantclient-basiclite-linux.x64-19.8.0.0.0dbru.zip && \
    mkdir -p /opt/oracle && \
    mv instantclient_19_8 /opt/oracle/ && \
    rm instantclient-basiclite-linux.x64-19.8.0.0.0dbru.zip && \
    echo /opt/oracle/instantclient_19_8 > /etc/ld.so.conf.d/oracle.conf && \
    ldconfig

# Variable de entorno para Oracle
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_19_8:$LD_LIBRARY_PATH
ENV ORACLE_HOME=/opt/oracle/instantclient_19_8

# Instalar OCI8
RUN echo "instantclient,/opt/oracle/instantclient_19_8" | pecl install oci8 && \
    docker-php-ext-enable oci8

# App
WORKDIR /app
COPY . .

# EntryPoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public"]