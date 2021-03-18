#!/bin/bash
cd /var/www/html/moodle/web
composer install
php admin/cli purge_caches.php