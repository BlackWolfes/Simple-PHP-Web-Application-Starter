# Dockerfile para Simple PHP Web Application
FROM php:8.1-apache

# Instalar extensiones PHP necesarias para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite de Apache para URLs amigables
RUN a2enmod rewrite

# Instalar herramientas adicionales para debugging y conectividad
RUN apt-get update && apt-get install -y \
    iputils-ping \
    telnet \
    mariadb-client \
    && rm -rf /var/lib/apt/lists/*

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar código fuente al contenedor
COPY ../public /var/www/html/
COPY ../app /var/www/html/app/
COPY ../config /var/www/html/config/
COPY ../database /var/www/html/database/
COPY ../storage /var/www/html/storage/

# Crear directorio de logs con permisos adecuados
RUN mkdir -p /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage/logs \
    && chmod -R 777 /var/www/html/storage/logs

# Configurar permisos para el directorio web
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configurar Apache para permitir .htaccess
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/webapp.conf \
    && a2enconf webapp

# Exponer puerto 80 para el servidor web
EXPOSE 80

# Comando por defecto para iniciar Apache
CMD ["apache2-foreground"]