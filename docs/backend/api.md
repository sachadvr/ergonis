# API

Routes utiles.

## Auth
- `POST /api/login`
- `POST /api/register`
- `GET /api/me`

## Config et realtime
- `GET /api/config`
- `POST /api/mercure/token`

## Mailbox
- `POST /api/mailbox/test`
- `POST /api/mailbox/sync-now`

## Candidatures
- `POST /api/applications/{id}/cv-fit`
- `POST /api/job_offers/from_extension`
- Ressources API Platform auto: `applications`, `job_offers`, `recruiter_emails`, `interviews`, `follow_up_rules`, `scheduled_follow_ups`, `user_mailbox_settings`.
- `PATCH` support merge-patch JSON.

## Social login
- `GET /api/auth/google/url`
- `POST /api/auth/google/callback`
- `GET /api/auth/microsoft/url`
- `POST /api/auth/microsoft/callback`

## Doc auto
- `GET /api/doc`
