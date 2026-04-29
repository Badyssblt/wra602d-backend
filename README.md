# wra602d-backend — Backoffice WRA602 City Builder

Backoffice Symfony 8.0 / API Platform 4 / JWT pour le jeu WRA602 City Builder. Gère utilisateurs, scores (leaderboard + partage), villes (sauvegarde / chargement de l'état complet de la grille). Communique avec le micro-service mailer (`wra602d-microservice`) via HTTP pour les notifications.

## Stack

- **Symfony 8.0** + PHP 8.4
- **API Platform 4** (REST + OpenAPI)
- **lexik/jwt-authentication-bundle** (auth stateless JWT)
- **Doctrine ORM 3.6** + PostgreSQL 16
- **Twig + Stimulus + AssetMapper** (dashboard backoffice custom)
- **Chart.js** via importmap (graphiques admin)
- **PHPUnit 13** + dama/doctrine-test-bundle (tests isolés en transaction)
- **PHPStan** + **PHP-CS-Fixer** + **GitHub Actions** (qualité + CI)

## Prérequis

- PHP 8.4 + extensions ctype, intl, pdo_pgsql, mbstring
- Composer 2
- Docker + Docker Compose
- OpenSSL (pour les clés JWT)
- Symfony CLI (recommandé pour `symfony serve`)

## Installation

```bash
git clone <repo> && cd wra602d-backend
composer install
docker compose up -d                        # Postgres + Mailpit
cp .env .env.local                          # ou édite .env.local existant
php bin/console lexik:jwt:generate-keypair --skip-if-exists
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n   # 1 admin + 5 joueurs avec villes/scores
symfony serve -d --port=8000
```

> **Note** : la première migration n'est générée qu'une fois (`doctrine:migrations:diff`). Ensuite on commit le fichier dans `migrations/`.

## Variables d'environnement

| Var | Rôle | Exemple |
|---|---|---|
| `DATABASE_URL` | DSN Postgres | `postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16` |
| `MAILER_DSN` | Mailpit local (le backoffice n'envoie pas directement les mails) | `smtp://localhost:1025` |
| `MICROSERVICE_MAILER_URL` | URL du micro-service mailer | `http://localhost:8001` |
| `MICROSERVICE_MAILER_TOKEN` | Token partagé avec le micro-service | `openssl rand -hex 32` |
| `CORS_ALLOW_ORIGIN` | Regex origins front | `^https?://(localhost\|127\.0\.0\.1)(:[0-9]+)?$` |
| `FRONTEND_URL` | URL du jeu (utilisée dans certains mails) | `http://localhost:5173` |
| `JWT_SECRET_KEY` / `JWT_PUBLIC_KEY` / `JWT_PASSPHRASE` | Auto-générés par `lexik:jwt:generate-keypair` | — |

`.env.local` n'est jamais committé (`.gitignore`). Les `.pem` JWT non plus.

## Structure projet

```
src/
├── Command/           CreateAdminCommand, PurgeOldScoresCommand
├── Controller/
│   ├── Admin/         Dashboard, Users, Scores, Cities, Security (custom Twig)
│   └── Api/           MeController, ShareScoreController
├── DataFixtures/      AppFixtures
├── Doctrine/          CurrentUserExtension (filtre API par user courant)
├── Entity/            User, GameScore, City, Building (ApiResource + filters)
├── EventListener/     User/GameScore/City listeners (Doctrine prePersist/postPersist/preUpdate)
├── Notifier/          MailerNotifierInterface + HttpMailerNotifier + NullMailerNotifier
├── Repository/        Repositories enrichis (paginatedSearch, findBestScoreForUser, …)
├── State/             UserRegisterProcessor (hash password), CitySaveProcessor (upsert grille)
└── Validator/         PseudonymFormat + BuildingPositionWithinGrid (assertions custom)

templates/admin/       Dashboard Twig + Stimulus (chart_controller.js)
config/packages/       security.yaml, lexik_jwt_authentication.yaml, api_platform.yaml, framework.yaml (scoped HttpClient)
docs/                  mld.dbml, postman_collection.json
```

## Commandes

```bash
php bin/console app:create-admin alice@example.com alice -p Sup3rPass     # admin user
php bin/console app:scores:purge --days=365 --dry-run                     # nettoyage
```

## Endpoints principaux

| Méthode | URL | Auth | Description |
|---|---|---|---|
| POST | `/api/users/register` | publique | Inscription (hash password via state processor) |
| POST | `/api/login_check` | publique | Login JSON → renvoie `{token: <JWT>}` |
| GET | `/api/users/me` | Bearer | Profil de l'utilisateur courant |
| GET | `/api/users` | ROLE_ADMIN | Liste paginée + filtres |
| GET | `/api/scores/leaderboard` | publique | Top 50 |
| GET | `/api/scores/share/{shareToken}` | publique | Score partagé |
| POST | `/api/scores` | Bearer | Soumettre un score |
| GET | `/api/scores` | Bearer | Mes scores (scope par CurrentUserExtension) |
| POST | `/api/scores/{uid}/share` | Bearer (owner) | Génère un lien partageable |
| POST | `/api/cities/save` | Bearer | Sauvegarde upsert d'une ville (grille + buildings + money) |
| GET | `/api/cities` | Bearer | Mes villes |

Documentation OpenAPI auto-générée : `http://localhost:8000/api/docs`.

Filtres API Platform exposés : `score[gt]`, `score[lt]`, `population[between]`, `order[score]`, `order[createdAt]`, `user.uid`, `user.pseudonym`, `createdAt[after]`, `createdAt[before]`.

### Payload `POST /api/cities/save`

```json
{
  "uid": "01JSBT9NVDA7XVD7H9S6NVB00V",
  "name": "MyTown",
  "money": 87500,
  "gridSize": 12,
  "buildings": [
    {"type": "house",  "posX": 3, "posZ": 4},
    {"type": "office", "posX": 5, "posZ": 4}
  ]
}
```

Le processor `CitySaveProcessor` upsert par `(uid, user)` et remplace intégralement les buildings (transaction).

### Payload `POST /api/scores/{uid}/share`

Pas de body. Réponse :
```json
{"shareToken": "01JSC1A4Z3QY9G2MZX8E7P7H4D", "shareUrl": "http://localhost:8000/api/scores/share/01JSC1A4Z3QY9G2MZX8E7P7H4D"}
```

## Dashboard backoffice

URL : `http://localhost:8000/admin` (form_login firewall, ROLE_ADMIN requis).

- Dashboard : KPIs (nb users / scores / cities / score moyen), top 10, graphique 7 jours (Chart.js), inscriptions récentes.
- Utilisateurs : recherche + pagination, fiche détail, promotion ROLE_ADMIN, suppression (CSRF).
- Scores : filtres min/max/pseudo, suppression.
- Villes : liste + détail avec rendu visuel de la grille 12×12.

Implémentation 100% custom Twig + Stimulus (pas EasyAdmin).

## Tests

```bash
# Unitaires (pas de DB requise)
vendor/bin/phpunit tests/Unit

# Tests fonctionnels (nécessitent Postgres en route + base de test)
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:schema:create --env=test
vendor/bin/phpunit
```

`dama/doctrine-test-bundle` enrobe chaque test fonctionnel dans une transaction puis rollback : pas de pollution entre tests, pas besoin de purge manuelle.

Couverture :
- Validators custom (`PseudonymFormat`, `BuildingPositionWithinGrid`)
- Notifier HTTP (mocké via `MockHttpClient`)
- Commandes (`CommandTester`)
- Auth JWT (register, login, /me, 401)
- Scoring (leaderboard public, scope user, filtres, range)
- Partage de score (génération lien + accès anonyme)
- Sauvegarde ville (upsert + remplacement buildings + assertion custom 422)
- Sécurité (un user ne voit pas les ressources d'un autre, non-admin bloqué sur collection users)
- Admin Twig (login page, redirection si non-auth)

## Qualité de code

```bash
vendor/bin/phpstan analyse                              # niveau 6
vendor/bin/php-cs-fixer fix --dry-run --diff            # @Symfony + @PHP84Migration
```

CI GitHub Actions configurée dans `.github/workflows/ci.yml` (Postgres service, JWT keys, migrations, phpstan, cs-fixer, phpunit).

## Communication avec le micro-service mailer

Côté `framework.yaml` :
```yaml
http_client:
  scoped_clients:
    mailer.client:
      base_uri: '%env(MICROSERVICE_MAILER_URL)%'
      headers:
        X-Microservice-Token: '%env(MICROSERVICE_MAILER_TOKEN)%'
        Content-Type: application/json
      timeout: 5
```

Service `App\Notifier\HttpMailerNotifier` (interface `MailerNotifierInterface`) injecté dans :
- `UserListener::postPersist` → email de bienvenue à l'inscription.
- `GameScoreListener::postPersist` → notification quand un nouveau score devient le meilleur du joueur.

En test, le service est aliasé sur `NullMailerNotifier` (no-op) via `services.yaml > when@test`.

## SOLID

- **S** (SRP) : un listener Doctrine ne fait qu'une chose, un controller admin ne fait qu'un type d'action, le `CitySaveProcessor` n'a qu'un cas d'usage (upsert).
- **O** (OCP) : ajouter un type de notification = ajouter une méthode dans `MailerNotifierInterface`. Aucun listener ni controller existant ne change.
- **L** (LSP) : `NullMailerNotifier` substituable à `HttpMailerNotifier` en test sans casser le comportement.
- **I** (ISP) : interface `MailerNotifierInterface` minimale (deux méthodes ciblées) plutôt qu'un Notifier générique.
- **D** (DIP) : tous les services dépendent d'abstractions (`MailerNotifierInterface`, `EntityManagerInterface`, `Security`, `HttpClientInterface`), jamais de classes concrètes.

## Modèle de données

Voir `docs/mld.dbml` (importable dans https://dbdiagram.io). Quatre tables : `app_user`, `game_score`, `city`, `building`. Identifiants exposés en API = `uid` ULID base32, jamais l'`id` interne.

## Documentation API & collection

- OpenAPI auto à `/api/docs` (HTML Swagger UI ou export YAML : `php bin/console api:openapi:export --yaml > docs/openapi.yaml`).
- `docs/postman_collection.json` — collection prête à importer dans Postman/Insomnia.

## Auth flow type

```
POST /api/users/register {email, pseudonym, password}   → 201
POST /api/login_check    {email, password}              → 200 {token: ...}
… toutes les routes /api authentifiées : Authorization: Bearer <token>
```

Token TTL = 3600s (configurable dans `lexik_jwt_authentication.yaml`).

## Voir aussi

- Frontend : `../wra602d-frontend/`
- Micro-service mailer : `../wra602d-microservice/`
