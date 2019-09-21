# Cloudbeds Interview Test

* Download the repository.
* Run `./deploy.sh` (docker is needed)
* Run tests if you want, but tests must be run inside a container since they use the database.
* App will be available at `http://localhost:8080`

Test must be run before populating the DB with data. 

To run tests:

`docker-compose exec php-fpm /bin/bash`

`php /app/vendor/phpunit/phpunit/phpunit --configuration /app/phpunit.xml`

To clear the database:

`docker-compose stop`
 
`rm -fr mysql-storage/*`

`./deploy.sh`
