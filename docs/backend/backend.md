# Backend

Backend = Symfony 8 + API Platform.

## Idée
- Entites exposent API direct.
- `OwnedEntityProcessor` rattache objet a user courant.
- Services font logique brute.
- Messenger prend taches longues.
- Mercure pousse event temps reel.

## API Platform
- Resources clefs: `JobOffer`, `Application`, `RecruiterEmail`, `Interview`, `FollowUpRule`, `ScheduledFollowUp`, `UserMailboxSettings`.
- `GET`, `GET collection`, `POST`, `PUT`, `PATCH`, `DELETE` selon resource.
- `Application`, `JobOffer`, `FollowUpRule`, `UserMailboxSettings` passent par processor owner.

## Services forts
- `EmailSyncProcessor` lit mails, matche candidature, sauve trace, annule relances.
- `FollowUpPlanningService` cree relances selon regles user.
- `FollowUpProcessorService` genere ou envoie mail relance.
- `ApplicationCvFitService` extrait texte PDF puis appelle IA.
- `JobOfferFromExtensionService` transforme offre extension en donnees metier.

## IA
- Provider choisi par `AI_PROVIDER`.
- Support: `openai`, `anthropic`, `ollama`, `null`.
- `NullAiService` coupe IA quand rien configure.

## Mail
- IMAP pour lire mail entrant.
- SMTP pour envoyer mail sortant.
- OAuth supporte Google et Microsoft.

## Async
- Transport `async` = doctrine queue `async`.
- Messages routés async: sync mail, sync all mailboxes, follow-ups, interview reminders, extension offers, CV fit.
- Scheduler lance sync mailboxes toutes 5 min, follow-ups toutes 30 min, reminders interview toutes 1 h.
