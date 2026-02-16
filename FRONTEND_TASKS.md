# Tâches frontend - Ce qui manque

Analyse du code frontend et des écarts avec le backend.

---

## 1. Paramètres - Connexion mail (IMAP / SMTP)

### État actuel
- **Parametres.vue** : Formulaire IMAP + SMTP complet
- **Stockage** : `localStorage` via `mailConfigStorage` (pas d'API)
- **Backend** : Entité `UserMailboxSettings` existe (IMAP uniquement), pas d'API exposée

### À faire

| Tâche | Priorité | Détails |
|-------|----------|---------|
| **API UserMailboxSettings** | Haute | Exposer `UserMailboxSettings` via API Platform (GET, POST, PUT, PATCH) avec sécurité par utilisateur |
| **Ajouter SMTP au backend** | Moyenne | `UserMailboxSettings` ne gère que IMAP. Ajouter champs SMTP (smtp_host, smtp_port, smtp_user, smtp_password, smtp_encryption) ou créer entité séparée |
| **Remplacer localStorage par API** | Haute | Dans `settingsApi.js` : `getMailboxSettings()` et `saveMailboxSettings()` qui appellent l'API au lieu de `mailConfigStorage` |
| **Bouton "Tester la connexion"** | Basse | Permettre de tester IMAP/SMTP avant sauvegarde |

**Fichiers concernés** :
- `jobplanner-api/src/Entity/UserMailboxSettings.php` (ajouter ApiResource)
- `jobplanner-frontend/src/api/settingsApi.js` (mailboxSettings API)
- `jobplanner-frontend/src/views/parametres/Parametres.vue` (appeler API au lieu de localStorage)

---

## 2. Paramètres - Logs et traçabilité

### État actuel
- **Parametres.vue** : Onglet "Logs et traçabilité" avec DataTable vide
- **Données** : `logs = ref([])` jamais chargé
- **Backend** : Entité `AiGenerationLog` existe, pas d'API exposée

### À faire

| Tâche | Priorité | Détails |
|-------|----------|---------|
| **API AiGenerationLog** | Haute | Exposer en lecture seule (GET, GET_COLLECTION) avec filtrage par utilisateur |
| **Élargir les logs** | Moyenne | `AiGenerationLog` ne couvre que l'IA. Créer une entité `ActionLog` plus générique (relances envoyées, sync emails, etc.) ou étendre AiGenerationLog |
| **Charger les logs dans Parametres** | Haute | `loadLogs()` qui appelle l'API, avec pagination |
| **Filtres (date, type)** | Basse | Filtrer les logs par période et type d'action |

**Fichiers concernés** :
- `jobplanner-api/src/Entity/AiGenerationLog.php` (ajouter ApiResource)
- `jobplanner-frontend/src/api/settingsApi.js` (getLogs)
- `jobplanner-frontend/src/views/parametres/Parametres.vue` (loadLogs, onMounted)

---

## 3. Tasks (My Tasks)

### État actuel
- **Tasks.vue** : Affiche les candidatures filtrées (wishlist, postulé, entretien) comme "tasks"
- **Boutons** : "Add Task" et "Create New Task" ne font rien (pas de handler)
- **Logique** : Les "tasks" sont dérivées des candidatures, pas une entité séparée

### À faire

| Tâche | Priorité | Détails |
|-------|----------|---------|
| **"Add Task" → créer offre/candidature** | Haute | Ouvrir un dialog pour créer une offre + candidature (comme dans OffresList), ou rediriger vers `/offres` avec dialog ouvert |
| **"Create New Task" → idem** | Haute | Même comportement que "Add Task" |
| **Ou : Task = candidature** | - | Si pas d'entité Task séparée, les boutons peuvent rediriger vers "Ajouter une offre" (`/offres` avec query `?add=1`) |

**Fichiers concernés** :
- `jobplanner-frontend/src/views/Tasks.vue` (handlers pour les boutons)

---

## 4. RecruiterEmail - POST / PATCH

### État actuel
- **Backend** : `RecruiterEmail` n'a que `Get` et `GetCollection` (lecture seule)
- **Frontend** : `applicationApi.sendEmail()` (POST) et `applicationApi.updateEmail()` (PATCH) sont utilisés (ex: CandidatureDetailDialog, Emails.vue)

### À faire

| Tâche | Priorité | Détails |
|-------|----------|---------|
| **Ajouter Post à RecruiterEmail** | Haute | Permettre la création d'emails (ex: brouillon, email envoyé manuellement) |
| **Ajouter Patch à RecruiterEmail** | Haute | Permettre de modifier isFavourite, isDeleted, isDraft, labels |
| **Sécurité** | Haute | Vérifier que l'utilisateur ne peut créer/modifier que des emails liés à ses candidatures |

**Fichiers concernés** :
- `jobplanner-api/src/Entity/RecruiterEmail.php` (Post, Patch dans ApiResource)

---

## 5. Mode simulation

### État actuel
- **Backend** : Variable `SIMULATION_MODE` existe
- **Frontend** : Aucun indicateur ni paramètre visible

### À faire

| Tâche | Priorité | Détails |
|-------|----------|---------|
| **Indicateur mode simulation** | Moyenne | Badge ou message dans Parametres ou Topbar : "Mode simulation activé - aucun email ne sera envoyé" |
| **API pour lire SIMULATION_MODE** | Basse | Endpoint `GET /api/settings` ou inclure dans une config globale |

---

## 6. Autres écarts identifiés

| Élément | État | Action |
|---------|------|--------|
| **applicationApi.createJobOffer / createApplication** | **Manquantes** | Appelées dans `createOffreEtCandidature` mais jamais définies → erreur à l'ajout d'offre. Ajouter ces deux méthodes qui font POST vers `/job_offers.jsonld` et `/applications.jsonld` |
| **CandidaturesKanban - bouton "Ajouter"** | À vérifier | S'il existe, doit ouvrir le formulaire d'ajout d'offre |
| **Calendrier** | Structure existante | Vérifier que les entretiens sont bien chargés et affichés |
| **Emails - envoi réel** | Non implémenté | Le frontend peut créer des RecruiterEmail en brouillon, mais l'envoi SMTP réel se fait côté backend (command/worker) |
| **Extension navigateur** | Non créée | Projet séparé |

---

## 7. Résumé des priorités

### P0 - Bloquant / critique
1. RecruiterEmail : ajouter Post et Patch
2. applicationApi : implémenter createJobOffer et createApplication (ou corriger createOffreEtCandidature)

### P1 - Important
3. Paramètres mail : API UserMailboxSettings + remplacer localStorage
4. Logs : API AiGenerationLog + chargement dans Parametres
5. Tasks : boutons "Add Task" / "Create New Task" fonctionnels

### P2 - Amélioration
6. UserMailboxSettings : ajouter SMTP
7. Mode simulation : indicateur frontend
8. Logs : entité plus complète (ActionLog)

---

## 8. Ordre d'implémentation suggéré

1. **Backend** : RecruiterEmail Post + Patch
2. **Frontend** : createJobOffer / createApplication dans applicationApi (si manquants)
3. **Backend** : UserMailboxSettings API (ApiResource)
4. **Frontend** : Paramètres mail → appeler API
5. **Backend** : AiGenerationLog API
6. **Frontend** : Logs → charger depuis API
7. **Frontend** : Tasks → handlers pour Add/Create
