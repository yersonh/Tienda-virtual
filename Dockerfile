# Imagen con PHP + OCI8 ya incluido (evita errores de instalación)
FROM ghcr.io/gvenzl/oracle-xe-php:8.1

# Instalar dependencias necesarias para tu proyecto
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    libpng-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP usadas por tu sistema
RUN docker-php-ext-install gd zip curl xml

# Directorio de trabajo
WORKDIR /app

# Copiar todo el proyecto
COPY . .

# Copiar entrypoint (IMPORTANTE)
COPY docker-entrypoint.sh /usr/local/bin/

# Dar permisos de ejecución
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Puerto que usa Railway
EXPOSE 8080

# Ejecutar script de arranque
ENTRYPOINT ["docker-entrypoint.sh"]

# Servidor PHP
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public"]