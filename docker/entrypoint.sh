#!/usr/bin/env sh
set -e

cd /app

# 1) JWT keys (idempotent)
if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
    echo "[entrypoint] Génération du keypair JWT…"
    php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
fi

# 2) S'assurer que vendor/ existe (cas où on monte un volume vide en dev)
if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] Installation des dépendances Composer…"
    composer install --no-interaction --prefer-dist --no-progress
fi

# 3) Attente de la BDD (parse DATABASE_URL)
echo "[entrypoint] Attente de la base de données…"
until php -r '
    $url = parse_url(getenv("DATABASE_URL"));
    if (!$url) exit(1);
    try {
        new PDO(
            "pgsql:host=" . $url["host"] . ";port=" . ($url["port"] ?? 5432) . ";dbname=" . ltrim($url["path"] ?? "/app", "/"),
            $url["user"] ?? "app",
            $url["pass"] ?? "!ChangeMe!"
        );
        exit(0);
    } catch (Throwable $e) { exit(1); }
' 2>/dev/null; do
    sleep 1
done
echo "[entrypoint] Base prête."

# 4) Schéma : migrations si présentes, sinon création directe (premier boot)
if ls migrations/Version*.php >/dev/null 2>&1; then
    php bin/console doctrine:database:create --if-not-exists --no-interaction || true
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
else
    php bin/console doctrine:database:create --if-not-exists --no-interaction || true
    php bin/console doctrine:schema:update --force --complete --no-interaction || true
fi

# 5) Cache warmup
php bin/console cache:clear --no-interaction || true

exec "$@"
