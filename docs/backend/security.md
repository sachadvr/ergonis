# Security

## Auth
- Login via JWT.
- `POST /api/login` est faux handler; authentifier coupe avant controller.
- `GET /api/me` confirme session cote frontend.

## Access
- `PUBLIC_ACCESS` pour login, register, auth social, doc.
- Reste `/api` demande `ROLE_USER`.

## Mercure
- `POST /api/mercure/token` donne cookie subscribe.
- Topic user = `urn:jobplanner:user:{id}:notifications`.

# UserMailboxSettings
- `UserMailboxSettings` secrets sont chiffrés au repos avec `MAILBOX_ENCRYPTION_KEY`.
- Frontend ne recoit plus passwords/tokens en lecture.

## Tests
- Des tests e2e coté front & Tests phpunit coté api sont présents afin de tester le projet
