# Imagen base con PHP y Apache
FROM php:8.2-apache

# Instalar extensiones necesarias de PHP (como mysqli para MySQL)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copiar archivos del proyecto al contenedor
COPY . /var/www/html/

# Establecer el directorio de trabajo
WORKDIR /var/www/html/

# Configurar Apache para permitir .htaccess
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf || true

# Exponer el puerto 80 (HTTP)
EXPOSE 80
