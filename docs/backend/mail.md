# Mail

## Lire mail
- IMAP via `ImapConnectionService`.
- `MailProviderFactory` choisit provider selon config user ou env.
- Support IMAP brut, Google OAuth, Microsoft OAuth, Mailpit.

## Ecrire mail
- SMTP via `UserMailerService`.
- Relances partent depuis settings user.

## Sync
- Sync lit emails, matche candidature, sauve `RecruiterEmail`.
- Mail deja vu saute via `messageId`.

## Secrets
- `UserMailboxSettings` secrets sont chiffrés au repos avec `MAILBOX_ENCRYPTION_KEY`.
- Frontend ne recoit plus passwords/tokens en lecture.

## Dev
- Mailpit simplifie test local.
