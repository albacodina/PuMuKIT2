ARG PHP_VERSION=7.2

FROM php:${PHP_VERSION}-fpm-alpine
MAINTAINER Pablo Nieto, pnieto@teltek.es

ARG APCU_VERSION=5.1.12
ARG PHP_MONGODB_VERSION=1.5.3
ARG XDEBUG_VERSION=2.6.1

RUN apk  add --no-cache \
    	     		--update \
	     		libgcc \
			libstdc++ \
			ca-certificates \
			libcrypto1.0 \
			libssl1.0 \
			libgomp \
			expat \
;

COPY --from=jrottenberg/ffmpeg:4.0-alpine /usr/local /usr/local


RUN apk add --no-cache \
		acl \
		file \
		gettext \
		git \
;

RUN set -eux; \
 	apk add --no-cache --virtual .build-deps \
 		$PHPIZE_DEPS \
 		icu-dev \
		openldap-dev \
		gettext-dev \
		libpng-dev \
		expat-dev \
 		libzip-dev \
 		zlib-dev \
		libxml2-dev \
		libxslt-dev \
 	; \
 	\
 	docker-php-ext-configure zip --with-libzip; \
 	docker-php-ext-install -j$(nproc) \
 		intl \
		exif \
		intl \
		ldap \
		gettext \
		pcntl \
		gd \
		shmop \
		sockets \
		sysvmsg \
		sysvsem \
		sysvshm \
 		zip \
		wddx \
		xsl \
 	; \
 	pecl install \
 		apcu-${APCU_VERSION} \
		mongodb-${PHP_MONGODB_VERSION} \
		xdebug-${XDEBUG_VERSION} \
 	; \
 	pecl clear-cache ;\
 	docker-php-ext-enable \
 		apcu \
 		opcache \
		mongodb \
 	; \
	\
	runDeps="$( \
            scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
                | tr ',' '\n' \
                | sort -u \
                | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
            )"; \
	apk add --no-cache --virtual .api-phpexts-rundeps $runDeps; \
	\
	apk del .build-deps


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY doc/docker/pumukit/php.ini /usr/local/etc/php/php.ini

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN set -eux; \
    composer global require "rubenrua/symfony-clean-tags-composer-plugin" --prefer-dist --no-progress --no-suggest --classmap-authoritative; \
    composer clear-cache
    
ENV PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /srv/pumukit

# default build for production
ARG APP_ENV=prod

# copy only specifically what we need
COPY --chown=www-data:www-data app app/
COPY --chown=www-data:www-data bin bin/
COPY --chown=www-data:www-data doc doc/
COPY --chown=www-data:www-data web web/
COPY --chown=www-data:www-data src src/
COPY --chown=www-data:www-data composer.json ./
COPY --chown=www-data:www-data composer.lock ./
COPY --chown=www-data:www-data doc/docker/pumukit/parameters.yml app/config/parameters.yml

RUN set -eux; \
    composer install -a -n --no-scripts; \
    php app/console a:i; \
    composer clear-cache

COPY doc/docker/pumukit/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

## Add the wait script to the image
ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.4.0/wait /wait
RUN chmod +x /wait

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

