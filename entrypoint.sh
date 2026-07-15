(
echo #!/bin/bash
echo set -e
echo echo "=== Checking MPM modules ==="
echo ls -la /etc/apache2/mods-enabled/ ^| grep -i mpm
echo rm -f /etc/apache2/mods-enabled/mpm_event.load
echo rm -f /etc/apache2/mods-enabled/mpm_event.conf
echo rm -f /etc/apache2/mods-enabled/mpm_worker.load
echo rm -f /etc/apache2/mods-enabled/mpm_worker.conf
echo ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
echo ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf
echo echo "=== After fix ==="
echo ls -la /etc/apache2/mods-enabled/ ^| grep -i mpm
echo exec apache2-foreground
) > entrypoint.sh