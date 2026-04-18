# Frontend

App = Vue 3 + TypeScript.

## Pages
- `Login` / `Register` pour auth.
- `Dashboard` pour vue rapide.
- `Applications` pour kanban.
- `ApplicationDetail` pour detail candidature.
- `JobOffers` pour offres.
- `Interviews` pour rendez-vous.
- `Emails` pour mails recruteur.
- `Settings` pour config compte et mailbox.
- Routes auth: `/auth/login`, `/auth/register`, `/auth/google/callback`, `/auth/microsoft/callback`.
- Routes privees: `/`, `/applications`, `/job-offers`, `/interviews`, `/emails`, `/settings`.

## Flux UI
- Router bloque page privee si user non logge.
- `auth.store` garde token + user en `localStorage`.
- `settings.store` charge config, mailbox, relances.
- Stores parlent API via `apiClient`.
- UI ecoute aussi event `auth:unauthorized` et deconnecte.
- Login social finit par callback frontend, puis store pose token user.

## Donnees UI
- UI lit `Application`, `JobOffer`, `Interview`, `RecruiterEmail`.
- Realtime notifications passent via Mercure.
