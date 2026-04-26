FROM php:8.1-apache

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
RUN docker-php-ext-install \
    gd \
    zip \
    curl \
    xml

# Descargar Oracle Instant Client 21.x (Basic Lite)
RUN mkdir -p /opt/oracle && \
    cd /opt/oracle && \
    wget -q "https://download.oracle.com/otn_software/linux/instantclient/2110000/instantclient-basiclite-linux.x64-21.10.0.0.0dbru.zip" \
        -O ic.zip && \
    unzip ic.zip && \
    rm ic.zip && \
    echo /opt/oracle/instantclient_21_10 > /etc/ld.so.conf.d/oracle-instantclient.conf && \
    ldconfig

# Compilar e instalar PDO_OCI usando el Instant Client descargado
RUN docker-php-ext-configure pdo_oci \
        --with-pdo-oci=instantclient,/opt/oracle/instantclient_21_10 && \
    docker-php-ext-install pdo_oci

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar Apache: DocumentRoot apunta a /var/www/html/public
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Copiar código de la aplicación
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 700 /var/www/html/wallet

# Variables de entorno Oracle
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_21_10 \
    TNS_ADMIN=/var/www/html/wallet

EXPOSE 80

CMD ["apache2-foreground"]
