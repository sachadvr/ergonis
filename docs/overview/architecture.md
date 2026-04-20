# Architecture

JobPlanner tourne en 3 blocs.

## Frontend
- Vue 3 + TypeScript.
- Affiche candidatures, mails, suivis, config.

## Backend
- Symfony 8.
- API Platform pour ressources API.
- JWT pour auth.
- Mercure pour push temps reel.
- Messenger pour traitement async.

[Data model](../backend/data-model.md)

## Infra locale
- PostgreSQL.
- Mailpit pour mail dev.
- Ollama, OpenAI, ou Anthropic pour IA.
