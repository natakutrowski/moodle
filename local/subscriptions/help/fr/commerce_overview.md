# Commerce — Vue d'ensemble de l'architecture

> **Version :** 7.93H  
> **Plugin :** `local_subscriptions`  
> **Public :** Développeurs, architectes, mainteneurs, intégrateurs  
> **Document associé :**
>
> - `commerce_operations.md`
> - `commerce_diagnostics.md`
> - `commerce_extension.md`

---

# Table des matières

1. Introduction
2. Historique
3. Pourquoi une refactorisation Commerce ?
4. Vision générale
5. Les concepts métier
6. Les grandes couches de l'architecture
7. Les composants principaux
8. Le cycle de vie d'un achat
9. Les différents types d'achats
10. Les Payment Providers
11. Le Runtime Commerce
12. Les Registries
13. Les Handlers
14. Le Fulfillment
15. Les garanties d'architecture
16. Les principes de conception
17. Glossaire
18. Références

---

# Introduction

Le moteur **Commerce** constitue aujourd'hui le cœur de toute la logique commerciale de CampusFR.

Son objectif est simple :

> **Transformer une intention d'achat en un accès métier, de manière fiable, sécurisée, extensible et indépendante du fournisseur de paiement.**

Autrement dit, le moteur Commerce ne vend pas uniquement des abonnements.

Il orchestre l'ensemble du cycle de vie d'un achat :

```
Client
    │
    ▼
Checkout
    │
    ▼
Provider de paiement
    │
    ▼
Validation
    │
    ▼
Fulfillment
    │
    ▼
Accès livré
```

Cette architecture est volontairement indépendante :

- de Stripe ;
- d'Alfa Bank ;
- des abonnements ;
- des produits numériques.

Elle constitue désormais une plateforme de commerce générique.

---

# Historique

Les premières versions de `local_subscriptions` reposaient sur deux mondes distincts.

```
Subscriptions
```

et

```
Digital Purchases
```

possédaient chacun :

- leur checkout ;
- leurs services ;
- leurs handlers ;
- leurs providers ;
- leurs validations.

Cela fonctionnait mais présentait plusieurs défauts.

Par exemple :

- duplication de logique ;
- maintenance difficile ;
- ajout d'un nouveau provider coûteux ;
- risques de divergences métier.

La Phase **7.93** introduit une refactorisation complète afin d'unifier ces deux univers.

---

# Pourquoi une refactorisation Commerce ?

Les objectifs principaux étaient :

- supprimer les duplications ;
- unifier les paiements ;
- unifier le checkout ;
- centraliser les règles métier ;
- rendre l'ajout de nouveaux produits trivial.

L'idée fondamentale est inspirée de la programmation orientée objet.

Au lieu de raisonner :

```
Subscription

OU

Digital Purchase
```

on raisonne désormais :

```
Commerce Purchase
        ▲
        │
 ┌──────┴────────┐
 │               │
Subscription   DigitalPurchase
```

Chaque achat possède :

- une identité ;
- un checkout ;
- un paiement ;
- un fulfillment.

Seule la spécialisation métier change.

---

# Vision générale

Le moteur Commerce peut être représenté de la manière suivante.

```
                +----------------------+
                |    Commerce API      |
                +----------+-----------+
                           |
                           |
                    Checkout Runtime
                           |
                           |
              +------------+-------------+
              |                          |
              |                          |
      Purchase Handler          Provider Handler
              |                          |
              +------------+-------------+
                           |
                    Payment Provider
                           |
                   (Stripe / Alfa...)
                           |
                           ▼
                  Payment Validation
                           |
                           ▼
                 Fulfillment Runtime
                           |
               +-----------+-----------+
               |                       |
      Subscription                Digital Product
```

Chaque couche possède une responsabilité unique.

Cette séparation est l'un des principes majeurs de l'architecture.

---

# Les concepts métier

Avant de comprendre les classes, il est indispensable de comprendre les concepts.

Le moteur Commerce repose sur quelques notions simples.

---

## Commerce Purchase

Une **Commerce Purchase** représente un achat.

Ce n'est pas un abonnement.

Ce n'est pas un produit numérique.

C'est simplement :

> Une demande d'acquisition d'un droit.

Exemples :

```
Acheter A1 Premium

Acheter B2

Acheter un ebook

Acheter une certification

Acheter un coaching
```

Toutes ces opérations sont des Commerce Purchases.

---

## Purchase Type

Le type d'achat décrit la nature métier.

Par exemple :

```
SUBSCRIPTION

DIGITAL_PRODUCT

CERTIFICATION

COACHING

DONATION
```

Le moteur Commerce ne connaît pas leur logique interne.

Il délègue cette logique au Handler correspondant.

---

## Checkout

Le Checkout est la phase durant laquelle l'utilisateur prépare son achat.

Il comprend notamment :

- validation des paramètres ;
- détermination du prix ;
- devise ;
- provider ;
- génération du Payment Request.

Le Checkout ne délivre jamais d'accès.

Il prépare uniquement le paiement.

---

## Payment Request

Le Payment Request représente la transaction à envoyer au fournisseur de paiement.

Il contient par exemple :

```
Montant

Devise

Client

Description

Purchase

Provider

Métadonnées
```

Il constitue le contrat entre CampusFR et le fournisseur de paiement.

---

## Provider

Le Provider est un connecteur vers un système externe.

Exemples :

```
Stripe

Alfa Bank

...
```

Le moteur Commerce ne connaît jamais leurs API directement.

Il dialogue uniquement via un contrat commun.

---

## Validation

Une fois le paiement terminé, le Provider informe CampusFR du résultat.

La validation consiste à vérifier :

- authenticité ;
- signature ;
- montant ;
- devise ;
- statut.

Aucune livraison métier n'est effectuée tant que cette validation n'est pas terminée.

---

## Fulfillment

Le Fulfillment représente la livraison du produit.

Par exemple :

```
Créer une subscription

Attribuer un accès

Créer une licence

Débloquer un contenu

Créer un droit
```

Le Fulfillment intervient uniquement après validation complète du paiement.

Il est totalement indépendant du Provider.

C'est une règle fondamentale de l'architecture.

---

# Les grandes couches de l'architecture

L'architecture est organisée selon plusieurs couches indépendantes.

```
Présentation
        │
        ▼
Checkout
        │
        ▼
Commerce Runtime
        │
        ▼
Provider
        │
        ▼
Fulfillment
        │
        ▼
Services métier
```

Chaque couche ne connaît que la couche immédiatement inférieure.

Cela limite fortement les dépendances.

---

## Présentation

La couche Présentation comprend :

- les pages Moodle ;
- les contrôleurs ;
- les API AJAX ;
- les endpoints.

Elle ne contient aucune logique commerciale.

Elle délègue immédiatement au Runtime.

---

## Runtime

Le Runtime orchestre l'ensemble des opérations.

Il décide :

- quel handler utiliser ;
- quel provider utiliser ;
- quel workflow exécuter.

Il constitue le chef d'orchestre du moteur Commerce.

---

## Providers

Les Providers encapsulent toutes les API externes.

Ils connaissent :

- Stripe ;
- Alfa Bank ;
- leurs signatures ;
- leurs endpoints ;
- leurs formats JSON.

Le reste du moteur ignore complètement ces détails.

---

## Fulfillment

Le Fulfillment transforme un paiement validé en effet métier.

Exemple :

```
Paiement validé

↓

Création Subscription

↓

Activation des accès

↓

Envoi des emails
```

Cette séparation garantit qu'un changement de Provider n'a jamais d'impact sur la logique métier.

---

# Les composants principaux

L'architecture Commerce est composée de plusieurs familles de composants spécialisées.

```
Commerce Runtime
│
├── Registries
├── Providers
├── Purchase Handlers
├── Fulfillment Handlers
├── Validators
├── Factories
├── Services
├── Repositories
└── Events
```

Chaque famille répond à une responsabilité unique. Les sections suivantes détaillent leur rôle et leurs interactions.

# Les Registries

Les **Registries** sont des composants de découverte.

Leur rôle n'est pas d'exécuter de logique métier mais de permettre au moteur Commerce de retrouver dynamiquement le composant capable de traiter une demande.

On peut les comparer à un annuaire.

```
                Runtime
                    │
        demande un Handler
                    │
                    ▼
          PurchaseRegistry
                    │
      retourne le Handler adapté
```

Le Runtime ne connaît donc jamais les classes concrètes.

Il ne dépend que des interfaces.

Cette approche offre plusieurs avantages :

- faible couplage ;
- ajout de nouvelles fonctionnalités sans modification du Runtime ;
- meilleure testabilité ;
- architecture ouverte aux extensions.

---

## Pourquoi utiliser des Registries ?

Prenons un exemple simple.

Sans Registry :

```php
if ($type === 'subscription') {
    $handler = new SubscriptionPurchaseHandler();
} else if ($type === 'digital') {
    $handler = new DigitalPurchaseHandler();
}
```

À chaque nouveau type d'achat, il faudrait modifier cette logique.

Avec un Registry :

```php
$handler = $registry->get_handler($purchasetype);
```

Le Runtime reste inchangé.

L'ajout d'un nouveau type d'achat consiste uniquement à enregistrer un nouveau Handler.

C'est un principe majeur de l'architecture Commerce :

> **Le moteur doit être ouvert à l'extension mais fermé à la modification.**

(C'est le principe **Open/Closed** décrit par SOLID.)

---

## Les principaux Registries

Le moteur Commerce s'appuie sur plusieurs Registries.

```
PurchaseHandlerRegistry

ProviderRegistry

FulfillmentRegistry

ValidatorRegistry
```

Chaque Registry gère une famille de composants.

Ils fonctionnent tous selon le même principe.

---

## PurchaseHandlerRegistry

Le Purchase Handler Registry permet de retrouver le composant responsable d'un type d'achat.

```
Purchase Type
        │
        ▼
PurchaseHandlerRegistry
        │
        ▼
PurchaseHandler
```

Exemple :

```
SUBSCRIPTION

↓

SubscriptionPurchaseHandler
```

ou

```
DIGITAL_PRODUCT

↓

DigitalProductPurchaseHandler
```

Le Runtime n'a jamais besoin de connaître les classes exactes.

---

## ProviderRegistry

Le Provider Registry sélectionne le fournisseur de paiement.

```
STRIPE

↓

StripeProvider
```

ou

```
ALFA

↓

AlfaBankProvider
```

Là encore, le Runtime ne dépend d'aucune implémentation particulière.

---

## FulfillmentRegistry

Le Fulfillment Registry choisit le composant chargé de livrer le produit.

Par exemple :

```
SubscriptionPurchase

↓

SubscriptionFulfillmentHandler
```

ou

```
DigitalProductPurchase

↓

DigitalProductFulfillmentHandler
```

Cette séparation permet d'utiliser le même Provider de paiement pour plusieurs produits différents.

---

## ValidatorRegistry

Certaines validations sont spécifiques.

Par exemple :

- contrôle d'un webhook Stripe ;
- validation d'une signature Alfa Bank ;
- contrôle d'un callback interne.

Le Validator Registry sélectionne automatiquement le validateur adapté.

---

# Les Handlers

Les Handlers constituent le cœur de la logique métier.

Contrairement aux Providers, ils ne parlent jamais à un service externe.

Ils manipulent uniquement les objets métier.

Leur responsabilité est volontairement limitée.

---

## Principe

Chaque Handler répond à une question précise.

Exemple :

```
Comment créer une Subscription ?

↓

SubscriptionPurchaseHandler
```

ou

```
Comment livrer un produit numérique ?

↓

DigitalProductFulfillmentHandler
```

Chaque Handler ne connaît qu'un seul domaine métier.

---

## Purchase Handler

Le Purchase Handler intervient avant le paiement.

Ses responsabilités sont notamment :

- vérifier les paramètres ;
- construire la Purchase ;
- déterminer le prix ;
- sélectionner les options disponibles ;
- préparer le Checkout.

Il ne contacte jamais Stripe.

Il ne crée jamais d'abonnement.

Il prépare simplement l'opération.

---

## Fulfillment Handler

Le Fulfillment Handler intervient après la validation du paiement.

Il est responsable de la livraison.

Exemple :

```
Paiement validé

↓

Créer Subscription

↓

Créer Entitlements

↓

Créer accès Moodle

↓

Créer Timeline CRM

↓

Envoyer les emails

↓

Déclencher les événements
```

Le Fulfillment Handler représente donc la partie la plus métier du moteur Commerce.

---

## Une règle importante

Un Handler ne doit jamais appeler directement un autre Handler.

Toutes les coordinations passent par le Runtime.

Schéma :

```
          Runtime

      /      |       \

Handler   Provider   Fulfillment
```

Jamais :

```
Handler A

↓

Handler B

↓

Handler C
```

Cette règle évite les dépendances circulaires.

---

# Le Runtime Commerce

Le Runtime est le véritable orchestrateur du moteur Commerce.

Il ne réalise quasiment aucun traitement métier.

Il coordonne uniquement les composants spécialisés.

On peut le comparer à un chef d'orchestre.

```
         Runtime

            │

    ┌───────┼────────┐

    ▼       ▼        ▼

Registry Provider Fulfillment
```

Chaque composant joue son rôle.

Le Runtime coordonne simplement leur exécution.

---

## Pourquoi un Runtime ?

Sans Runtime, les dépendances deviennent rapidement complexes.

Exemple :

```
Checkout

↓

Subscription

↓

Stripe

↓

Webhook

↓

Subscription

↓

Emails

↓

CRM

↓

Timeline

↓

Events
```

Chaque composant finit par dépendre de tous les autres.

Avec le Runtime :

```
Checkout

↓

Runtime

↓

Handlers

↓

Provider

↓

Fulfillment
```

Les dépendances restent parfaitement maîtrisées.

---

## Responsabilités

Le Runtime est responsable de :

- sélectionner les Handlers ;
- sélectionner le Provider ;
- coordonner le Checkout ;
- coordonner le paiement ;
- lancer le Fulfillment ;
- déclencher les événements ;
- garantir l'ordre d'exécution.

En revanche, il ne décide jamais :

- des prix ;
- des règles métier ;
- des accès ;
- des emails.

Ces responsabilités appartiennent aux composants spécialisés.

---

# Le cycle de vie complet d'un achat

Le diagramme suivant résume le fonctionnement général du moteur Commerce.

```
Utilisateur

    │

    ▼

Choix du produit

    │

    ▼

Purchase Handler

    │

    ▼

Checkout

    │

    ▼

Payment Request

    │

    ▼

Payment Provider

    │

    ▼

Paiement accepté

    │

    ▼

Validation

    │

    ▼

Fulfillment Runtime

    │

    ▼

Fulfillment Handler

    │

    ▼

Accès Moodle

    │

    ▼

Emails

    │

    ▼

CRM

    │

    ▼

Fin
```

Ce schéma constitue la colonne vertébrale de toute l'architecture Commerce.

Tous les types d'achats suivent cette séquence.

Les différences résident uniquement dans les Handlers spécialisés.

# Les différents types d'achats

L'un des objectifs majeurs de la Phase 7.93 était de faire disparaître la distinction technique entre les différentes familles de produits.

Avant cette refactorisation, chaque type d'achat disposait de son propre moteur.

Aujourd'hui, le moteur Commerce considère qu'un achat est avant tout une **Commerce Purchase**, quel que soit le produit vendu.

Le comportement métier est ensuite délégué à des composants spécialisés.

Cette approche permet de conserver une architecture unique tout en laissant chaque domaine exprimer ses propres règles.

---

## Architecture générale

```
                  Commerce Purchase
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
        ▼                 ▼                 ▼
 Subscription      Digital Product    Future Types
        │                 │                 │
        ▼                 ▼                 ▼
Specific Handler   Specific Handler  Specific Handler
```

Le moteur Commerce ne manipule jamais directement une Subscription ou un produit numérique.

Il manipule uniquement une **Purchase**, puis délègue le comportement.

---

## Subscription

Le cas le plus courant est l'achat d'un abonnement.

Par exemple :

```
CampusFR A1

CampusFR A2

CampusFR Premium

Lifetime

Upgrade

Renouvellement
```

Le Handler spécialisé connaît :

- les plans ;
- les règles d'upgrade ;
- les entitlements ;
- les accès Moodle ;
- les périodes de validité.

Toutes ces règles restent confinées au domaine Subscription.

Le Runtime n'en a aucune connaissance.

---

## Digital Product

Le deuxième domaine concerne les produits numériques.

Par exemple :

```
ebook

PDF

Pack audio

Mini-cours

Certification téléchargeable

Ressource pédagogique
```

Dans ce cas, le Fulfillment ne crée pas de Subscription.

Il peut par exemple :

- créer une licence ;
- débloquer un téléchargement ;
- ouvrir une ressource Moodle ;
- générer une clé d'accès.

Le paiement, lui, reste identique.

---

## Pourquoi cette séparation ?

Deux achats peuvent partager :

- le même Checkout ;
- le même Provider ;
- les mêmes contrôles de sécurité ;
- le même système de paiement.

Mais produire des effets métier totalement différents.

Exemple :

```
Paiement Stripe

        │

        ▼

Subscription
```

ou

```
Paiement Stripe

        │

        ▼

Téléchargement PDF
```

Le moteur ne mélange jamais ces deux responsabilités.

---

## Évolution future

Cette architecture permet d'ajouter facilement de nouveaux domaines.

Par exemple :

```
Coaching

Formation individuelle

Certification officielle

Paiement d'examen

Donation

Marketplace
```

Le Runtime ne change pas.

Il suffit d'ajouter :

- un Purchase Handler ;
- un Fulfillment Handler ;
- éventuellement quelques règles métier.

Cette évolutivité est l'un des principaux bénéfices de la refactorisation.

---

# Les Payment Providers

Les Providers constituent la frontière entre CampusFR et le monde extérieur.

Ils encapsulent intégralement les API des prestataires de paiement.

Le reste du moteur Commerce ne dépend jamais d'une API spécifique.

---

## Principe

```
          Runtime

              │

              ▼

      Provider Interface

       ┌──────┴──────┐

       ▼             ▼

   Stripe       Alfa Bank
```

Le Runtime dialogue uniquement avec une interface commune.

Chaque Provider implémente ensuite cette interface selon les exigences de son API.

---

## Responsabilités d'un Provider

Un Provider est responsable de :

- créer une session de paiement ;
- transmettre les données au prestataire ;
- recevoir les retours ;
- vérifier les signatures ;
- interpréter les réponses ;
- retourner un résultat normalisé.

Il ne décide jamais :

- de la création d'une Subscription ;
- de la livraison d'un produit ;
- des accès Moodle.

Cette séparation est essentielle.

---

## Contrat d'un Provider

Chaque Provider doit respecter le même contrat logique.

Par exemple :

```
Créer un paiement

↓

Retourner une URL de paiement

↓

Recevoir une validation

↓

Retourner un résultat normalisé
```

Ainsi, le Runtime peut remplacer un Provider par un autre sans modifier son fonctionnement.

---

## Exemple

```
Checkout

↓

ProviderRegistry

↓

StripeProvider

↓

Stripe API

↓

Réponse Stripe

↓

Commerce Result
```

ou

```
Checkout

↓

ProviderRegistry

↓

AlfaProvider

↓

Alfa API

↓

Réponse Alfa

↓

Commerce Result
```

Le reste de l'application ignore complètement les différences.

---

## Pourquoi encapsuler les Providers ?

Les API de paiement évoluent régulièrement.

Nouveaux endpoints.

Nouvelles signatures.

Nouvelles contraintes.

Si ces détails étaient dispersés dans tout le projet, chaque évolution deviendrait très coûteuse.

L'encapsulation garantit que les modifications restent limitées à un périmètre réduit.

---

# Le processus de Checkout

Le Checkout constitue la phase de préparation d'un achat.

Il ne réalise aucun paiement.

Il prépare simplement toutes les informations nécessaires.

---

## Étapes

```
Utilisateur

↓

Produit sélectionné

↓

Validation

↓

Construction Purchase

↓

Calcul du prix

↓

Choix du Provider

↓

Création Payment Request

↓

Redirection
```

À ce stade :

- aucun accès n'est créé ;
- aucune Subscription n'existe encore ;
- aucun produit n'est livré.

---

## Pourquoi cette séparation ?

Le Checkout peut échouer.

Exemple :

- plan supprimé ;
- prix invalide ;
- devise indisponible ;
- utilisateur interdit ;
- provider inactif.

Il serait dangereux de créer immédiatement les accès.

Le Checkout est donc volontairement sans effet métier durable.

---

# Le Fulfillment

Le Fulfillment représente la dernière étape du cycle de vie d'un achat.

Il ne démarre qu'après une validation complète du paiement.

C'est seulement à ce moment que le moteur transforme un paiement en droits réels.

---

## Schéma général

```
Paiement validé

        │

        ▼

Fulfillment Runtime

        │

        ▼

Fulfillment Handler

        │

        ▼

Effets métier
```

Les effets métier dépendent du type d'achat.

---

## Exemple : Subscription

```
Paiement accepté

↓

Créer Subscription

↓

Créer Entitlements

↓

Créer accès Moodle

↓

Créer Timeline CRM

↓

Envoyer emails

↓

Déclencher événements
```

---

## Exemple : Produit numérique

```
Paiement accepté

↓

Créer Licence

↓

Débloquer téléchargement

↓

Historique CRM

↓

Emails

↓

Fin
```

Le moteur Commerce considère ces deux scénarios comme deux variantes d'un même processus.

---

# Une règle fondamentale : le paiement ne livre jamais le produit

L'une des erreurs les plus fréquentes dans les moteurs de paiement consiste à confondre :

- validation financière ;
- livraison métier.

Dans Commerce, ces deux responsabilités sont totalement indépendantes.

```
Paiement

↓

Validation

↓

Fulfillment

↓

Produit livré
```

Jamais :

```
Paiement

↓

Produit livré

↓

Validation
```

Cette règle permet notamment :

- de rejouer un Fulfillment ;
- de corriger un incident ;
- d'ajouter des audits ;
- d'assurer l'idempotence des traitements.

Elle constitue l'un des piliers de la robustesse du moteur Commerce.

# Les garanties d'architecture

Une architecture Commerce ne se juge pas uniquement sur sa capacité à effectuer un paiement.

Elle doit également garantir que les traitements restent fiables, cohérents et reproductibles dans toutes les situations :

- paiement interrompu ;
- double clic utilisateur ;
- webhook reçu plusieurs fois ;
- panne serveur ;
- timeout réseau ;
- rollback manuel.

La Phase 7.93 a été conçue autour de plusieurs garanties fondamentales.

---

# Séparation stricte des responsabilités

Chaque composant possède une responsabilité unique.

```
UI

↓

Checkout

↓

Runtime

↓

Provider

↓

Validation

↓

Fulfillment

↓

Services métier
```

Aucune couche ne doit effectuer le travail d'une autre.

Par exemple :

Le Provider ne crée jamais une Subscription.

Le Fulfillment ne contacte jamais Stripe.

Le Runtime ne calcule jamais les prix.

Le Checkout ne crée jamais les accès.

Cette règle simplifie considérablement la maintenance.

---

# Faible couplage

Les composants communiquent exclusivement via leurs contrats publics.

Le Runtime ignore les implémentations concrètes.

Exemple :

```
Runtime

↓

Provider Interface

↓

Stripe
```

ou

```
Runtime

↓

Provider Interface

↓

Alfa
```

Le Runtime ne dépend donc jamais d'une classe spécifique.

---

# Ouvert à l'extension

L'ajout d'un nouveau produit ne nécessite normalement aucune modification du Runtime.

Il suffit de fournir :

```
Purchase Handler

+

Fulfillment Handler

+

Enregistrement dans le Registry
```

Même principe pour un nouveau Provider.

```
Provider

↓

Provider Registry
```

Le reste du moteur continue de fonctionner sans modification.

---

# Idempotence

L'idempotence est une propriété essentielle d'un moteur de paiement.

Elle garantit qu'une même opération peut être exécutée plusieurs fois sans produire plusieurs effets métier.

Exemple :

```
Webhook Stripe

↓

Fulfillment

↓

Subscription créée
```

Si Stripe renvoie le même webhook :

```
Webhook Stripe

↓

Fulfillment

↓

Aucune deuxième Subscription
```

Le résultat final reste identique.

Cette propriété protège le moteur contre :

- les doublons réseau ;
- les retries automatiques ;
- les erreurs utilisateurs ;
- les traitements asynchrones.

---

# Transactions

Les opérations critiques doivent être atomiques.

Autrement dit :

```
Tout réussit

OU

Tout échoue
```

Jamais :

```
Subscription créée

MAIS

Entitlements absents
```

ou

```
Paiement validé

MAIS

Accès Moodle non créés
```

Les traitements critiques doivent être protégés par des transactions lorsque cela est pertinent.

---

# Déterminisme

À partir des mêmes données d'entrée, le moteur doit toujours produire le même résultat.

Exemple :

```
Même Purchase

+

Même Payment

=

Même Fulfillment
```

Ce principe facilite énormément :

- les audits ;
- les tests ;
- les relectures de logs ;
- les reprises après incident.

---

# Traçabilité

Toutes les étapes importantes doivent être observables.

Par exemple :

```
Checkout démarré

↓

Payment Request créée

↓

Paiement envoyé

↓

Paiement validé

↓

Fulfillment démarré

↓

Fulfillment terminé

↓

Emails envoyés
```

Cette traçabilité simplifie :

- le support ;
- les audits ;
- les diagnostics ;
- les investigations.

---

# Tolérance aux erreurs

Une erreur dans une partie du moteur ne doit pas contaminer l'ensemble du workflow.

Par exemple :

```
Paiement validé

↓

Erreur Email
```

Le paiement reste valide.

La Subscription reste créée.

L'envoi de mail pourra être rejoué indépendamment.

Cette séparation augmente fortement la résilience du système.

---

# Les principes de conception

Le moteur Commerce applique plusieurs principes d'architecture issus des bonnes pratiques modernes.

---

## Single Responsibility Principle

Chaque classe possède une responsabilité unique.

Exemple :

```
CheckoutBuilder

↓

Préparer le checkout
```

Pas :

```
Préparer

+

Payer

+

Créer Subscription

+

Envoyer Emails
```

---

## Open / Closed Principle

Les composants doivent être ouverts à l'extension mais fermés à la modification.

Le Runtime n'a pas vocation à être modifié lors de l'ajout :

- d'un nouveau Provider ;
- d'un nouveau type d'achat ;
- d'un nouveau Fulfillment.

---

## Dependency Inversion

Le Runtime dépend d'interfaces.

Jamais d'implémentations.

```
Runtime

↓

ProviderInterface
```

et non

```
Runtime

↓

StripeProvider
```

Cette inversion facilite :

- les tests ;
- les mocks ;
- les remplacements ;
- les évolutions futures.

---

## Composition plutôt qu'héritage

Lorsque cela est possible, le moteur privilégie la composition.

Exemple :

```
Runtime

+

Registry

+

Provider

+

Handler
```

plutôt qu'une hiérarchie complexe de classes.

Cette approche réduit les effets de bord.

---

## Interfaces explicites

Les contrats entre composants doivent être clairement définis.

Le comportement attendu d'un Provider ou d'un Handler doit pouvoir être compris sans lire son implémentation.

Autrement dit :

> **Les interfaces décrivent le comportement, les classes fournissent l'implémentation.**

---

# Bonnes pratiques de développement

Les développements futurs doivent respecter les règles suivantes.

---

## Ne jamais appeler directement un Provider

Toujours passer par le Runtime.

✔ Correct :

```
Runtime

↓

Provider
```

✘ À éviter :

```
Controller

↓

StripeProvider
```

---

## Ne jamais mélanger paiement et métier

Le paiement valide une transaction.

Le Fulfillment crée les droits.

Ces deux étapes doivent rester indépendantes.

---

## Éviter les dépendances croisées

Les Handlers ne doivent pas s'appeler mutuellement.

Le Runtime coordonne toujours les échanges.

---

## Centraliser les règles métier

Les règles propres aux abonnements restent dans le domaine Subscription.

Les règles des produits numériques restent dans leur domaine.

Le Runtime ne contient aucune logique spécifique.

---

## Éviter les traitements cachés

Une méthode publique ne doit pas provoquer des effets inattendus.

Exemple déconseillé :

```
create_payment()

↓

Crée aussi une Subscription
```

Une méthode doit conserver une responsabilité claire et prévisible.

---

# Glossaire

## Commerce Purchase

Représentation générique d'un achat, indépendamment du produit vendu.

---

## Checkout

Préparation d'un achat avant son envoi au Provider.

---

## Payment Request

Représentation locale d'une demande de paiement.

---

## Provider

Connecteur vers un prestataire de paiement externe.

---

## Runtime

Orchestrateur principal du moteur Commerce.

---

## Registry

Composant chargé de retrouver dynamiquement les implémentations adaptées.

---

## Purchase Handler

Composant responsable de la préparation métier d'un achat.

---

## Fulfillment

Transformation d'un paiement validé en droits réels.

---

## Fulfillment Handler

Implémentation spécialisée de la livraison d'un type de produit.

---

## Idempotence

Propriété garantissant qu'une même opération peut être exécutée plusieurs fois sans produire plusieurs effets métier.

---

# Conclusion

La refactorisation introduite lors de la Phase **7.93** transforme profondément le plugin `local_subscriptions`.

Le moteur Commerce ne repose plus sur des traitements spécifiques aux abonnements ou aux produits numériques, mais sur une architecture générique capable d'orchestrer l'ensemble du cycle de vie d'un achat.

Cette évolution apporte plusieurs bénéfices majeurs :

- une architecture plus simple à maintenir ;
- une séparation claire des responsabilités ;
- une meilleure robustesse face aux incidents ;
- une intégration facilitée de nouveaux fournisseurs de paiement ;
- une extensibilité naturelle vers de nouveaux types de produits.

Le moteur Commerce constitue désormais une véritable plateforme de services, indépendante des implémentations métier et des API de paiement sous-jacentes.

Les trois documents suivants complètent cette vue d'ensemble :

- **`commerce_operations.md`** — décrit le fonctionnement opérationnel du moteur (checkout, paiements, fulfillment, CLI, exploitation).
- **`commerce_diagnostics.md`** — présente les outils de diagnostic, les audits, les erreurs courantes, les procédures de reprise et de rollback.
- **`commerce_extension.md`** — explique comment étendre le moteur Commerce en ajoutant de nouveaux Providers, Purchase Handlers, Fulfillment Handlers ou fonctionnalités métier.

---

# Voir aussi

- `commerce_operations.md`
- `commerce_diagnostics.md`
- `commerce_extension.md`
- Documentation générale de `local_subscriptions`
- Documentation développeur du CRM CampusFR