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

# Start Apache
exec apache2-foreground
