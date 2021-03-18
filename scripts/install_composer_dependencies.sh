#!/bin/bash
cd /var/www/html/moodle/web
yum install wget -y
wget https://getcomposer.org/composer.phar
php composer.phar install
php admin/cli purge_caches.php