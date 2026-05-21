# JobPlanner

Application web fullstack de gestion de candidatures à l'emploi qui centralise le suivi des offres, des échanges avec les recruteurs et des rendez-vous, avec automatisation via workflows et IA.

Instance de démo : [ergonis.app](https://ergonis.app) — compte : `guest@test.com` / `guest`.

## Stack

- Backend : Symfony 8 + API Platform 4, PHP 8.4, PostgreSQL 16, Doctrine 3.6
- Frontend : Vue 3.5 + TypeScript, Pinia 3, Vite 7, Tailwind 4
- Realtime : Mercure SSE
- Auth : JWT RS256 (LexikJWT) + OAuth 2.0 Google / Microsoft
- IA : Ollama local (par défaut) / OpenAI / Anthropic / Null
- Extension : Chrome Manifest v3
- CI/CD : GitHub Actions, Docker Hub, déploiement Portainer + Traefik

## Démarrage local

Prérequis : [Docker Desktop](https://www.docker.com/products/docker-desktop/) (ou [OrbStack](https://orbstack.dev/) en alternative), [Git](https://git-scm.com/downloads). [Ollama](https://ollama.com/download) installé sur l'hôte si IA locale souhaitée.

```bash
git clone https://github.com/sachadvr/ergonis && cd ergonis
cp .env.example jobplanner-api/.env
# Éditer jobplanner-api/.env : remplacer MAILBOX_ENCRYPTION_KEY par une chaîne aléatoire >= 32 chars
docker compose up -d
docker compose exec api php bin/console doctrine:migrations:migrate
docker compose exec api php bin/console doctrine:fixtures:load
```

Services : Frontend `http://localhost:5173` / API `http://localhost:8000` / Mailpit `http://localhost:8025`.

## Configuration OAuth (optionnel)

Sans clés OAuth, sync Gmail/Outlook désactivés ; IMAP générique, Ollama et compte démo restent fonctionnels.

### Google (Gmail)

[console.cloud.google.com](https://console.cloud.google.com) → APIs & Services → Credentials → OAuth 2.0 Client ID

| Champ | Valeur |
|---|---|
| Redirect URI | `http://localhost:8000/api/auth/google/callback` |
| Scopes | `gmail.readonly`, `https://mail.google.com/` |
| Variables `.env` | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` |

### Microsoft (Outlook)

[portal.azure.com](https://portal.azure.com) → App registrations → New → Certificates & secrets

| Champ | Valeur |
|---|---|
| Redirect URI | `http://localhost:8000/api/auth/microsoft/callback` |
| Permissions | `Mail.Read`, `offline_access` |
| Variables `.env` | `AZURE_CLIENT_ID`, `AZURE_CLIENT_SECRET` |

## Configuration IA

Provider sélectionnable via `AI_PROVIDER` (`ollama` par défaut).

### Ollama local (gratuit, aucun token)

Installer depuis [ollama.com], puis :

```bash
ollama pull bjoernb/gemma4-31b-think:latest
ollama run bjoernb/gemma4-31b-think:latest
```

Ollama tourne sur l'hôte (pas dans Docker), accédé depuis les containers via `host.docker.internal:11434`.

### OpenAI / Anthropic

`.env` :
```
AI_PROVIDER=openai
OPENAI_API_KEY=sk-...
# ou
AI_PROVIDER=anthropic
ANTHROPIC_API_KEY=sk-ant-...
```

## Documentation

Docs complètes : [docs.ergonis.app](https://docs.ergonis.app/) (architecture, modèle données, sécurité, flux, CI, AI).

Sources sous `docs/`.

## Tests

```bash
# Backend (PHPUnit 13)
docker compose exec api php vendor/bin/phpunit

# Frontend (Playwright)
make e2e
```

Suite actuelle : 87 tests PHPUnit + 9 tests Playwright. Coverage lignes backend ≈ 46%.

## Production

Déploiement sur `compose.prod.yaml` (Traefik + Let's Encrypt). Images Docker Hub : `sachadvr/jobplanner-api`, `sachadvr/jobplanner-frontend`. Push CI déclenche un webhook Portainer.

## Licence

[AGPL-3.0](LICENSE). Tout hébergeur d'une version modifiée doit publier ses modifications sous la même licence.
