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

## Tests
- En test, hash password moins cher.
