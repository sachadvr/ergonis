# Fonctionnalités détaillées - JobPlanner

Ce document détaille toutes les fonctionnalités du projet, leur état d'implémentation et les fichiers associés.

## Table des matières

1. [Gestion des offres et candidatures](#1-gestion-des-offres-et-candidatures)
2. [Extension navigateur](#2-extension-navigateur)
3. [Gestion des emails recruteurs](#3-gestion-des-emails-recruteurs)
4. [Relances et automatisation](#4-relances-et-automatisation)
5. [Planification des rendez-vous](#5-planification-des-rendez-vous)
6. [Intelligence artificielle](#6-intelligence-artificielle)
7. [Dashboard et suivi](#7-dashboard-et-suivi)
8. [Sécurité et contrôle](#8-sécurité-et-contrôle)

---

## 1. Gestion des offres et candidatures

### 1.1 Backend

#### Entités

**JobOffer** (`jobplanner-api/src/Entity/JobOffer.php`)
- ✅ Implémenté
- Champs : `id`, `title`, `company`, `url`, `location`, `notes`, `interviewPrep`, `sourceUrl`, `recruiterContactEmail`
- Relations : `owner` (User), `applications` (Collection<Application>)
- API Platform : CRUD complet avec groupes de sérialisation
- Fichiers de configuration : `config/serializer/JobOffer.yaml`

**Application** (`jobplanner-api/src/Entity/Application.php`)
- ✅ Implémenté
- Champs : `id`, `status` (enum ApplicationStatus), `pipelinePosition`, `appliedAt`, `lastActivityAt`
- Relations : `jobOffer` (JobOffer), `owner` (User), `history` (Collection<ApplicationHistory>), `recruiterEmails` (Collection<RecruiterEmail>), `scheduledFollowUps` (Collection<ScheduledFollowUp>), `interviews` (Collection<Interview>)
- API Platform : CRUD complet avec tri par `appliedAt` DESC
- Fichiers de configuration : `config/serializer/Application.yaml`

**ApplicationStatus** (`jobplanner-api/src/Entity/ApplicationStatus.php`)
- ✅ Implémenté
- Enum : `WISHLIST`, `APPLIED` (postulé), `FOLLOWED_UP` (relancé), `INTERVIEW` (entretien), `REJECTED` (refusé), `ACCEPTED` (accepté)

**ApplicationHistory** (`jobplanner-api/src/Entity/ApplicationHistory.php`)
- ✅ Implémenté
- Champs : `id`, `actionType` (enum ApplicationHistoryActionType), `description`, `createdAt`
- Relations : `application` (Application)
- API Platform : GET et GET_COLLECTION uniquement (lecture seule)
- Fichiers de configuration : `config/serializer/ApplicationHistory.yaml`

**ApplicationHistoryActionType** (`jobplanner-api/src/Entity/ApplicationHistoryActionType.php`)
- ✅ Implémenté
- Enum des types d'actions (à vérifier les valeurs exactes)

#### Repositories

- ✅ `ApplicationRepository` (`jobplanner-api/src/Repository/ApplicationRepository.php`)
  - Méthode `findActiveByUser()` utilisée par `EmailMatchingService`

#### Sécurité et propriété

**OwnedEntityInterface** (`jobplanner-api/src/State/OwnedEntityInterface.php`)
- ✅ Implémenté
- Interface pour les entités possédées par un utilisateur

**OwnedEntityProcessor** (`jobplanner-api/src/State/OwnedEntityProcessor.php`)
- ✅ Implémenté
- Assignation automatique du propriétaire lors de la création
- Utilisé par : JobOffer, Application, FollowUpRule

**OwnershipExtension** (`jobplanner-api/src/Doctrine/OwnershipExtension.php`)
- ✅ Implémenté
- Filtrage automatique des résultats par utilisateur connecté

**InterviewOwnershipExtension** (`jobplanner-api/src/Doctrine/InterviewOwnershipExtension.php`)
- ✅ Implémenté
- Extension spécifique pour les entretiens

### 1.2 Frontend

**CandidaturesKanban.vue** (`jobplanner-frontend/src/views/candidatures/CandidaturesKanban.vue`)
- ✅ Implémenté
- Pipeline Kanban avec colonnes par statut
- Drag & drop entre les colonnes

**CandidatureDetail.vue** (`jobplanner-frontend/src/views/candidatures/CandidatureDetail.vue`)
- ✅ Implémenté
- Vue détaillée d'une candidature
- Affichage de l'historique

**OffresList.vue** (`jobplanner-frontend/src/views/offres/OffresList.vue`)
- ✅ Implémenté
- Liste des offres d'emploi

**ApplicationService** (`jobplanner-frontend/src/service/ApplicationService.js`)
- ✅ Implémenté
- Appels API réels via `applicationApi` (getCandidatures, getCandidatureById, createOffreEtCandidature, etc.)

---

## 2. Extension navigateur

### 2.1 Backend

**ExtensionController** (`jobplanner-api/src/Controller/Api/ExtensionController.php`)
- ✅ Implémenté
- Endpoint : `POST /api/job_offers/from_extension`
- Authentification requise (JWT)
- Utilise `JobOfferFromExtensionService`

**JobOfferFromExtensionService** (`jobplanner-api/src/Service/JobOfferFromExtensionService.php`)
- ✅ Implémenté
- Création d'une offre depuis les données de l'extension
- Utilisation de l'IA pour extraire les informations
- Création optionnelle d'une candidature associée

**JobOfferFromExtensionInput** (`jobplanner-api/src/DTO/JobOfferFromExtensionInput.php`)
- ✅ Implémenté
- Validation : URL ou titre requis
- Champs : `url`, `title`, `content`, `createApplication`

**JsonPayloadParser** (`jobplanner-api/src/Service/JsonPayloadParser.php`)
- ✅ Implémenté
- Parser pour les données JSON de l'extension

### 2.2 Extension navigateur

**Manifest.json**
- ❌ Non créé
- À créer pour Chrome et Firefox

**Content script**
- ❌ Non créé
- Script pour récupérer le contenu de la page (titre, URL, texte)

**Background script**
- ❌ Non créé
- Script pour communiquer avec l'API

**Popup interface**
- ❌ Non créé
- Interface pour prévisualiser et confirmer l'ajout

---

## 3. Gestion des emails recruteurs

### 3.1 Backend

**RecruiterEmail** (`jobplanner-api/src/Entity/RecruiterEmail.php`)
- ✅ Implémenté
- Champs : `id`, `sender`, `subject`, `body`, `messageId` (unique), `receivedAt`, `aiSummary`, `direction` (INCOMING/OUTGOING), `isFavourite`, `isDeleted`, `isDraft`, `labels`
- Relations : `application` (Application)
- API Platform : GET, GET_COLLECTION, POST, PATCH
- Filtre : recherche par `application`
- Fichiers de configuration : `config/serializer/RecruiterEmail.yaml`

**RecruiterEmailRepository** (`jobplanner-api/src/Repository/RecruiterEmailRepository.php`)
- ✅ Implémenté
- Méthode `findOneBy(['messageId' => $messageId])` pour détecter les doublons

**SyncEmailsHandler** (`jobplanner-api/src/MessageHandler/SyncEmailsHandler.php`)
- ✅ Implémenté
- Utilise `ImapConnectionService` pour la connexion IMAP
- Logique de traitement des emails :
  - ✅ Détection des doublons
  - ✅ Association automatique aux candidatures
  - ✅ Création de l'entité RecruiterEmail
  - ✅ Création d'une entrée dans ApplicationHistory
  - ✅ Mise à jour de `lastActivityAt`
  - ✅ Annulation des relances en attente

**ImapConnectionService** (`jobplanner-api/src/Service/ImapConnectionService.php`)
- ✅ Implémenté
- Connexion IMAP via `UserMailboxSettings`
- Méthodes : `hasImapConfigured()`, `fetchUnseenEmails()`

**UserMailboxSettings** (`jobplanner-api/src/Entity/UserMailboxSettings.php`)
- ✅ Implémenté
- Champs IMAP : host, port, encryption, user, password, folder
- Champs SMTP : host, port, encryption, user, password
- API Platform : CRUD complet
- Fichiers de configuration : `config/serializer/UserMailboxSettings.yaml`

**EmailMatchingService** (`jobplanner-api/src/Service/EmailMatchingService.php`)
- ✅ Implémenté
- Association automatique basée sur :
  - Domaine de l'expéditeur
  - Nom de l'entreprise dans le contenu
  - Intitulé du poste dans le contenu
- Méthode `isAlreadyProcessed()` pour éviter les doublons

**SyncEmailsMessage** (`jobplanner-api/src/Message/SyncEmailsMessage.php`)
- ✅ Implémenté
- Message Messenger pour déclencher la synchronisation
- Contient `userId`

**Schedule** (`jobplanner-api/src/Schedule.php`)
- 🚧 Structure préparée, tâches non configurées
- Utilise Symfony Scheduler
- Commentaires indiquant où ajouter les tâches récurrentes

### 3.2 Frontend

**Vue emails** (`jobplanner-frontend/src/views/emails/`)
- ✅ Implémenté
- Visualisation des emails associés aux candidatures
- Filtrage et actions (favori, supprimer, restaurer)

**Paramètres** (`jobplanner-frontend/src/views/parametres/Parametres.vue`)
- ✅ Implémenté
- Connexion mail (IMAP/SMTP) via API `UserMailboxSettings`
- Règles de relance (CRUD)
- Logs et traçabilité (AiGenerationLog)

**Tasks** (`jobplanner-frontend/src/views/Tasks.vue`)
- ✅ Implémenté
- Affichage des candidatures (wishlist, postulé, entretien) comme tâches
- Boutons "Add Task" / "Create New Task" → redirection vers `/offres?add=1`

---

## 4. Relances et automatisation

### 4.1 Backend

**FollowUpRule** (`jobplanner-api/src/Entity/FollowUpRule.php`)
- ✅ Implémenté
- Champs : `id`, `daysWithoutReply`, `templateType` (follow_up, thank_you, spontaneous), `enabled`, `createdAt`
- Relations : `owner` (User)
- API Platform : CRUD complet
- Fichiers de configuration : `config/serializer/FollowUpRule.yaml`

**ScheduledFollowUp** (`jobplanner-api/src/Entity/ScheduledFollowUp.php`)
- ✅ Implémenté
- Champs : `id`, `scheduledAt`, `status` (pending, sent, cancelled), `cancelledAt`, `generatedContent`
- Relations : `application` (Application)
- API Platform : GET, GET_COLLECTION, PATCH
- Fichiers de configuration : `config/serializer/ScheduledFollowUp.yaml`

**Logique d'annulation** (`jobplanner-api/src/MessageHandler/SyncEmailsHandler.php`)
- ✅ Implémenté
- Méthode `cancelPendingFollowUps()` : annule les relances en attente lorsqu'une réponse est reçue

### 4.2 À implémenter

**Service de génération de mails**
- ❌ Non créé
- Utiliser `AiServiceInterface::generateFollowUpEmail()`
- Générer le contenu et le stocker dans `ScheduledFollowUp.generatedContent`

**Command pour traiter les relances**
- ❌ Non créé
- Command Symfony pour :
  - Trouver les relances à envoyer (`status = PENDING` et `scheduledAt <= now`)
  - Générer le contenu si nécessaire
  - Envoyer le mail (si `SIMULATION_MODE = false`)
  - Mettre à jour le statut

**Planification automatique**
- ❌ Non implémenté
- Lors de la création/modification d'une candidature :
  - Vérifier les règles actives (`FollowUpRule.enabled = true`)
  - Calculer la date de relance (`appliedAt + daysWithoutReply`)
  - Créer un `ScheduledFollowUp`

**Interface frontend**
- ❌ Non créé
- Configuration des règles de relance
- Visualisation des relances planifiées
- Validation manuelle avant envoi

---

## 5. Planification des rendez-vous

### 5.1 Backend

**Interview** (`jobplanner-api/src/Entity/Interview.php`)
- ✅ Implémenté
- Champs : `id`, `scheduledAt`, `type` (visio, tel, presentiel), `notes`, `locationOrLink`, `contactName`, `reminderSent`, `reminderSentAt`
- Relations : `application` (Application)
- API Platform : CRUD complet avec tri par `scheduledAt` ASC
- Fichiers de configuration : `config/serializer/Interview.yaml`

### 5.2 Frontend

**Vue calendrier** (`jobplanner-frontend/src/views/calendrier/`)
- ⚠️ Structure existante avec FullCalendar
- À compléter pour afficher les entretiens

### 5.3 À implémenter

**Intégration calendrier**
- ❌ Non complété
- Afficher les entretiens dans le calendrier
- Créer/modifier des entretiens depuis le calendrier

**Rappels avant entretiens**
- ❌ Non implémenté
- Command Symfony pour envoyer des rappels (email/notification)
- Utiliser `Interview.reminderSent` pour éviter les doublons
- Configurer l'heure d'envoi (ex: 24h avant)

---

## 6. Intelligence artificielle

### 6.1 Backend

**AiServiceInterface** (`jobplanner-api/src/Service/Ai/AiServiceInterface.php`)
- ✅ Implémenté
- Méthodes :
  - `extractJobOfferFromContent(string $url, string $title, string $content): array`
  - `generateFollowUpEmail(Application $application, string $tone = 'professionnel'): string`
  - `summarizeEmail(string $emailBody): string`
  - `suggestReplies(string $emailBody): array`

**NullAiService** (`jobplanner-api/src/Service/Ai/NullAiService.php`)
- ✅ Implémenté
- Implémentation par défaut sans API externe
- Extraction basique avec `CompanyExtractor`
- Génération de mails basiques

**CompanyExtractor** (`jobplanner-api/src/Service/CompanyExtractor.php`)
- ✅ Implémenté
- Extraction basique du nom d'entreprise depuis le contenu

**AiGenerationLog** (`jobplanner-api/src/Entity/AiGenerationLog.php`)
- ✅ Implémenté
- Entité pour traçabilité des générations IA
- API Platform : GET et GET_COLLECTION uniquement
- Fichiers de configuration : `config/serializer/AiGenerationLog.yaml`

### 6.2 À implémenter

**Implémentations concrètes**
- ❌ Non créé
- `OpenAiService` : Utilisation de l'API OpenAI (GPT-4, GPT-3.5)
- `AnthropicService` : Utilisation de l'API Anthropic (Claude)
- Configuration via variables d'environnement (`AI_PROVIDER`, `OPENAI_API_KEY`, etc.)

**Configuration**
- ❌ Non configuré
- Service container pour sélectionner l'implémentation selon `AI_PROVIDER`
- Gestion des erreurs et fallback vers `NullAiService`

**Améliorations**
- ❌ Non implémenté
- Adaptation du ton (professionnel, neutre, dynamique) dans `generateFollowUpEmail()`
- Amélioration de l'extraction (localisation, salaire, etc.)

---

## 7. Dashboard et suivi

### 7.1 Frontend

**Dashboard.vue** (`jobplanner-frontend/src/views/Dashboard.vue`)
- ✅ Structure existante

**Composants dashboard** (`jobplanner-frontend/src/components/dashboard/`)
- ✅ Structure existante

### 7.2 À implémenter

**Statistiques**
- ❌ Non implémenté
- Nombre de candidatures par statut
- Nombre d'entretiens à venir
- Taux de réponse
- Graphiques et visualisations (Chart.js disponible)

**Prochaines actions**
- ❌ Non implémenté
- Relances à envoyer (liste des `ScheduledFollowUp` avec `status = PENDING`)
- Entretiens prochains (liste des `Interview` avec `scheduledAt` dans les prochains jours)
- Actions en attente

**Vue d'ensemble**
- ❌ Non implémenté
- Activité récente (dernières actions dans `ApplicationHistory`)
- Candidatures récentes

**Endpoints API**
- ❌ Non créé
- Endpoint pour les statistiques
- Endpoint pour les prochaines actions

---

## 8. Sécurité et contrôle

### 8.1 Backend

**Authentification**

**LoginController** (`jobplanner-api/src/Controller/Api/LoginController.php`)
- ✅ Implémenté
- Endpoint : `POST /api/login`
- Génération du token JWT

**RegisterController** (`jobplanner-api/src/Controller/Api/RegisterController.php`)
- ✅ Implémenté
- Endpoint : `POST /api/register`
- Création d'un utilisateur

**UserRegistrationService** (`jobplanner-api/src/Service/UserRegistrationService.php`)
- ✅ Implémenté
- Gestion de l'inscription avec validation

**User** (`jobplanner-api/src/Entity/User.php`)
- ✅ Implémenté
- Implémente `UserInterface` et `PasswordAuthenticatedUserInterface`
- Champs : `id`, `email`, `roles`, `password`, `createdAt`, `updatedAt`

**UserRepository** (`jobplanner-api/src/Repository/UserRepository.php`)
- ✅ Implémenté

**Configuration JWT**
- ✅ Configuré
- Fichiers : `config/packages/lexik_jwt_authentication.yaml`
- Clés dans `config/jwt/`

**Sécurité des données**

**OwnedEntityProcessor** (`jobplanner-api/src/State/OwnedEntityProcessor.php`)
- ✅ Implémenté
- Assignation automatique du propriétaire

**OwnershipExtension** (`jobplanner-api/src/Doctrine/OwnershipExtension.php`)
- ✅ Implémenté
- Filtrage automatique par utilisateur

**Contrôle**

**SIMULATION_MODE**
- ✅ Configuré
- Variable d'environnement pour éviter les envois accidentels
- À utiliser dans les services d'envoi d'emails

**AiGenerationLog**
- ✅ Entité créée
- Traçabilité des générations IA

### 8.2 À implémenter

**Validation manuelle**
- ❌ Non implémenté
- Interface pour valider les mails avant envoi automatique
- Workflow de validation dans le frontend

**Interface de logs**
- ✅ Implémenté
- Visualisation des logs dans Paramètres (onglet "Logs et traçabilité")
- Chargement via `settingsApi.getLogs()` → `GET /api/ai_generation_logs`

**Audit trail**
- ❌ Non implémenté
- Logs complets de toutes les actions importantes
- Historique des modifications

---

## Résumé de l'état d'avancement

### ✅ Complètement implémenté

1. Gestion des offres et candidatures (backend + frontend)
2. Authentification JWT
3. Sécurité et isolation des données
4. Entités pour emails, relances, entretiens
5. Interface IA abstraite avec implémentation par défaut
6. Structure pour extension navigateur (backend)
7. Synchronisation IMAP (ImapConnectionService + SyncEmailsHandler)
8. UserMailboxSettings (IMAP/SMTP) avec API
9. RecruiterEmail (POST, PATCH)
10. AiGenerationLog (API lecture)
11. Paramètres (connexion mail, règles, logs)
12. Tasks (affichage + boutons Add/Create)
13. Docker (API, frontend, database, Mailpit)
14. Fixtures (toutes les entités)

### 🚧 Partiellement implémenté

1. Extension navigateur (backend OK, extension à créer)
2. Relances automatiques (entités OK, génération/envoi à implémenter)
3. Dashboard (structure OK, contenu à ajouter)
4. Calendrier (structure OK, intégration à compléter)

### ❌ Non implémenté

1. Services IA concrets (OpenAI, Anthropic)
2. Command pour traiter les relances
3. Planification automatique des relances
4. Rappels avant entretiens
5. Statistiques et visualisations
6. Interface de logs
7. Tests unitaires et d'intégration
8. Documentation API complète
