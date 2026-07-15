FROM php:8.3-apache

ARG CACHEBUST=1

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
           /etc/apache2/mods-enabled/mpm_event.conf \
           /etc/apache2/mods-enabled/mpm_worker.load \
           /etc/apache2/mods-enabled/mpm_worker.conf \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

RUN apache2ctl configtest

COPY . /var/www/html/

EXPOSE 80
CMD sh -c "sed -i \"s/Listen 80/Listen \${PORT:-80}/\" /etc/apache2/ports.conf && \
           sed -i \"s/:80>/:\${PORT:-80}>/\" /etc/apache2/sites-available/000-default.conf && \
           apache2ctl configtest && \
           apache2-foreground"
