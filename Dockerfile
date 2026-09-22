FROM php:8.3-fpm

ARG UID=1000
ARG GID=1000

RUN groupadd -g ${GID} appuser && \
    useradd -u ${UID} -g ${GID} -s /bin/bash -m appuser

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    default-mysql-client \
    libicu-dev \
    libpng-dev \
    && docker-php-ext-install pdo_mysql mbstring bcmath intl zip gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

USER appuser
