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

# Check if Moodle is already installed by checking if config table exists
INSTALLED=$(php -r "
\$host = getenv('MYSQLHOST');
\$db = getenv('MYSQLDATABASE');
\$user = getenv('MYSQLUSER');
\$pass = getenv('MYSQLPASSWORD');
\$port = getenv('MYSQLPORT') ?: '3306';

try {
    \$conn = new mysqli(\$host, \$user, \$pass, \$db, \$port);
    if (\$conn->connect_error) {
        echo 'no';
        exit;
    }
    \$result = \$conn->query(\"SHOW TABLES LIKE 'mdl_config'\");
    echo (\$result && \$result->num_rows > 0) ? 'yes' : 'no';
    \$conn->close();
} catch (Exception \$e) {
    echo 'no';
}
" 2>/dev/null || echo "no")

if [ "$INSTALLED" = "no" ]; then
    echo "Moodle not installed. Running CLI installer..."
    
    # Run Moodle CLI installer as www-data user
    su -s /bin/bash www-data -c "php /var/www/html/admin/cli/install_database.php \
        --agree-license \
        --adminpass=Admin123! \
        --adminemail=admin@example.com \
        --fullname='Moodle Site' \
        --shortname='Moodle'" || echo "Installation may have already been completed or failed"
fi

# Start Apache
exec apache2-foreground
