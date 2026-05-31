FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    wrk \
    libzip-dev \
    && docker-php-ext-install sockets zip \
    && rm -rf /var/lib/apt/lists/*

RUN git config --global --add safe.directory /package

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /package
