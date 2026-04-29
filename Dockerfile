# Symfony 8.0 / PHP 8.4 — image FrankenPHP (Caddy + PHP-FPM bundled, HTTP/2)
FROM dunglas/frankenphp:1-php8.4

ENV SERVER_NAME=":80"
WORKDIR /app

# Extensions PHP requises par le projet
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libicu-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pdo_pgsql intl zip opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Cache layer : on installe les deps avant de copier la source
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

# Copie de la source
COPY . .
RUN composer run-script post-install-cmd --no-interaction || true

# Entrypoint: génère JWT + attend la BDD + migre, puis lance FrankenPHP
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
