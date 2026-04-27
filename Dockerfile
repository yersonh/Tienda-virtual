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
RUN docker-php-ext-install pdo curl xml mbstring

# Crear directorio Oracle
RUN mkdir -p /opt/oracle

# Descargar Oracle Instant Client
RUN cd /tmp && \
    wget --timeout=30 -O instantclient-basiclite.zip https://download.oracle.com/otn_software/linux/instantclient/instantclient-basiclite-linuxx64.zip && \
    wget --timeout=30 -O instantclient-sdk.zip https://download.oracle.com/otn_software/linux/instantclient/instantclient-sdk-linuxx64.zip && \
    unzip -q instantclient-basiclite.zip && \
    unzip -q instantclient-sdk.zip && \
    mv instantclient_*/* /opt/oracle/ && \
    rm -rf instantclient* && \
    echo /opt/oracle > /etc/ld.so.conf.d/oracle.conf && \
    ldconfig

# Variables de entorno Oracle
ENV LD_LIBRARY_PATH=/opt/oracle
ENV ORACLE_HOME=/opt/oracle

# Instalar OCI8
RUN echo 'instantclient,/opt/oracle' | pecl install oci8-3.2.1 && \
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
