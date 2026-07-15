FROM php:8.3-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# ลบ symlink ของ mpm_event และ mpm_worker ออกโดยตรง เหลือแค่ mpm_prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
           /etc/apache2/mods-enabled/mpm_event.conf \
           /etc/apache2/mods-enabled/mpm_worker.load \
           /etc/apache2/mods-enabled/mpm_worker.conf \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

COPY . /var/www/html/

EXPOSE 80
