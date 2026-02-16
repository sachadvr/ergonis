# Présentation du projet JobPlanner

## 🎯 Objectif

**JobPlanner** est une application web fullstack de gestion de candidatures à l'emploi qui centralise le suivi des offres, des échanges avec les recruteurs et des rendez-vous, avec automatisation via workflows et IA.

### Problématique résolue

Lors d'une recherche d'emploi, il est difficile de :
- Suivre toutes les candidatures envoyées
- Ne pas oublier de relancer les recruteurs
- Organiser les entretiens et préparations
- Centraliser les échanges par email
- Extraire rapidement les informations d'une offre d'emploi

**JobPlanner** résout ces problèmes en automatisant et centralisant tout le processus.

## 🏗️ Architecture

### Stack technique

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend                            │
│  Vue.js 3 + PrimeVue + Vue Router + FullCalendar      │
└──────────────────┬──────────────────────────────────────┘
                   │ HTTP/REST
┌──────────────────▼──────────────────────────────────────┐
│                    Backend API                          │
│  Symfony 8.0 + API Platform + Doctrine ORM             │
│  + JWT Auth + Messenger + Scheduler                    │
└──────────────────┬──────────────────────────────────────┘
                   │ SQL
┌──────────────────▼──────────────────────────────────────┐
│                 Base de données                         │
│              PostgreSQL 16                               │
└──────────────────────────────────────────────────────────┘

Services externes :
  • Extension navigateur (Chrome/Firefox)
  • IMAP (synchronisation emails)
  • SMTP (envoi emails)
  • IA (OpenAI/Anthropic)
```

### Structure du projet

```
JobPlanner/
├── jobplanner-api/          # Backend Symfony
│   ├── src/
│   │   ├── Entity/          # 11 entités Doctrine
│   │   ├── Controller/      # 3 contrôleurs REST
│   │   ├── Service/         # Services métier + IA
│   │   └── MessageHandler/  # Automatisation
│   └── config/              # Configuration
└── jobplanner-frontend/     # Frontend Vue.js
    └── src/
        ├── views/           # 5+ pages principales
        ├── components/      # Composants réutilisables
        └── api/             # Client API
```

## ✨ Fonctionnalités principales

### 1. 📋 Gestion des offres et candidatures

**Pipeline Kanban interactif**
- Visualisation par statut : Wishlist → Postulé → Relancé → Entretien → Refusé/Accepté
- Drag & drop pour changer le statut
- Vue détaillée avec historique complet

**Gestion complète**
- Ajout manuel d'offres
- Notes et préparation d'entretien
- Suivi de toutes les actions

### 2. 🌐 Extension navigateur

**Ajout rapide d'offres**
- Bouton dans la barre d'outils
- Récupération automatique du contenu de la page
- Extraction intelligente via IA (titre, entreprise, localisation)
- Création automatique de candidature

**État** : Backend prêt (`POST /api/job_offers/from_extension`), extension à créer

### 3. 📧 Gestion des emails recruteurs

**Synchronisation automatique**
- Connexion IMAP à la boîte mail
- Synchronisation périodique automatique
- Association intelligente aux candidatures :
  - Par expéditeur (domaine)
  - Par contenu (entreprise, poste)
- Résumé automatique via IA

**Historique centralisé**
- Tous les échanges visibles dans la candidature
- Recherche et filtrage

**État** : Implémenté (ImapConnectionService + SyncEmailsHandler, UserMailboxSettings)

### 4. 🔄 Relances et automatisation

**Règles configurables**
- Relancer après X jours sans réponse
- Génération automatique de mails via IA
- Adaptation du ton (professionnel, neutre, dynamique)

**Workflow intelligent**
- Planification automatique des relances
- Annulation si réponse reçue
- Validation manuelle avant envoi (mode simulation)

**État** : Entités créées, génération/envoi à implémenter

### 5. 📅 Planification des rendez-vous

**Calendrier intégré**
- Vue mensuelle/semaine/jour
- Création d'entretiens liés aux candidatures
- Informations pratiques : visio/présentiel, lieu, contact

**Rappels automatiques**
- Notification avant l'entretien
- Préparation personnalisée

**État** : Entité et API prêtes, calendrier à intégrer

### 6. 🤖 Intelligence artificielle

**Extraction d'informations**
- Depuis pages web d'offres d'emploi
- Titre, entreprise, localisation, salaire

**Génération de contenu**
- Mails de relance personnalisés
- Mails de remerciement
- Candidatures spontanées

**Analyse d'emails**
- Résumé automatique
- Suggestions de réponses

**État** : Interface abstraite prête, implémentations concrètes à créer (OpenAI, Anthropic)

### 7. 📊 Dashboard et suivi

**Vue d'ensemble**
- Statistiques : candidatures par statut, taux de réponse
- Prochaines actions : relances à envoyer, entretiens à venir
- Activité récente

**Visualisations**
- Graphiques et métriques
- Tendances

**État** : Structure prête, contenu à ajouter

### 8. 🔒 Sécurité et contrôle

**Authentification**
- JWT sécurisé
- Isolation des données par utilisateur

**Contrôle**
- Mode simulation pour éviter les envois accidentels
- Validation manuelle avant envoi automatique
- Logs de toutes les actions

**État** : Authentification complète, logs à améliorer

## 📈 État d'avancement

### ✅ Complètement implémenté

- Architecture backend complète (12 entités, API REST)
- Authentification JWT
- Gestion des offres et candidatures (CRUD)
- Pipeline Kanban frontend + vue détail candidature
- Sécurité et isolation des données
- Synchronisation IMAP (ImapConnectionService + SyncEmailsHandler)
- Configuration mail (UserMailboxSettings IMAP/SMTP)
- Paramètres (connexion mail, règles de relance, logs)
- Tasks (affichage + création d'offres)
- Docker (API, frontend, PostgreSQL, Mailpit)
- Fixtures complètes

### 🚧 En cours / À compléter

- Extension navigateur (backend OK, extension à créer)
- Relances automatiques (génération + envoi)
- Services IA concrets (OpenAI, Anthropic)
- Dashboard avec statistiques
- Intégration calendrier complète
- Scheduler (tâches récurrentes)

### 📋 À venir

- Tests unitaires et d'intégration
- Documentation API complète
- CI/CD
- Monitoring

## 🎓 Objectifs pédagogiques

Ce projet met en pratique :

1. **Architecture fullstack**
   - Séparation backend/frontend
   - API REST avec API Platform
   - Communication asynchrone (Messenger)

2. **Base de données relationnelle**
   - Modélisation des relations
   - Migrations Doctrine
   - Optimisation des requêtes

3. **Automatisation de workflows**
   - Tâches récurrentes (Scheduler)
   - Messages asynchrones (Messenger)
   - Règles métier configurables

4. **Intégration de services externes**
   - Extension navigateur
   - IMAP/SMTP
   - APIs IA (OpenAI, Anthropic)

5. **Bonnes pratiques**
   - Sécurité (JWT, isolation données)
   - Validation et gestion d'erreurs
   - Architecture modulaire et extensible

## 🚀 Démarrage rapide

### Avec Docker (recommandé)

```bash
# Depuis la racine du projet
docker compose up -d --build
docker compose exec api php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec api php bin/console doctrine:fixtures:load --no-interaction

# Connexion : guest@test.com / guest
# Frontend : http://localhost  |  API : http://localhost:8000  |  Mailpit : http://localhost:8025
```

### Mode développement (hot reload)

```bash
docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.dev.yml up -d
# Frontend : http://localhost:5173 (Vite HMR)
```

### Sans Docker

```bash
# Backend
cd jobplanner-api
composer install
cp .env .env.local  # Configurer DATABASE_URL, JWT_PASSPHRASE
php bin/console doctrine:migrations:migrate
symfony server:start

# Frontend
cd jobplanner-frontend
npm install
npm run dev
```

## 📚 Documentation

- **README.md** : Vue d'ensemble et guide d'installation
- **README.docker.md** : Guide Docker et mode développement
- **FEATURES.md** : Liste détaillée des fonctionnalités implémentées
- **ARCHITECTURE.md** : Architecture technique complète
- **MISSING_FEATURES.md** : Fonctionnalités manquantes et priorités

## 🔮 Prochaines étapes

1. Compléter la synchronisation IMAP
2. Créer l'extension navigateur
3. Implémenter les services IA (OpenAI, Anthropic)
4. Finaliser le dashboard avec statistiques
5. Automatiser les relances
6. Ajouter les tests et la documentation complète

---

**JobPlanner** - Centralisez et automatisez votre recherche d'emploi 🎯
