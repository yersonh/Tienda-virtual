FROM oraclelinux:8-slim

# Dependencias y Oracle Instant Client (preinstalado en esta imagen)
RUN microdnf install -y \
    php php-cli php-pdo php-gd php-zip php-curl php-xml \
    unzip curl libaio libzip-devel \
    && microdnf clean all

# Instalar extensión OCI8
RUN echo "instantclient,/usr/lib/oracle/19.8/client64/lib" | pecl install oci8 \
    && docker-php-ext-enable oci8

# Variable de entorno para Oracle
ENV LD_LIBRARY_PATH=/usr/lib/oracle/19.8/client64/lib

# App
WORKDIR /app
COPY . .

# EntryPoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public"]