# Architecture technique - JobPlanner

Ce document décrit l'architecture technique détaillée du projet JobPlanner.

## Vue d'ensemble

JobPlanner suit une architecture fullstack classique avec séparation claire entre backend et frontend :

```
┌─────────────────┐         ┌─────────────────┐         ┌─────────────────┐
│   Frontend      │         │   Backend API   │         │   Base de       │
│   (Vue.js)      │◄───────►│   (Symfony)     │◄───────►│   données       │
│                 │  HTTP   │                 │  SQL    │   (PostgreSQL)  │
└─────────────────┘         └─────────────────┘         └─────────────────┘
       │                            │
       │                            │
       ▼                            ▼
┌─────────────────┐         ┌─────────────────┐
│   Extension     │         │   Services       │
│   Navigateur    │         │   Externes       │
│                 │         │   (IMAP, IA)     │
└─────────────────┘         └─────────────────┘
```

## Backend (Symfony)

### Structure des répertoires

```
jobplanner-api/
├── config/                      # Configuration Symfony
│   ├── packages/                # Configuration des bundles
│   │   ├── api_platform.yaml   # Configuration API Platform
│   │   ├── doctrine.yaml       # Configuration Doctrine ORM
│   │   ├── security.yaml       # Configuration sécurité
│   │   ├── messenger.yaml      # Configuration Messenger
│   │   └── lexik_jwt_authentication.yaml
│   ├── routes/                  # Routes personnalisées
│   └── serializer/              # Configuration sérialisation
├── src/
│   ├── Entity/                  # Entités Doctrine
│   ├── Repository/              # Repositories Doctrine
│   ├── Controller/Api/          # Contrôleurs REST
│   ├── Service/                 # Services métier
│   │   └── Ai/                  # Services IA
│   ├── MessageHandler/          # Handlers Messenger
│   ├── Message/                 # Messages Messenger
│   ├── State/                   # Processors API Platform
│   ├── Doctrine/                # Extensions Doctrine
│   ├── DTO/                     # Data Transfer Objects
│   ├── Command/                 # Commands Symfony
│   └── Schedule.php             # Configuration Scheduler
├── public/                      # Point d'entrée web
└── compose.yaml                 # Docker Compose
```

### Architecture des couches

#### 1. Couche Présentation (Controllers)

Les contrôleurs gèrent les requêtes HTTP et délèguent la logique métier aux services.

**Contrôleurs existants** :
- `LoginController` : Authentification JWT
- `RegisterController` : Inscription utilisateur
- `ExtensionController` : Endpoint pour l'extension navigateur

**API Platform** :
- Les entités avec l'attribut `#[ApiResource]` génèrent automatiquement des endpoints REST
- Configuration dans `config/packages/api_platform.yaml`

#### 2. Couche Métier (Services)

Les services contiennent la logique métier de l'application.

**Services principaux** :
- `JobOfferFromExtensionService` : Création d'offres depuis l'extension
- `EmailMatchingService` : Association emails ↔ candidatures
- `ImapConnectionService` : Connexion IMAP et récupération des emails
- `UserRegistrationService` : Gestion de l'inscription
- `CompanyExtractor` : Extraction basique du nom d'entreprise
- `AiServiceInterface` + implémentations : Services IA

**Patterns utilisés** :
- Dependency Injection (autowiring)
- Interface Segregation (AiServiceInterface)
- Strategy Pattern (différentes implémentations IA)

#### 3. Couche Données (Entities & Repositories)

**Entités Doctrine** :
- `User` : Utilisateurs de l'application
- `JobOffer` : Offres d'emploi
- `Application` : Candidatures
- `ApplicationHistory` : Historique des actions
- `RecruiterEmail` : Emails reçus/envoyés
- `Interview` : Entretiens planifiés
- `FollowUpRule` : Règles de relance
- `ScheduledFollowUp` : Relances planifiées
- `AiGenerationLog` : Logs des générations IA
- `UserMailboxSettings` : Configuration IMAP/SMTP par utilisateur

**Relations principales** :
```
User
├── JobOffer (OneToMany)
│   └── Application (OneToMany)
│       ├── ApplicationHistory (OneToMany)
│       ├── RecruiterEmail (OneToMany)
│       ├── ScheduledFollowUp (OneToMany)
│       └── Interview (OneToMany)
├── FollowUpRule (OneToMany)
└── UserMailboxSettings (OneToOne)
```

**Repositories** :
- Repositories Doctrine générés automatiquement
- Méthodes personnalisées dans `ApplicationRepository` et `RecruiterEmailRepository`

#### 4. Couche Sécurité

**Authentification JWT** :
- Bundle : Lexik JWT Authentication
- Configuration : `config/packages/lexik_jwt_authentication.yaml`
- Clés : `config/jwt/private.pem` et `config/jwt/public.pem`

**Isolation des données** :
- `OwnedEntityInterface` : Interface pour les entités possédées
- `OwnedEntityProcessor` : Assignation automatique du propriétaire
- `OwnershipExtension` : Filtrage automatique par utilisateur dans les requêtes

**Sécurité des endpoints** :
- Configuration dans `config/packages/security.yaml`
- Protection des routes API (sauf `/api/login` et `/api/register`)

#### 5. Couche Automatisation

**Symfony Messenger** :
- Configuration : `config/packages/messenger.yaml`
- Messages asynchrones pour :
  - Synchronisation emails (`SyncEmailsMessage`)
  - Traitement des relances (à implémenter)

**Symfony Scheduler** :
- Configuration : `src/Schedule.php`
- Tâches récurrentes à configurer :
  - Synchronisation emails IMAP
  - Traitement des relances planifiées
  - Envoi des rappels d'entretiens

**Handlers** :
- `SyncEmailsHandler` : Synchronisation IMAP via `ImapConnectionService`, matching et création des entités

### Flux de données

#### Création d'une candidature depuis l'extension

```
Extension → POST /api/job_offers/from_extension
    ↓
ExtensionController::fromExtension()
    ↓
JobOfferFromExtensionService::createFromExtension()
    ↓
AiServiceInterface::extractJobOfferFromContent()
    ↓
JobOffer (persist) + Application (persist)
    ↓
EntityManager::flush()
    ↓
Response JSON
```

#### Synchronisation des emails

```
Scheduler → SyncEmailsMessage
    ↓
SyncEmailsHandler::__invoke()
    ↓
Connexion IMAP (à implémenter)
    ↓
Pour chaque email :
    ├─ EmailMatchingService::isAlreadyProcessed()
    ├─ EmailMatchingService::findMatchingApplication()
    ├─ Création RecruiterEmail
    ├─ Création ApplicationHistory
    ├─ Mise à jour Application.lastActivityAt
    └─ Annulation ScheduledFollowUp en attente
```

#### Association automatique emails ↔ candidatures

```
Email reçu
    ↓
EmailMatchingService::findMatchingApplication()
    ↓
Critères de matching :
    ├─ Domaine expéditeur = domaine entreprise
    ├─ Nom entreprise dans sujet/corps
    └─ Intitulé poste dans sujet/corps
    ↓
Application trouvée → Association
```

### Configuration

#### Variables d'environnement

Fichier `.env.local` :
```env
# Base de données
DATABASE_URL="postgresql://..."

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=...

# CORS
CORS_ALLOW_ORIGIN=http://localhost:5173

# Email
MAILER_DSN=smtp://...

# Mode simulation
SIMULATION_MODE=true

# IA
AI_PROVIDER=openai
OPENAI_API_KEY=...
```

#### Configuration API Platform

- Sérialisation : Groupes `read` et `write`
- Filtres : SearchFilter pour recherche
- Tri : Configuration par défaut sur certaines entités
- Pagination : Activée par défaut

## Frontend (Vue.js)

### Structure des répertoires

```
jobplanner-frontend/
├── src/
│   ├── views/                   # Pages de l'application
│   │   ├── candidatures/       # Gestion candidatures
│   │   ├── offres/              # Gestion offres
│   │   ├── calendrier/          # Calendrier entretiens
│   │   ├── emails/              # Gestion emails
│   │   ├── parametres/          # Paramètres
│   │   └── Dashboard.vue        # Dashboard
│   ├── components/              # Composants réutilisables
│   │   ├── candidatures/        # Composants candidatures
│   │   ├── dashboard/           # Widgets dashboard
│   │   └── landing/             # Composants landing
│   ├── layout/                  # Layout principal
│   │   └── composables/         # Composables Vue
│   ├── api/                     # Client API
│   ├── service/                 # Services frontend
│   ├── theme/                   # Thème PrimeVue
│   └── main.js                  # Point d'entrée
├── public/                      # Fichiers statiques
└── package.json                 # Dépendances
```

### Architecture des composants

#### Structure Vue.js

**Composition API** :
- Utilisation de `<script setup>`
- Composables pour la logique réutilisable

**Routing** :
- Vue Router pour la navigation
- Routes définies dans le router

**State Management** :
- Actuellement : Services avec données mock
- À considérer : Pinia pour l'état global

**UI Framework** :
- PrimeVue : Composants UI
- Thème : Sakai (Wonderflow)
- FullCalendar : Calendrier
- Chart.js : Graphiques (disponible)

### Flux de données

#### Récupération des candidatures

```
CandidaturesKanban.vue
    ↓
ApplicationService.getCandidatures()
    ↓
applicationApi.getCandidatures() → GET /api/applications
    ↓
Affichage dans le Kanban
```

#### Authentification

**Implémenté** :
- Stockage du token JWT (localStorage)
- Intercepteur axios pour ajouter le token
- Redirection vers login si 401

## Base de données (PostgreSQL)

### Schéma principal

```sql
users
├── id (PK)
├── email (UNIQUE)
├── password (hashed)
├── roles (JSON)
├── created_at
└── updated_at

job_offers
├── id (PK)
├── owner_id (FK → users.id)
├── title
├── company
├── url
├── location
├── notes (TEXT)
├── interview_prep (TEXT)
├── source_url
├── recruiter_contact_email
├── created_at
└── updated_at

applications
├── id (PK)
├── job_offer_id (FK → job_offers.id)
├── owner_id (FK → users.id)
├── status (ENUM)
├── pipeline_position
├── applied_at
├── last_activity_at
├── created_at
└── updated_at

application_history
├── id (PK)
├── application_id (FK → applications.id)
├── action_type (ENUM)
├── description (TEXT)
└── created_at

recruiter_emails
├── id (PK)
├── application_id (FK → applications.id)
├── sender
├── subject
├── body (TEXT)
├── message_id (UNIQUE)
├── received_at
├── ai_summary (TEXT)
├── direction
├── is_favourite
├── is_deleted
├── is_draft
└── labels (JSON)

interviews
├── id (PK)
├── application_id (FK → applications.id)
├── scheduled_at
├── type
├── notes (TEXT)
├── location_or_link
├── contact_name
├── reminder_sent
└── reminder_sent_at

follow_up_rules
├── id (PK)
├── owner_id (FK → users.id)
├── days_without_reply
├── template_type
├── enabled
└── created_at

scheduled_follow_ups
├── id (PK)
├── application_id (FK → applications.id)
├── scheduled_at
├── status
├── cancelled_at
└── generated_content (TEXT)

ai_generation_logs
├── id (PK)
├── user_id (FK)
├── type
├── prompt (TEXT)
├── tokens_used
└── created_at

user_mailbox_settings
├── id (PK)
├── user_id (FK, UNIQUE)
├── imap_host, imap_port, imap_encryption
├── imap_user, imap_password, imap_folder
├── smtp_host, smtp_port, smtp_encryption
├── smtp_user, smtp_password
├── is_active
├── created_at
└── updated_at
```

### Index recommandés

- `users.email` : UNIQUE (déjà présent)
- `recruiter_emails.message_id` : UNIQUE (déjà présent)
- `applications.owner_id` : Pour le filtrage par utilisateur
- `applications.status` : Pour les requêtes par statut
- `interviews.scheduled_at` : Pour les requêtes de calendrier
- `scheduled_follow_ups.scheduled_at` + `status` : Pour les requêtes de relances

## Services externes

### IMAP (Synchronisation emails)

**Bibliothèque** : php-imap/php-imap

**Configuration** :
- Connexion via DSN IMAP
- Authentification avec credentials utilisateur
- Synchronisation périodique via Scheduler

**Flux** :
1. Connexion à la boîte mail
2. Recherche des nouveaux emails (UNSEEN)
3. Traitement via `SyncEmailsHandler`
4. Marquage comme lu

### SMTP (Envoi d'emails)

**Bibliothèque** : Symfony Mailer

**Configuration** :
- DSN SMTP dans `MAILER_DSN`
- Mode simulation via `SIMULATION_MODE`

**Utilisation** :
- Envoi de relances
- Envoi de rappels d'entretiens
- Envoi de notifications

### IA (Génération de contenu)

**Interface** : `AiServiceInterface`

**Implémentations** :
- `NullAiService` : Par défaut (sans API)
- `OpenAiService` : À créer (OpenAI API)
- `AnthropicService` : À créer (Anthropic API)

**Configuration** :
- Sélection via `AI_PROVIDER`
- Clés API dans les variables d'environnement

**Utilisations** :
- Extraction d'informations depuis pages web
- Génération de mails de relance
- Résumé d'emails
- Suggestions de réponses

## Sécurité

### Authentification

- JWT avec expiration
- Refresh token (à implémenter)
- Hachage des mots de passe (bcrypt)

### Autorisation

- Isolation des données par utilisateur
- Filtrage automatique dans les requêtes
- Validation des propriétaires avant modification

### Protection des données

- Validation des entrées (Symfony Validator)
- Protection CSRF (si formulaires)
- CORS configuré pour le frontend
- Sanitization des données utilisateur

### Logs et audit

- Logs des actions importantes
- Traçabilité des générations IA
- Mode simulation pour éviter les envois accidentels

## Performance

### Optimisations actuelles

- Pagination automatique (API Platform)
- Lazy loading des relations Doctrine
- Cache Symfony (à configurer)

### Optimisations à considérer

- Cache des requêtes fréquentes
- Index de base de données
- Optimisation des requêtes N+1
- Lazy loading des collections volumineuses
- CDN pour les assets frontend

## Déploiement

### Docker

**Configuration actuelle** (`docker-compose.yml`) :
- **database** : PostgreSQL 16
- **api** : Backend Symfony (Dockerfile)
- **frontend** : Vue.js build + Nginx
- **mailpit** : SMTP (port 1025) et interface web (port 8025)

**Mode développement** (`docker-compose.dev.yml`) :
- Frontend avec Vite et hot reload (port 5173)
- Montage du code source pour l'API

Voir `README.docker.md` pour le guide complet.

### CI/CD

**À implémenter** :
- Tests automatisés
- Build et déploiement
- Migration de base de données
- Variables d'environnement sécurisées

## Monitoring

### Logs

- Logs Symfony (monolog)
- Logs structurés (à améliorer)
- Logs des actions importantes

### Métriques

**À implémenter** :
- Nombre de requêtes API
- Temps de réponse
- Erreurs et exceptions
- Utilisation de l'IA

### Alertes

**À implémenter** :
- Erreurs critiques
- Échecs de synchronisation emails
- Problèmes de connexion base de données
