FROM php:8.2-apache

# PHP uzantılarını yükle (curl zaten var)
RUN docker-php-ext-install mysqli && \
    a2enmod rewrite

# Tüm dosyaları Apache'nin çalışma dizinine kopyala
COPY . /var/www/html/

# İzinleri ayarla (dosyaların okunabilir olması için)
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    mkdir -p /tmp/uploads && \
    chmod 777 /tmp/uploads

# Port 80'i aç (Apache varsayılan)
EXPOSE 80
