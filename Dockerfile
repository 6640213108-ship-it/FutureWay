FROM php:8.3-apache

# แก้ปัญหา MPM conflict - บังคับใช้ prefork เท่านั้น (จำเป็นสำหรับ mod_php)
RUN a2dismod mpm_event mpm_worker 2>/dev/null; \
    a2enmod mpm_prefork

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY . /var/www/html/

EXPOSE 80