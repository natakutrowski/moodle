# Commerce — Extension et développement

> **Version :** 7.93H  
> **Plugin :** `local_subscriptions`  
> **Public :** Développeurs et architectes  
> **Document associé :**
>
> - `commerce_overview.md`
> - `commerce_operations.md`
> - `commerce_diagnostics.md`

---

# Table des matières

1. Introduction
2. Philosophie d'extension
3. Principes fondamentaux
4. Vue générale des points d'extension
5. Les contrats (Contracts)
6. Les Registries
7. Les Handlers
8. Les Providers
9. Les Factories
10. Les Services
11. Les Repositories
12. Les Events
13. Les tests
14. Les bonnes pratiques
15. Les erreurs fréquentes
16. Évolutions futures
17. Conclusion

---

# Introduction

Le moteur Commerce a été conçu dès son origine pour être **extensible**.

L'objectif n'était pas uniquement de supporter les abonnements CampusFR actuels, mais de pouvoir accueillir de nouveaux produits, de nouveaux fournisseurs de paiement et de nouveaux workflows sans remettre en cause l'architecture existante.

Cette capacité d'évolution est l'un des principaux objectifs de la refactorisation 7.93.

Le présent document décrit les mécanismes permettant d'étendre proprement le moteur tout en conservant ses garanties d'architecture.

---

# Philosophie d'extension

Le moteur applique un principe simple :

> **Ajouter plutôt que modifier.**

Autrement dit, lorsqu'un nouveau besoin apparaît, la première question doit être :

```
Puis-je ajouter un composant ?
```

et non :

```
Dois-je modifier le Runtime ?
```

Dans la majorité des cas, la réponse doit être :

```
Ajouter un composant.
```

Cette approche réduit fortement les régressions.

---

# Les principes fondamentaux

Toute extension du moteur Commerce doit respecter les principes suivants.

---

## Respecter les responsabilités

Chaque composant possède une mission unique.

Par exemple :

```
Provider

↓

Communication avec le prestataire
```

et non :

```
Provider

↓

Paiement

↓

Subscription

↓

Emails
```

Les responsabilités ne doivent jamais être mélangées.

---

## Respecter les contrats

Le Runtime ne dialogue jamais avec une implémentation concrète.

Il dialogue uniquement avec des contrats.

```
Runtime

↓

Interface

↓

Implémentation
```

Une extension doit donc commencer par respecter le contrat attendu.

---

## Respecter le découplage

Une nouvelle fonctionnalité ne doit pas créer de dépendance inutile.

Exemple déconseillé :

```
Provider

↓

SubscriptionRepository
```

Un Provider n'a aucune raison de connaître les Subscriptions.

Le découplage est une règle essentielle.

---

# Vue générale des points d'extension

Le moteur peut être étendu à plusieurs niveaux.

```
Commerce Runtime

│

├── Purchase Handlers

├── Fulfillment Handlers

├── Providers

├── Validators

├── Registries

├── Factories

├── Services

└── Events
```

Chaque famille répond à un besoin particulier.

---

# Les Contracts

Les Contracts représentent les interfaces publiques du moteur.

Ils définissent :

- les méthodes disponibles ;
- leurs paramètres ;
- leur valeur de retour ;
- les garanties attendues.

Le Runtime dépend exclusivement de ces contrats.

---

## Pourquoi utiliser des Contracts ?

Sans contrat :

```
Runtime

↓

StripeProvider
```

Le Runtime dépend directement d'une implémentation.

Avec un contrat :

```
Runtime

↓

ProviderInterface

↓

StripeProvider
```

ou

```
Runtime

↓

ProviderInterface

↓

AlfaProvider
```

Le Runtime reste totalement indépendant.

---

## Évolution d'un contrat

Les interfaces publiques doivent évoluer avec prudence.

Toute modification peut affecter plusieurs implémentations.

Il est recommandé de privilégier :

- l'ajout de nouvelles méthodes ;
- des comportements compatibles avec les versions précédentes ;
- une documentation claire des changements.

---

# Les Registries

Les Registries constituent le mécanisme principal de découverte des composants.

Ils évitent toute dépendance directe entre le Runtime et les implémentations.

---

## Ajouter un nouveau composant

Le processus est généralement le suivant.

```
Créer le composant

↓

Implémenter le contrat

↓

L'enregistrer dans le Registry

↓

Tester
```

Le Runtime devient immédiatement capable de l'utiliser.

---

## Pourquoi ne pas utiliser un switch ?

Une approche classique serait :

```php
switch ($provider) {
    case 'stripe':
        ...
}
```

Cette solution présente plusieurs inconvénients :

- multiplication des conditions ;
- dépendances fortes ;
- difficulté de maintenance.

Le Registry élimine complètement ce problème.

---

# Les Purchase Handlers

Le Purchase Handler prépare un achat.

Il connaît les règles métier de son domaine.

Par exemple :

```
Prix

Upgrade

Promotions

Devise

Eligibilité
```

Il ne contacte jamais le Provider.

---

## Ajouter un nouveau Purchase Handler

Le développement suit généralement les étapes suivantes.

```
Créer la classe

↓

Implémenter le contrat

↓

Tester les validations

↓

Enregistrer le Handler

↓

Tests fonctionnels
```

Le Runtime ne nécessite aucune modification.

---

# Les Fulfillment Handlers

Le Fulfillment Handler est responsable de la livraison du produit.

Il intervient uniquement après validation complète du paiement.

Chaque type de produit possède son propre Handler.

Exemples :

```
Subscription

↓

SubscriptionFulfillmentHandler
```

```
Certification

↓

CertificationFulfillmentHandler
```

```
Marketplace

↓

MarketplaceFulfillmentHandler
```

Chaque Handler reste totalement indépendant des autres.

---

# Les Providers

Les Providers encapsulent les API externes.

Ils représentent l'un des principaux points d'extension du moteur Commerce.

Un nouveau Provider peut être ajouté sans modifier le Runtime.

---

## Ajouter un Provider

La procédure générale est la suivante.

```
Créer la classe

↓

Implémenter ProviderInterface

↓

Gérer les appels API

↓

Implémenter la validation

↓

Enregistrer le Provider

↓

Tester
```

Le Runtime continuera à fonctionner sans changement.

---

## Ce qu'un Provider ne doit jamais faire

Un Provider ne doit jamais :

- créer une Subscription ;
- envoyer des emails ;
- modifier le CRM ;
- ouvrir un cours Moodle ;
- créer un Entitlement.

Son rôle s'arrête à la communication avec le prestataire de paiement.

---

# Les Factories

Les Factories sont responsables de la création des objets complexes.

Leur objectif est double :

- centraliser les règles de construction ;
- éviter la duplication de code.

Une Factory ne contient pas de logique métier.

Elle construit simplement des objets cohérents.

---

## Pourquoi utiliser une Factory ?

Prenons un exemple.

Sans Factory :

```php
$purchase = new CommercePurchase();

$purchase->set_user($user);
$purchase->set_currency($currency);
$purchase->set_provider($provider);
$purchase->set_amount($amount);
...
```

Cette logique risque rapidement d'être dupliquée dans plusieurs classes.

Avec une Factory :

```php
$purchase = $purchasefactory->create(...);
```

Toute la logique de construction reste centralisée.

---

## Règles

Une Factory doit :

- créer des objets valides ;
- vérifier les préconditions minimales ;
- ne jamais effectuer d'accès réseau ;
- ne jamais appeler un Provider.

Une Factory construit.

Elle n'exécute pas le workflow.

---

# Les Services

Les Services regroupent les traitements réutilisables.

Ils représentent généralement la couche métier de plus bas niveau.

Exemples :

```
PriceCalculatorService

CurrencyService

TaxService

DiscountService

PaymentRequestService
```

Un Service ne connaît généralement pas le Runtime.

Il fournit une fonctionnalité isolée et réutilisable.

---

## Bonnes pratiques

Un Service doit être :

- indépendant ;
- testable ;
- déterministe ;
- facilement réutilisable.

Il ne doit jamais dépendre d'un contrôleur ou d'une interface utilisateur.

---

# Les Repositories

Les Repositories encapsulent tous les accès aux données.

Le reste du moteur Commerce ne devrait jamais exécuter directement des requêtes SQL.

Architecture recommandée :

```
Runtime

↓

Service

↓

Repository

↓

Base de données
```

Cette séparation facilite :

- les tests ;
- les évolutions ;
- les optimisations SQL ;
- les changements de stockage.

---

## Responsabilités

Un Repository est responsable de :

- charger ;
- rechercher ;
- enregistrer ;
- supprimer ;

des objets métier.

Il ne contient normalement aucune logique fonctionnelle.

---

# Les Events

Le moteur Commerce produit plusieurs événements internes.

Ces événements permettent aux autres composants du plugin de réagir sans créer de dépendances directes.

Architecture :

```
Commerce

↓

Event

↓

Autres composants
```

Le moteur ignore complètement qui écoute ces événements.

---

## Pourquoi utiliser des événements ?

Prenons un exemple.

Après la création d'une Subscription, plusieurs traitements peuvent être nécessaires :

- créer une Timeline ;
- mettre à jour le CRM ;
- envoyer un email ;
- générer des statistiques.

Sans événements :

```
Subscription

↓

Timeline

↓

CRM

↓

Emails

↓

Stats
```

La classe Subscription dépend alors de nombreux composants.

Avec un système d'événements :

```
Subscription créée

↓

Event

↓

Listener CRM

↓

Listener Timeline

↓

Listener Emails

↓

Listener Stats
```

Le couplage disparaît.

---

## Recommandations

Les événements doivent représenter un fait métier.

Par exemple :

```
PaymentValidated

SubscriptionCreated

PurchaseCompleted
```

À éviter :

```
ButtonClicked

ControllerCalled

MethodFinished
```

Les événements doivent rester indépendants des détails techniques.

---

# Les tests

L'architecture Commerce a été pensée pour être facilement testable.

Chaque couche peut être testée indépendamment.

```
Unit Tests

↓

Integration Tests

↓

Functional Tests
```

---

## Tests unitaires

Ils vérifient une classe isolée.

Exemple :

```
PriceCalculator

↓

Calcul attendu
```

Aucune communication réseau n'est nécessaire.

---

## Tests d'intégration

Ils vérifient les interactions entre plusieurs composants.

Exemple :

```
Runtime

↓

Registry

↓

Provider
```

Le comportement global est testé.

---

## Tests fonctionnels

Ils reproduisent un achat complet.

Exemple :

```
Checkout

↓

Paiement

↓

Webhook

↓

Fulfillment

↓

Accès Moodle
```

Ces tests permettent de valider le workflow dans son ensemble.

---

# Organisation recommandée

Lorsqu'une nouvelle fonctionnalité est développée, il est conseillé de procéder dans l'ordre suivant.

```
Contrats

↓

Implémentation

↓

Tests unitaires

↓

Tests d'intégration

↓

Tests fonctionnels

↓

Documentation
```

Cette méthode réduit fortement les régressions.

---

# Les erreurs fréquentes

L'expérience acquise pendant la refactorisation 7.93 a permis d'identifier plusieurs erreurs récurrentes.

---

## Ajouter de la logique dans le Runtime

Le Runtime doit rester un orchestrateur.

À éviter :

```
Runtime

↓

Calcul des prix

↓

Création Subscription

↓

Emails
```

À privilégier :

```
Runtime

↓

Handlers spécialisés
```

---

## Contourner les Registries

Il est déconseillé d'instancier directement les implémentations.

Exemple déconseillé :

```php
$provider = new StripeProvider();
```

Préférer :

```php
$provider = $providerregistry->get_provider(...);
```

---

## Mélanger Provider et métier

Un Provider ne doit jamais manipuler directement :

- des Subscriptions ;
- des produits ;
- des cours Moodle ;
- des Cohorts.

Il échange uniquement avec le prestataire de paiement.

---

## Ajouter des dépendances circulaires

Architecture déconseillée :

```
Provider

↓

Runtime

↓

Provider
```

ou

```
Handler

↓

Handler

↓

Handler
```

Le Runtime doit rester le point central de coordination.

---

## Créer des effets de bord

Une méthode doit faire ce que son nom annonce.

Exemple déconseillé :

```
validate_payment()

↓

Valide

+

Crée Subscription

+

Envoie Email
```

Les responsabilités doivent rester explicites.

---

# Évolutions futures

L'architecture Commerce a été conçue pour évoluer progressivement.

Les principales pistes d'évolution envisagées sont les suivantes.

---

## Nouveaux Providers

L'ajout de nouveaux prestataires de paiement ne nécessite normalement que :

- une nouvelle implémentation du contrat Provider ;
- son enregistrement dans le Registry ;
- les tests associés.

Le Runtime reste inchangé.

---

## Nouveaux types de produits

Le moteur peut accueillir de nouveaux domaines métier.

Par exemple :

```
Marketplace

Coaching

Abonnements récurrents avancés

Examens

Certifications

Services
```

Chaque nouveau domaine apporte ses propres Handlers.

---

## Nouveaux workflows

Le moteur pourra également intégrer de nouveaux scénarios :

- paiements différés ;
- paiements fractionnés ;
- abonnements renouvelables ;
- coupons avancés ;
- cartes cadeaux ;
- achats groupés.

L'objectif est de conserver un Runtime stable malgré l'enrichissement des fonctionnalités.

---

## Internationalisation

L'architecture actuelle facilite déjà la prise en charge :

- de plusieurs devises ;
- de plusieurs langues ;
- de plusieurs prestataires selon le pays.

Cette capacité constitue un élément essentiel pour le développement international de CampusFR.

---

# Recommandations finales

Avant d'ajouter une nouvelle fonctionnalité, il est conseillé de se poser systématiquement les questions suivantes :

```
Est-ce un nouveau type d'achat ?

↓

Est-ce un nouveau Provider ?

↓

Est-ce un nouveau Handler ?

↓

Est-ce un nouveau Service ?

↓

Est-ce simplement une règle métier supplémentaire ?
```

Dans la majorité des cas, une extension correcte consiste à **ajouter** un composant plutôt qu'à modifier les composants existants.

Cette discipline permet de préserver la stabilité de l'ensemble du moteur.

---

# Conclusion

Le moteur Commerce issu de la Phase **7.93** constitue une architecture modulaire, découplée et extensible.

Son organisation autour de :

- Contracts ;
- Registries ;
- Runtime ;
- Handlers ;
- Providers ;
- Services ;
- Repositories ;
- Events ;

permet de faire évoluer le système sans remettre en cause les composants existants.

En respectant les principes décrits dans ce document, les futurs développements pourront s'intégrer naturellement au moteur tout en conservant :

- la lisibilité du code ;
- la robustesse des traitements ;
- l'idempotence ;
- la testabilité ;
- la maintenabilité.

Cette documentation constitue la référence de développement du moteur Commerce et doit servir de guide pour toute évolution future.

---

# Voir aussi

- **`commerce_overview.md`** — Concepts métier et architecture générale.
- **`commerce_operations.md`** — Déroulement opérationnel du cycle de vie d'un achat.
- **`commerce_diagnostics.md`** — Audits, diagnostics, reprise après incident et procédures d'exploitation.

---
**Fin de la documentation technique Commerce (version française).**