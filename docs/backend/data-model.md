# Data Model

```mermaid
classDiagram

class User {
  +int id
  +string email
  +string roles
  +string password
  +datetime createdAt
  +datetime updatedAt
}

class JobOffer {
  +int id
  +string title
  +string company
  +string url
  +string rawContent
  +string location
  +string notes
  +string interviewPrep
  +string sourceUrl
  +string recruiterContactEmail
  +string jobSummary
  +decimal salaryMin
  +decimal salaryMax
  +string salaryCurrency
  +string contractType
  +string remotePolicy
  +json details
  +datetime createdAt
  +datetime updatedAt
}

class Application {
  +int id
  +ApplicationStatus status
  +int pipelinePosition
  +datetime appliedAt
  +datetime createdAt
  +datetime updatedAt
  +datetime lastActivityAt
  +string cvFitAnalysisStatus
  +json cvFitAnalysisResult
  +datetime cvFitAnalysisRequestedAt
  +datetime cvFitAnalysisCompletedAt
}

class ApplicationHistory {
  +int id
  +ApplicationHistoryActionType actionType
  +string description
  +bool isSeen
  +datetime createdAt
}

class RecruiterEmail {
  +int id
  +string sender
  +string subject
  +string body
  +string messageId
  +datetime receivedAt
  +string aiSummary
  +string direction
  +bool isFavourite
  +bool isDeleted
  +bool isDraft
  +bool isSeen
  +json labels
}

class Interview {
  +int id
  +datetime scheduledAt
  +string type
  +string notes
  +string locationOrLink
  +string contactName
  +bool reminderSent
  +datetime reminderSentAt
}

class ScheduledFollowUp {
  +int id
  +datetime scheduledAt
  +string status
  +datetime cancelledAt
  +string generatedContent
}

class FollowUpRule {
  +int id
  +int daysWithoutReply
  +string templateType
  +bool enabled
  +datetime createdAt
}

class AiGenerationLog {
  +int id
  +string type
  +string prompt
  +int tokensUsed
  +datetime createdAt
}

class ApplicationStatus {
  WISHLIST
  APPLIED
  INTERVIEW
  OFFER
  REJECTED
  ACCEPTED
}

class ApplicationHistoryActionType {
  EMAIL_RECEIVED
  STATUS_CHANGED
  CREATED
  IMPORTED_FROM_EXTENSION
  RELANCE_SENT
  INTERVIEW_SCHEDULED
}

User "1" --> "*" Application : owner
User "1" --> "*" JobOffer : owner
User "1" --> "*" FollowUpRule : owner
User "1" --> "*" RecruiterEmail : owner
User "1" --> "*" AiGenerationLog : owner

JobOffer "1" --> "*" Application

Application "1" --> "*" ApplicationHistory
Application "1" --> "*" RecruiterEmail
Application "1" --> "*" ScheduledFollowUp
Application "1" --> "*" Interview

ApplicationHistory --> Application
RecruiterEmail --> Application
ScheduledFollowUp --> Application
Interview --> Application
```

# Explications des entitées
## User
- Compte candidat
- Owner de toutes les données (multi-tenant simple)
- Utilisé pour auth et filtrage global

## JobOffer
- Offre d’emploi
- Peut venir de l'extension (import) ou d’un ajout manuel
- Contient :
  - données métier (title, company, salary…)
  - contenu brut (`rawContent`) pour reprocessing
  - enrichissements (`jobSummary`, `interviewPrep`)
- Relation : 1 JobOffer → N Applications

## Application
- Entité centrale du système
- Représente une candidature utilisateur pour une offre
- Contient :
  - état (`status`, `pipelinePosition`)
  - dates (`appliedAt`, `lastActivityAt`)
  - données IA (analyse CV)
- Agrège :
  - historique
  - emails recruteur
  - interviews
  - relances
- Source principale de vérité métier

## ApplicationHistory
- Timeline des événements d’une candidature / notifications utilisateurs
- Types :
  - création
  - changement de statut
  - email reçu
  - entretien planifié
  - relance envoyée
- Contient :
  - description optionnelle
  - flag `isSeen` (notifications)
- Sert pour UI, notifications et debug

## RecruiterEmail
- Email synchronisé depuis la boîte mail
- Source externe de vérité
- Contraintes :
  - unique `(messageId + owner)`
- Contient :
  - contenu (`body`)
  - metadata (`sender`, `subject`, `receivedAt`)
  - flags UI (`isSeen`, `isFavourite`, etc)
  - résumé IA (`aiSummary`)
- Peut déclencher :
  - mise à jour activité
  - création d’historique

## FollowUpRule
- Règle utilisateur pour automatiser les relances
- Définit :
  - délai sans réponse (`daysWithoutReply`)
  - type de template
  - activation
- Sert d’input pour génération de relances

## ScheduledFollowUp
- Relance planifiée concrète
- Cycle :
  - pending → sent → cancelled
- Contient :
  - date d’envoi
  - contenu généré
- Sert de buffer entre règle et envoi réel

## Interview
- Entretien lié à une candidature
- Contient :
  - date (`scheduledAt`)
  - type (visio, tel, présentiel)
  - lieu ou lien
  - contact
  - notes
- Gère aussi les rappels (`reminderSent`)

## AiGenerationLog
- Log des appels à l’IA
- Contient :
  - type d’usage
  - prompt
  - tokens consommés
- Sert pour :
  - monitoring coût
  - debug
  - analytics

## UserMailboxSettings
- Configuration mail utilisateur
- Contient :
  - IMAP (sync réception)
  - SMTP (envoi emails)
  - OAuth (tokens, expiration)
- Sert pour :
  - synchronisation des emails
  - envoi des relances
