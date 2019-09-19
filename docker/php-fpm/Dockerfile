FROM phpdockerio/php73-fpm:latest
WORKDIR "/app"

# Fix debconf warnings upon build
ARG DEBIAN_FRONTEND=noninteractive

# Install selected extensions and other stuff
RUN apt-get update \
    && apt-get -y --no-install-recommends install  php7.3-mysql php-xdebug \
    && apt-get clean; rm -rf /tmp/* /var/tmp/* /usr/share/doc/*
