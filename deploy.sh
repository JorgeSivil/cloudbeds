#!/bin/bash
if [ ! -d "mysql-storage" ]
then
  mkdir "mysql-storage"
fi
docker-compose up -d
docker-compose exec php-fpm /bin/sh -c "cd /app && composer install"