#!/bin/bash
set -e

# Remove all MPM module configurations
rm -f /etc/apache2/mods-enabled/mpm_*.load
rm -f /etc/apache2/mods-enabled/mpm_*.conf

# Enable only mpm_prefork
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# Ensure moodledata directory exists and has correct permissions
mkdir -p /app/moodledata
chown -R www-data:www-data /app/moodledata
chmod -R 0777 /app/moodledata

# Create and set permissions for Moodle cache and temp directories
mkdir -p /var/www/html/cache
mkdir -p /var/www/html/localcache
mkdir -p /var/www/html/temp
mkdir -p /var/www/html/cache/classes

chown -R www-data:www-data /var/www/html/cache
chown -R www-data:www-data /var/www/html/localcache
chown -R www-data:www-data /var/www/html/temp

chmod -R 0777 /var/www/html/cache
chmod -R 0777 /var/www/html/localcache
chmod -R 0777 /var/www/html/temp

# Start Apache
exec apache2-foreground
