# Architecture développeur de `local_subscriptions`

Cette documentation présente les principes structurants du plugin Moodle `local_subscriptions`.

L’objectif de l’architecture est de maintenir un CRM riche sans concentrer les responsabilités dans les pages PHP ou dans les renderers.

## Domaines fonctionnels

Le plugin est organisé autour de plusieurs domaines :

- Dashboard CRM ;
- User Explorer ;
- CRM Intelligence ;
- Digital Purchases ;
- Command Center ;
- Help Center ;
- CRM Inbox ;
- profils et timeline ;
- abonnements et droits d’accès.

Chaque domaine peut contenir ses propres services, repositories, providers et objets de présentation.

## Séparation SQL, Services et Renderers

### SQL et repositories

Les requêtes SQL doivent être regroupées dans des repositories ou des classes dédiées à l’accès aux données.

Le repository :

- construit les requêtes ;
- utilise l’API DML Moodle ;
- gère les paramètres ;
- applique le tri et la pagination ;
- retourne des enregistrements ou objets simples.

Il ne doit pas générer de HTML.

### Services

Les services portent les règles métier :

- calcul d’un statut ;
- sélection d’un plan ;
- validation d’un upgrade ;
- calcul d’un montant ;
- classification CRM ;
- synchronisation d’accès ;
- orchestration entre plusieurs repositories.

Les services ne doivent pas dépendre des détails d’affichage.

### Renderers et templates

Les renderers préparent les données destinées aux templates.

Ils peuvent :

- construire des view models ;
- formater des dates ;
- préparer des URLs ;
- choisir des icônes ou libellés ;
- appeler `get_string()`.

Ils ne doivent pas exécuter de logique SQL ni décider d’une règle commerciale complexe.

## `AdminSecurity`

`AdminSecurity` centralise les contrôles d’accès administrateur.

Il doit être utilisé pour :

- vérifier les capabilities ;
- protéger les pages sensibles ;
- sécuriser les actions CRM ;
- éviter la duplication des contrôles ;
- garantir une stratégie homogène.

Toute action modifiant des données doit également vérifier le sesskey Moodle.

## `subscription_config`

`subscription_config` centralise les éléments de configuration partagés :

- chemins fonctionnels ;
- URLs d’administration ;
- identifiants de pages ;
- réglages du plugin ;
- valeurs structurantes réutilisées.

Il permet d’éviter les chaînes dupliquées et les routes construites manuellement dans plusieurs fichiers.

## Capabilities

Les capabilities définissent ce qu’un administrateur peut voir ou modifier.

Bonnes pratiques :

- une capability doit exister dans `db/access.php` avant d’être utilisée ;
- les noms doivent rester stables ;
- les pages doivent appeler `require_capability()` ou `AdminSecurity` ;
- une capability de lecture doit être distincte d’une capability de gestion lorsque le risque le justifie ;
- les upgrades doivent déclencher la mise à jour des définitions Moodle.

Ne jamais appeler une capability inexistante.

## Dashboard CRM

Le Dashboard agrège des indicateurs calculés par des services.

Il ne doit pas contenir directement des requêtes dispersées.

Les périodes doivent partager une définition commune des bornes temporelles.

## User Explorer

Le User Explorer repose sur :

- un objet de filtre ;
- un repository paginé ;
- des services de statut ;
- un renderer ;
- des liens de navigation entrants depuis CRM Intelligence.

Le tri doit être appliqué avant pagination.

## CRM Intelligence

CRM Intelligence produit des signaux exploitables.

Un signal doit être :

- compréhensible ;
- traçable ;
- navigable ;
- associé à une liste de profils ;
- calculé par une règle documentée.

## Digital Purchases

Ce domaine gère les achats, upgrades, devises et remboursements.

Les règles d’idempotence et de statut financier doivent résider dans les services.

## Command Center

Le Command Center est extensible via des providers.

Chaque provider doit respecter un contrat commun et vérifier les permissions avant de publier un résultat.

## Help Center

Le Help Center utilise des métadonnées et des fichiers Markdown traduits.

Une commande CLI de validation doit vérifier :

- les catégories ;
- les articles ;
- les guides ;
- les fichiers présents ;
- les traductions requises ;
- la cohérence des identifiants.

## CRM Inbox

CRM Inbox sépare :

- connecteurs ;
- stockage des credentials ;
- récupération des messages ;
- pièces jointes ;
- synchronisation ;
- services métier ;
- rendu.

Les credentials ne doivent jamais être exposés dans les logs ou les templates.

## Standards Moodle

Le plugin doit respecter :

- namespaces PSR-4 Moodle ;
- `defined('MOODLE_INTERNAL') || die();` ;
- API DML ;
- API Access ;
- API String ;
- API URL ;
- tâches planifiées ;
- CLI sécurisées ;
- fichiers `install.xml` et `upgrade.php` cohérents.

## Règle générale

Une page d’administration doit orchestrer, pas implémenter l’ensemble du métier.

Une structure saine ressemble à :

```text
Page PHP
  -> AdminSecurity
  -> Service
  -> Repository
  -> View model
  -> Renderer
  -> Template
```

Cette séparation rend les tests, les migrations et la maintenance beaucoup plus sûrs.
