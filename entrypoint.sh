#!/bin/bash
# ========================================
# FutureWay - entrypoint.sh
# ปรับ Apache ใน image php:8.3-apache ให้พร้อมสำหรับ Railway ก่อนเริ่มทำงาน
#   1) ใช้ mpm_prefork (mod_php ต้องการ) แทน mpm_event ที่ image เปิดมาให้
#   2) ฟังพอร์ตตาม $PORT ที่ Railway กำหนด (ค่าเริ่มต้น 80)
#   3) เปิด AllowOverride ให้ .htaccess มีผล
# ตั้ง DEBUG_ENTRYPOINT=1 ถ้าอยากเห็นค่าที่ตั้งไว้ใน log ตอนบูต
# ========================================
set -euo pipefail

PORT="${PORT:-80}"

log() {
  if [ -n "${DEBUG_ENTRYPOINT:-}" ]; then
    echo "[entrypoint] $*"
  fi
}

# ---- 1) MPM ----
rm -f /etc/apache2/mods-enabled/mpm_event.load  /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# ---- 2) พอร์ต ----
# match ทั้งบรรทัด/ทั้ง pattern เพื่อให้ idempotent: restart กี่ครั้งก็ได้ค่าตาม $PORT เสมอ
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# ---- 3) .htaccess ----
sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

log "listening on port ${PORT}"
log "$(ls /etc/apache2/mods-enabled/ | grep -i mpm | tr '\n' ' ')"

exec apache2-foreground
