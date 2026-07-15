#!/bin/bash
set -e
echo "=== Checking MPM modules ==="
ls -la /etc/apache2/mods-enabled/ | grep -i mpm
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf
echo "=== After fix ==="
ls -la /etc/apache2/mods-enabled/ | grep -i mpm
echo "=== Setting PORT to ${PORT:-80} ==="
# ใช้ ^Listen .*$ แทน "Listen 80" ตรงๆ เพื่อให้ idempotent
# ไม่ว่า container จะ restart กี่ครั้ง ก็จะ set ค่าตรงตาม $PORT เสมอ
# ไม่ใช่ไป match ทับค่าที่เคยแก้ไปแล้ว (เช่น "Listen 8080" ที่ดันมี "Listen 80" ซ้อนอยู่ข้างใน)
sed -i "s/^Listen .*/Listen ${PORT:-80}/" /etc/apache2/ports.conf

# เช่นเดียวกัน match เฉพาะ pattern ทั้งก้อน *:PORT> กันไม่ให้ไป match ทับค่าที่แก้ไปแล้ว
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf

echo "=== ports.conf now ==="
cat /etc/apache2/ports.conf
echo "=== 000-default.conf VirtualHost line ==="
grep VirtualHost /etc/apache2/sites-available/000-default.conf

exec apache2-foreground