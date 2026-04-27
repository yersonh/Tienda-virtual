FROM oraclelinux:8-slim

# Instalar PHP 8.1 y extensiones
RUN yum install -y \
    php81-cli \
    php81-common \
    php81-pdo \
    php81-gd \
    php81-curl \
    php81-xml \
    php81-zip \
    && yum clean all

# Oracle Instant Client - en OracleLinux está optimizado
RUN yum install -y \
    oracle-instantclient-release-el8 \
    && yum-config-manager --enable ol8_oracle_instantclient \
    && yum install -y oracle-instantclient-basic oracle-instantclient-devel \
    && yum clean all

# Instalar oci8 directo (Oracle Linux lo soporta nativamente)
RUN /usr/bin/pecl install -f oci8 \
    && echo "extension=oci8.so" > /etc/php.ini.d/20-oci8.ini

# Verificar que oci8 carga
RUN /usr/bin/php -r "extension_loaded('oci8') or die('ERROR: oci8 no carga\n');" && echo "=== oci8 OK ==="

COPY . /app/

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV TNS_ADMIN=/app/wallet \
    LD_LIBRARY_PATH=/usr/lib/oracle/21/client64/lib:$LD_LIBRARY_PATH

WORKDIR /app

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["/usr/bin/php", "-S", "0.0.0.0:8080", "-t", "/app/public"]
