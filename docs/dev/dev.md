# Dev

## Stack locale
- `compose.yaml` lance `database`, `mailpit`, `mercure`, `api`, `worker`, `scheduler`, `frontend`.
- `worker` consomme `async`.
- `scheduler` consomme `scheduler_default`.

## Role services
- `api` sert backend Symfony.
- `worker` consomme messages async.
- `scheduler` consomme taches planifiees.
- `frontend` lance Vue dev server.
- `mailpit` capture mails dev.
- `mercure` pousse event temps reel.

## Commandes
- `make build` : build images front + back.
- `make release` : build + push images.
- `make csfix` : PHP CS Fixer.

## Points sensibles
- API a besoin DB, JWT, mail, et variable IA.
- Sans Mailpit ou SMTP valide, tests mail peuvent casser.
- Hors Docker, `mailpit` host resolu pas.
- Ollama tourne sur l'hote (pas dans compose), accede via `host.docker.internal:11434`.
- `MAILBOX_ENCRYPTION_KEY` placeholder dans `.env.example`, remplacer par chaine aleatoire >= 32 chars.
