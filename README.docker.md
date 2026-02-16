# JobPlanner - Docker

## Démarrage

```bash
# Depuis la racine du projet
docker compose up -d --build

# Ou avec les variables d'environnement
docker compose --env-file .env.docker up -d --build
```

## Mode développement (hot reload)

Pour le hot reload du frontend avec Vite :

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.dev.yml up -d
```

- **Frontend** : http://localhost:5173 (Vite + HMR)
- **API** : http://localhost:8000
- Le code source est monté, les changements sont pris en compte immédiatement.

## Services

| Service   | Port | Description                    |
|-----------|------|--------------------------------|
| API       | 8000 | Backend Symfony                |
| Frontend  | 80   | Application Vue.js             |
| Database  | 5432 | PostgreSQL 16                  |
| Mailpit   | 1025 | SMTP (envoi emails)            |
| Mailpit   | 8025 | Interface web (visualisation)  |

## Mailpit

Mailpit intercepte tous les emails envoyés par l'application (relances, notifications, etc.).

- **Interface web** : http://localhost:8025
- **SMTP** : localhost:1025 (utilisé automatiquement par l'API)

L'API est configurée avec `MAILER_DSN=smtp://mailpit:1025`.

## Première exécution

1. Les clés JWT sont générées automatiquement lors du build si elles n'existent pas.

2. Exécuter les migrations :
   ```bash
   docker compose exec api php bin/console doctrine:migrations:migrate --no-interaction
   ```

3. Charger les fixtures (optionnel, dev) :
   ```bash
   docker compose exec api php bin/console doctrine:fixtures:load --no-interaction
   ```

## URLs

- Frontend : http://localhost
- API : http://localhost:8000
- Documentation API : http://localhost:8000/api/doc
- Mailpit : http://localhost:8025
