#!/bin/sh

rm -rf /var/www/html/wp-content/plugins/akismet
rm -f /var/www/html/wp-content/plugins/hello.php

# Display version
wp cli version

if ! wp core is-installed; then
    wp core install --url='localhost' --title='Custom Theme' --admin_user='admin' --admin_password='admin' --admin_email='admin@example.com'
fi

# Install Wordpress
wp language core install ru_RU
wp language core activate ru_RU

# Setup rewrite rules
wp rewrite structure '%postname%/'
wp rewrite flush --hard

# Template
wp theme activate adv-adm-pan

echo "php_value upload_max_filesize 2G" >> /var/www/html/.htaccess
echo "php_value post_max_size 2G" >> /var/www/html/.htaccess
echo "php_value memory_limit 256M" >> /var/www/html/.htaccess