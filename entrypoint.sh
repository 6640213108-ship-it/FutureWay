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
exec apache2-foreground