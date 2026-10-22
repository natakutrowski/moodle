# CampusFR — CR / CRM Rights

## CR.1 — Cadrage et audit initial

Archive de référence : `subscriptions.zip`, plugin `local_subscriptions`, version `2026100902` (`version.php`).
Chantier indépendant de 7.98 Showroom A1 + Landing ; aucune modification de l'archive dans cette étape.

### Constat dans les sources

- 18 capabilities `local/subscriptions:*` déclarées dans `db/access.php` avec `contextlevel = CONTEXT_SYSTEM` et `manager => CAP_ALLOW`.
- Points de contrôle centralisés dans `classes/admin/AdminSecurity.php` via `require_capability()` et `has_capability()`.
- `classes/admin/Capabilities.php` ne contient pas de constante `MANAGE_SHOWROOMS`, malgré la capability déclarée.
- Aucune interface de gestion des affectations de rôles n'a été trouvée dans `admin/users/` ; les `role_assign` existants concernent surtout l'accès pédagogique/Commerce.
- `classes/subscription_manager.php`, `classes/trial_manager.php` et les services de fulfilment manipulent les rôles au **niveau cours** ; un changement de rôles système ne doit pas perturber ces mécanismes.

### Choix d'architecture

1. Réutiliser les rôles Moodle et la table native `role_assignments` (aucune table de rôles CRM maison).
2. Autoriser plusieurs rôles au contexte système pour un même utilisateur ; ne jamais remplacer implicitement tous les rôles existants.
3. Conserver indépendamment les rôles de cours (Student/Teacher) et les inscriptions/achats/progression.
4. Contrôler chaque action par `has_capability`/`require_capability`, jamais par nom/ID du rôle.
5. Pour les utilisateurs CRM, réserver les permissions Moodle critiques (`moodle/role:assign`, `moodle/role:manage`, `moodle/site:config`) à l'administration autorisée.
6. Utiliser une **allowlist** des rôles CampusFR administrables depuis le CRM ; ne pas permettre d'attribuer Administrator/Manager via le formulaire métier.
7. Séparer les droits d'affectation des rôles des droits ordinaires `manage_users` (nouvelle capability dédiée, à définir à CR.2).
8. Journaliser ajout/retrait (acteur, cible, rôle, date) et protéger les POST par sesskey.
9. Conserver l'administration Moodle standard comme solution de secours indépendante du CRM.

### Rôles cibles (provisoires)

- CRM / Operations Manager
- Customer Support
- Commerce / Sales Manager
- Finance / Comptabilité
- Content / Marketing Manager
- Technical Admin (rôle hautement privilégié)
- Tutor / Équipe pédagogique — pas d'accès CRM global en attendant les scopes par équipe.

Direction / Super Admin : utiliser les comptes administrateurs Moodle déjà contrôlés ; ne pas créer un pseudo-super-admin CRM qui contourne la sécurité.

### Plan de livraison

- **CR.1** : inventaire des capabilities/endpoints et catalogue de rôles ; validation des responsabilités.
- **CR.2** : remédiation des checks de lecture/écriture ; granularité des droits ; nouvelles capabilities ciblées.
- **CR.3** : création et mapping des rôles Moodle CampusFR, compatible avec les rôles multiples.
- **CR.4** : UX d'administration des rôles sur une fiche utilisateur existante ; affichage des rôles système et cours ; ajout/retrait unitaire sécurisé ; navigation conditionnelle.
- **CR.5** : tests automatisés et manuels des rôles simples/combinés, ajout à des comptes Student existants, retrait d'un seul rôle, non-régression des cours, tentatives de privilège interdites et contrôles négatifs des endpoints.

### Scénarios de recette indispensables

1. Un utilisateur Student A1 reçoit Support ; le cours A1 et sa progression sont inchangés.
2. Le même utilisateur reçoit Marketing ; Support + Marketing restent actifs.
3. Retirer Support conserve Marketing et Student A1.
4. Une personne sans rôle système se connecte ; elle ne voit pas le CRM.
5. Support peut utiliser Inbox mais ne peut pas modifier prix ou promotions.
6. Finance en lecture ne peut pas écrire de note sur un achat sans capability distincte.
7. Un gestionnaire de rôles CRM ne peut pas s'attribuer des rôles plus puissants ni modifier les rôles hors allowlist.
8. Un endpoint direct ne permet pas de contourner un bouton caché.
9. Les opérations de conversion Trial/Student et de fulfilment des cours continuent de fonctionner.
10. Le changement de rôle est traçable, vérifiable et réversible sans affecter les autres affectations.

### Hors périmètre immédiat

- Refonte des inscriptions/enrolments des cours.
- Rôles système limités à un sous-ensemble de clients ; nécessite un mécanisme de scopes séparé.
- Automatisation d'attribution de rôles à partir d'une commande Commerce.
