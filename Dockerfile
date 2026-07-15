(
echo FROM php:8.3-apache
echo.
echo RUN docker-php-ext-install mysqli pdo pdo_mysql
echo.
echo COPY entrypoint.sh /entrypoint.sh
echo RUN chmod +x /entrypoint.sh
echo.
echo COPY . /var/www/html/
echo.
echo EXPOSE 80
echo.
echo ENTRYPOINT ["/entrypoint.sh"]
) > Dockerfile