# Fonctionnalités manquantes ou incomplètes - JobPlanner

Ce document identifie ce qui reste à implémenter selon le plan de présentation.

## 1. Extension navigateur

**État** : ✅ Implémenté

**Fait** :
- [x] Manifest.json (Chrome MV3)
- [x] Content script pour récupérer le contenu de la page (titre, URL, texte)
- [x] Popup pour prévisualiser et confirmer l'ajout
- [x] Options page pour configurer token JWT et URL API

**Fichiers** : `jobplanner-extension/`

---

## 2. Relances automatiques

**État** : Entités créées (FollowUpRule, ScheduledFollowUp), logique partielle

**À faire** :
- [ ] Service de génération de mails via IA (`AiServiceInterface::generateFollowUpEmail()`)
- [ ] Command Symfony pour traiter les relances planifiées
- [ ] Planification automatique lors de la création/modification d'une candidature
- [ ] Interface frontend pour configurer les règles et visualiser les relances planifiées
- [ ] Validation manuelle avant envoi automatique

**Fichiers** :
- `jobplanner-api/src/Command/ProcessFollowUpsCommand.php` (à créer)
- `jobplanner-api/src/Schedule.php` (ajouter tâche récurrente)
- Service de planification des relances

---

## 3. Planification des rendez-vous

**État** : Entité Interview et API prêtes, calendrier structure existante

**À faire** :
- [ ] Intégration complète du calendrier avec les entretiens
- [ ] Création/modification d'entretiens depuis le calendrier
- [ ] Rappels avant les entretiens (command + email/notification)
- [ ] Configurer l'heure d'envoi des rappels (ex: 24h avant)

**Fichiers** :
- `jobplanner-frontend/src/views/calendrier/Calendrier.vue`
- `jobplanner-api/src/Command/SendInterviewRemindersCommand.php` (à créer)

---

## 4. Intelligence artificielle

**État** : Interface abstraite + NullAiService par défaut

**À faire** :
- [ ] OpenAiService : utilisation de l'API OpenAI (GPT-4, GPT-3.5)
- [ ] AnthropicService : utilisation de l'API Anthropic (Claude)
- [ ] Configuration via variables d'environnement (`AI_PROVIDER`, `OPENAI_API_KEY`)
- [ ] Service container pour sélectionner l'implémentation
- [ ] Amélioration du ton (professionnel, neutre, dynamique) dans `generateFollowUpEmail()`

**Fichiers** :
- `jobplanner-api/src/Service/Ai/OpenAiService.php` (à créer)
- `jobplanner-api/src/Service/Ai/AnthropicService.php` (à créer)
- `jobplanner-api/config/services.yaml` (configuration)

---

## 5. Dashboard et suivi

**État** : Structure existante (Dashboard.vue, StatsWidget, EntretiensWidget)

**À faire** :
- [ ] Statistiques réelles (nombre de candidatures par statut, entretiens à venir)
- [ ] Prochaines actions (relances à envoyer, entretiens prochains)
- [ ] Graphiques et visualisations (Chart.js disponible)
- [ ] Activité récente (ApplicationHistory)
- [ ] Endpoint API pour les statistiques agrégées (optionnel)

**Fichiers** :
- `jobplanner-frontend/src/views/Dashboard.vue`
- `jobplanner-frontend/src/components/dashboard/`

---

## 6. Scheduler

**État** : Schedule.php existe, tâches non configurées

**À faire** :
- [ ] Tâche récurrente : synchronisation emails IMAP (SyncEmailsMessage)
- [ ] Tâche récurrente : traitement des relances planifiées
- [ ] Tâche récurrente : rappels d'entretiens

**Fichiers** :
- `jobplanner-api/src/Schedule.php`

---

## 7. Améliorations diverses

**À faire** :
- [ ] Mode simulation : indicateur visible dans le frontend (badge ou message)
- [ ] Bouton "Tester la connexion" IMAP/SMTP dans les paramètres
- [ ] Filtres sur les logs (date, type)
- [ ] Tests unitaires et d'intégration
- [ ] Documentation API complète (Swagger)
- [ ] CI/CD
- [ ] Monitoring et logs structurés

---

## Priorités recommandées

1. **P0** : Scheduler (synchronisation emails + relances)
2. **P1** : Extension navigateur
3. **P1** : Services IA concrets (OpenAI)
4. **P2** : Dashboard avec statistiques
5. **P2** : Rappels entretiens
6. **P3** : Tests et documentation
