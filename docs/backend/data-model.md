# Data Model

## User
- Compte candidat.
- Base auth et owner partout.

## JobOffer
- Offre brute ou enrichie.
- Porte titre, company, url, notes, summary, contact recruteur.

## Application
- Lien `User` + `JobOffer`.
- Porte statut, position pipeline, date depot, activite.
- Contient history, mails recruteur, relances, interviews.

## RecruiterEmail
- Mail entrant lie a candidature.
- Unique par `messageId` + `owner`.
- Garde body, summary IA, flags mail.

## FollowUpRule
- Regle user pour relance auto.
- `daysWithoutReply`, template, enabled.

## ScheduledFollowUp
- Relance planifiee.
- Status: `pending`, `sent`, `cancelled`.
- Peut garder contenu genere.

## Interview
- Rendez-vous sur candidature.
- Type: visio, tel, presentiel.
- Flag rappel envoye.

## Mailbox settings
- IMAP + SMTP par user.
- Peut porter OAuth token et date sync.
