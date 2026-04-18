# Flux metier

## 1. Nouvelle offre
- Extension prend offre depuis page web.
- Backend cree `JobOffer`.
- Si user lie, cree `Application`.

## 2. Mail recruteur
- Sync mailbox lit mails entrant.
- Match mail avec `Application`.
- Sauve `RecruiterEmail`.
- Ajoute `ApplicationHistory`.
- Annule relances en attente.
- Si mail deja vu, skip via `messageId`.

## 3. Relances
- `FollowUpRule` definit delai apres candidature.
- `FollowUpPlanningService` cree `ScheduledFollowUp`.
- `FollowUpProcessorService` genere mail via IA, ou reuse texte deja present.
- Si mail impossible, relance annulee.
- Scheduler traite ca par lot, pas a chaque requete user.

## 4. CV fit
- User envoie PDF CV.
- Backend extrait texte.
- IA renvoie score + points forts/faibles.
- Reponse contient resume court + analyse.

## 5. Rendez-vous
- Commande/handler envoie rappels interview.
- Notif et mail partent selon config.
- Rappel deja envoye garde `reminderSentAt`.
