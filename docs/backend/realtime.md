# Realtime

Mercure pousse notif live.

## Ce qui part
- mail recruteur entrant
- import depuis extension

## Flow
- Backend fabrique payload notif.
- Backend signe JWT Mercure.
- Publie vers hub.
- Frontend recoit event et met UI a jour.

## Auth
- Token subscribe donne par `POST /api/mercure/token`.
- Cookie scope sur `/.well-known/mercure`.
