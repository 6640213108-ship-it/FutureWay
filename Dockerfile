FROM php:8.3-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# เปิด mod_headers — image นี้ไม่ได้เปิดมาให้
# ถ้าไม่เปิด บล็อก <IfModule mod_headers.c> ใน .htaccess จะถูกข้ามทั้งก้อนแบบเงียบ ๆ
# (ไม่ error ไม่เตือนอะไรเลย) กฎ no-cache ที่เขียนไว้จึงไม่เคยมีผลจริง
# ผลคือ deploy โค้ดใหม่ขึ้นไปแล้ว เบราว์เซอร์ยังใช้ HTML/CSS ตัวเก่าที่ cache ไว้
RUN a2enmod headers

# ติดตั้ง Python ก่อน COPY โค้ด เพื่อให้ Docker cache layer นี้ไว้
# ไม่ต้องติดตั้งซ้ำทุกครั้งที่แก้โค้ด PHP/HTML
RUN apt-get update && apt-get install -y python3 python3-pip \
    && pip3 install --break-system-packages mysql-connector-python \
    && rm -rf /var/lib/apt/lists/*

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

COPY . /var/www/html/

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
