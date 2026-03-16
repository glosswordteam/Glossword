#!/bin/bash
 set -e
 chmod -R 777 /var/www/html/gw_temp 2>/dev/null || true
 chmod -R 777 /var/www/html/gw_install/temp 2>/dev/null || true
 chmod -R 777 /var/www/html/db_config.php 2>/dev/null || true
 exec apache2-foreground
