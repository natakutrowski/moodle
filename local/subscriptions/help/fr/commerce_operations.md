# Commerce — Fonctionnement opérationnel

> **Version :** 7.93H  
> **Plugin :** `local_subscriptions`  
> **Public :** Développeurs, exploitants, administrateurs techniques  
> **Document associé :**
>
> - `commerce_overview.md`
> - `commerce_diagnostics.md`
> - `commerce_extension.md`

---

# Table des matières

1. Introduction
2. Objectif du moteur Commerce
3. Vue opérationnelle
4. Le cycle complet d'un achat
5. Le Checkout
6. Construction d'une Commerce Purchase
7. Création du Payment Request
8. Sélection du Provider
9. Redirection vers le fournisseur de paiement
10. Retour utilisateur
11. Validation du paiement
12. Webhooks
13. Fulfillment
14. Notifications
15. Événements
16. États d'une transaction
17. Cas particuliers
18. Conclusion

---

# Introduction

Le document précédent présentait l'architecture générale du moteur Commerce.

Le présent document décrit son **fonctionnement opérationnel**, c'est-à-dire la manière dont les différents composants collaborent pendant un achat réel.

Il répond notamment aux questions suivantes :

- Que se passe-t-il lorsqu'un utilisateur clique sur « Acheter » ?
- Comment un Provider est-il sélectionné ?
- À quel moment une Subscription est-elle créée ?
- Quand les emails sont-ils envoyés ?
- Que se passe-t-il si un paiement échoue ?
- Comment éviter les doubles traitements ?

Ce document suit chronologiquement le parcours complet d'une transaction.

---

# Objectif du moteur Commerce

Le moteur Commerce poursuit un objectif très simple :

> Transformer une intention d'achat en un droit métier.

Ce droit peut prendre différentes formes :

- création d'un abonnement ;
- ouverture d'un cours Moodle ;
- déblocage d'un contenu ;
- création d'une licence ;
- attribution d'un certificat.

Le moteur ne s'intéresse pas à la nature exacte du droit.

Il orchestre uniquement le workflow.

---

# Vue opérationnelle

Le cycle complet peut être résumé ainsi.

```
Utilisateur

    │

    ▼

Checkout

    │

    ▼

Commerce Runtime

    │

    ▼

Purchase Handler

    │

    ▼

Payment Request

    │

    ▼

Provider

    │

    ▼

Paiement

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

Accès créés

    │

    ▼

Emails

    │

    ▼

CRM
```

Cette séquence reste identique quel que soit le produit vendu.

---

# Le cycle complet d'un achat

Chaque achat suit toujours les mêmes grandes étapes.

```
1. L'utilisateur choisit un produit.

↓

2. Le Checkout prépare l'achat.

↓

3. Une Commerce Purchase est construite.

↓

4. Un Payment Request est créé.

↓

5. Le Provider ouvre une session de paiement.

↓

6. Le client effectue le paiement.

↓

7. Le Provider confirme le résultat.

↓

8. Le Runtime valide la transaction.

↓

9. Le Fulfillment livre le produit.

↓

10. Les notifications sont envoyées.
```

Cette séquence constitue le contrat fondamental du moteur Commerce.

---

# Le Checkout

Le Checkout représente la phase de préparation.

Il ne crée aucun droit.

Il construit simplement toutes les informations nécessaires au paiement.

Ses responsabilités sont les suivantes :

- identifier le produit ;
- identifier le client ;
- vérifier les paramètres ;
- déterminer le prix ;
- déterminer la devise ;
- choisir le Provider ;
- construire la Purchase ;
- créer le Payment Request.

Le Checkout est volontairement **sans effet métier**.

---

## Pourquoi séparer le Checkout ?

Cette séparation offre plusieurs avantages.

Le Checkout peut échouer sans conséquence.

Exemples :

- produit supprimé ;
- devise non autorisée ;
- Provider indisponible ;
- utilisateur suspendu ;
- offre expirée.

Dans tous ces cas :

```
Aucun accès Moodle

Aucune Subscription

Aucune licence

Aucun email
```

n'est créé.

Cette propriété simplifie énormément les reprises.

---

# Construction d'une Commerce Purchase

La première étape consiste à construire une représentation interne de l'achat.

Cette représentation contient notamment :

```
Client

Produit

Purchase Type

Prix

Devise

Provider

Métadonnées
```

À ce stade :

```
Aucun paiement

Aucun accès

Aucun effet métier
```

La Purchase est uniquement un objet de travail.

---

# Validation des données

Avant de poursuivre, le moteur vérifie que toutes les informations sont cohérentes.

Par exemple :

- utilisateur existant ;
- produit actif ;
- devise disponible ;
- prix positif ;
- Provider autorisé ;
- règles métier respectées.

Le moindre échec interrompt immédiatement le Checkout.

```
Validation

↓

Erreur

↓

Fin
```

Aucun appel externe n'est encore réalisé.

---

# Calcul du prix

Le calcul du prix appartient exclusivement au domaine métier.

Le Runtime ne calcule jamais lui-même les montants.

Selon le type de produit, le Handler peut prendre en compte :

- promotions ;
- remises ;
- upgrades ;
- différences de prix ;
- devise ;
- TVA ;
- règles commerciales.

Une fois calculé, le prix devient la référence utilisée par le Provider.

---

# Sélection du Provider

Le moteur choisit ensuite le fournisseur de paiement.

Le choix peut dépendre :

- de la configuration ;
- de la devise ;
- du pays ;
- du produit ;
- des préférences utilisateur.

Le Runtime ne connaît jamais directement Stripe ou Alfa Bank.

Il délègue cette décision au Provider Registry.

```
Runtime

↓

Provider Registry

↓

Provider
```

Cette abstraction permet d'ajouter facilement de nouveaux prestataires.

# Création du Payment Request

Une fois le Checkout validé, le moteur Commerce construit un **Payment Request**.

Le Payment Request représente la transaction locale qui sera confiée au Provider.

Il constitue le lien entre le monde métier de CampusFR et le système de paiement externe.

Schématiquement :

```
Commerce Purchase

        │

        ▼

Payment Request

        │

        ▼

Provider
```

Le Payment Request est créé **avant** toute communication avec le prestataire de paiement.

---

## Contenu d'un Payment Request

Selon les besoins du projet, un Payment Request contient notamment :

- un identifiant unique ;
- la Purchase associée ;
- l'utilisateur concerné ;
- le Provider sélectionné ;
- le montant ;
- la devise ;
- le statut courant ;
- la date de création ;
- les métadonnées nécessaires au Provider.

Il constitue la référence de toutes les opérations ultérieures.

---

## Pourquoi créer un Payment Request avant le paiement ?

Cette décision présente plusieurs avantages.

Même si le navigateur est fermé juste après la redirection vers Stripe ou Alfa Bank, CampusFR possède déjà une trace complète de la transaction.

Cela permet :

- de reprendre un paiement interrompu ;
- de corréler les webhooks ;
- d'auditer les tentatives ;
- de diagnostiquer les incidents ;
- de produire des statistiques fiables.

Le Payment Request devient donc la source de vérité de toute la transaction financière.

---

# Ouverture de la session de paiement

Le Runtime délègue ensuite l'ouverture de la session au Provider sélectionné.

```
Payment Request

        │

        ▼

Provider

        │

        ▼

API externe
```

Le Provider est libre d'utiliser le protocole imposé par son prestataire :

- API REST ;
- formulaire HTML ;
- redirection HTTP ;
- SDK JavaScript ;
- API propriétaire.

Le Runtime ne connaît aucun de ces détails.

---

## Résultat attendu

Le Provider retourne généralement :

- une URL de paiement ;
- un identifiant de session ;
- un jeton sécurisé ;
- ou toute information permettant de poursuivre le Checkout.

Le Runtime ne manipule ensuite que cette réponse normalisée.

---

# Redirection de l'utilisateur

Une fois la session créée, le navigateur est redirigé vers le prestataire.

```
CampusFR

        │

        ▼

Stripe

ou

Alfa Bank
```

À partir de cet instant, l'utilisateur quitte temporairement CampusFR.

Toutes les interactions sont désormais gérées par le prestataire.

---

# Pendant le paiement

Le moteur Commerce n'effectue normalement aucun traitement métier pendant cette phase.

Le client peut :

- saisir sa carte bancaire ;
- utiliser Apple Pay ;
- utiliser Google Pay ;
- payer via le moyen proposé par le Provider.

Le résultat reste inconnu tant que le Provider ne l'a pas confirmé.

---

# Retour utilisateur

Une fois le paiement terminé, le navigateur revient généralement sur CampusFR.

Ce retour permet principalement :

- d'afficher une page de succès ;
- d'afficher une page d'échec ;
- d'informer l'utilisateur de l'état de son paiement.

Il est important de comprendre que **ce retour navigateur ne constitue pas une preuve de paiement**.

Le navigateur peut :

- être fermé ;
- perdre la connexion ;
- être modifié par l'utilisateur ;
- être interrompu.

Le moteur Commerce ne considère donc jamais ce retour comme une validation officielle.

---

# Validation du paiement

La validation est réalisée uniquement à partir des informations fournies par le Provider.

```
Provider

↓

Notification officielle

↓

Validation

↓

Paiement reconnu
```

Les contrôles effectués peuvent notamment porter sur :

- la signature cryptographique ;
- le montant ;
- la devise ;
- l'identifiant de transaction ;
- le statut retourné ;
- les métadonnées attendues.

Tant que ces contrôles ne sont pas terminés, le paiement est considéré comme non validé.

---

## Pourquoi ne pas faire confiance au navigateur ?

Prenons un exemple.

```
Utilisateur

↓

Clique sur "Retour"

↓

Page "Merci"

↓

Navigateur fermé
```

Cela ne prouve absolument pas que le paiement a été accepté.

À l'inverse :

```
Paiement accepté

↓

Navigateur fermé
```

Le paiement reste parfaitement valide.

La seule source fiable est le Provider.

---

# Les Webhooks

La plupart des prestataires modernes utilisent des **webhooks**.

Un webhook est une notification envoyée directement par le Provider vers CampusFR.

```
Stripe

↓

HTTPS

↓

Webhook Commerce
```

Contrairement au navigateur, cette communication est réalisée de serveur à serveur.

Elle est donc beaucoup plus fiable.

---

## Rôle des webhooks

Les webhooks permettent notamment :

- confirmer un paiement ;
- signaler un remboursement ;
- annoncer une annulation ;
- notifier un échec ;
- transmettre des informations complémentaires.

Le Runtime traite ces événements de manière totalement indépendante du navigateur.

---

## Vérification d'un webhook

Avant toute action, le moteur vérifie :

```
Signature

↓

Authenticité

↓

Transaction connue

↓

Montant

↓

Devise

↓

Statut
```

Le moindre échec provoque l'arrêt immédiat du traitement.

Aucun Fulfillment n'est exécuté.

---

# États d'une transaction

Une transaction traverse plusieurs états au cours de son cycle de vie.

Schéma simplifié :

```
Créée

↓

En attente

↓

Redirection

↓

Paiement en cours

↓

Paiement validé

↓

Fulfillment

↓

Terminée
```

Certains scénarios alternatifs sont également possibles :

```
Créée

↓

Annulée
```

ou

```
Créée

↓

Échec
```

ou encore :

```
Créée

↓

Expirée
```

Chaque changement d'état doit être traçable afin de faciliter les audits et les diagnostics.

---

# Déclenchement du Fulfillment

Une fois la validation terminée, le Runtime autorise enfin la livraison du produit.

```
Paiement validé

↓

Fulfillment Runtime

↓

Fulfillment Handler
```

Cette transition est irréversible : le moteur considère désormais que les fonds ont été correctement acceptés et qu'il peut créer les droits métier.

Aucun accès n'est créé avant cette étape.

# Le processus de Fulfillment

Le **Fulfillment** est la phase durant laquelle le paiement validé est transformé en effets métier.

C'est ici que le moteur Commerce crée réellement les droits de l'utilisateur.

Jusqu'à ce point, aucune modification fonctionnelle durable n'a été effectuée.

---

## Vue d'ensemble

```
Paiement validé

        │

        ▼

Fulfillment Runtime

        │

        ▼

Fulfillment Registry

        │

        ▼

Fulfillment Handler

        │

        ▼

Création des droits
```

Le Runtime ne connaît pas la logique métier.

Il sélectionne simplement le Handler approprié.

---

## Exemple : abonnement CampusFR

Pour un abonnement, le Fulfillment peut notamment effectuer les opérations suivantes :

```
Créer la Subscription

↓

Créer les Entitlements

↓

Attribuer les Cohorts Moodle

↓

Ouvrir les cours

↓

Créer les accès CRM

↓

Créer les événements Timeline

↓

Déclencher les notifications

↓

Envoyer les emails
```

Toutes ces opérations appartiennent exclusivement au domaine Subscription.

---

## Exemple : produit numérique

Pour un produit numérique, le workflow est différent.

```
Créer une licence

↓

Débloquer le téléchargement

↓

Créer l'historique CRM

↓

Envoyer les emails

↓

Fin
```

Le moteur Commerce ne fait aucune différence.

Il exécute simplement le Handler adapté.

---

# Pourquoi séparer le Fulfillment ?

Cette séparation constitue l'un des principes fondamentaux de l'architecture.

Imaginons qu'un utilisateur paie aujourd'hui mais que le serveur Moodle rencontre une panne au moment de créer la Subscription.

Si le paiement et le Fulfillment étaient confondus :

```
Paiement

↓

Erreur

↓

État incertain
```

Il deviendrait difficile de savoir :

- si le client a payé ;
- si la Subscription existe ;
- s'il faut recommencer.

Avec l'architecture actuelle :

```
Paiement validé

↓

Fulfillment

↓

Erreur
```

Le paiement reste valide.

Le Fulfillment peut être rejoué ultérieurement.

Cette propriété simplifie énormément l'exploitation.

---

# Les notifications

Une fois le Fulfillment terminé, plusieurs notifications peuvent être générées.

Par exemple :

- email de confirmation ;
- email de bienvenue ;
- reçu de paiement ;
- notification CRM ;
- événements internes.

Ces notifications ne doivent jamais faire partie du paiement lui-même.

Elles représentent uniquement des traitements complémentaires.

---

## Pourquoi envoyer les emails en dernier ?

Prenons l'exemple suivant.

```
Email envoyé

↓

Erreur Subscription
```

L'utilisateur recevrait un message indiquant que son abonnement est actif alors qu'il ne l'est pas.

Le bon ordre est donc :

```
Subscription créée

↓

Accès Moodle

↓

Timeline

↓

Emails
```

Les notifications interviennent toujours après la réussite des opérations métier.

---

# Les événements internes

Le moteur Commerce produit également des événements destinés aux autres composants de CampusFR.

Exemple :

```
PurchaseCreated

PaymentValidated

FulfillmentStarted

FulfillmentCompleted

SubscriptionCreated

DigitalPurchaseCompleted
```

Ces événements permettent de découpler le moteur Commerce du reste de l'application.

Un composant CRM peut ainsi écouter :

```
SubscriptionCreated
```

sans que Commerce connaisse son existence.

---

# Séquence complète d'un achat

Le diagramme suivant représente le workflow complet.

```
Utilisateur

        │

        ▼

Choisit un produit

        │

        ▼

Checkout

        │

        ▼

Purchase Handler

        │

        ▼

Commerce Purchase

        │

        ▼

Payment Request

        │

        ▼

Provider

        │

        ▼

Paiement

        │

        ▼

Webhook

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

Création des droits

        │

        ▼

Notifications

        │

        ▼

Fin
```

Cette séquence représente le fonctionnement nominal du moteur.

---

# Cas particuliers

Le moteur Commerce doit également gérer des scénarios moins courants.

---

## Paiement refusé

```
Checkout

↓

Provider

↓

Paiement refusé
```

Dans ce cas :

- aucun Fulfillment ;
- aucun accès ;
- aucune Subscription ;
- aucun produit livré.

Le Payment Request est simplement mis à jour avec un état d'échec.

---

## Paiement abandonné

L'utilisateur peut quitter le site avant de terminer son paiement.

```
Checkout

↓

Provider

↓

Utilisateur ferme son navigateur
```

Le Payment Request reste alors dans un état intermédiaire.

Aucun droit n'est créé.

Selon le Provider, un webhook ultérieur pourra éventuellement confirmer le paiement.

---

## Double clic sur "Payer"

Le navigateur peut envoyer deux requêtes très proches.

```
Utilisateur

↓

Double clic

↓

Deux requêtes
```

Le moteur Commerce doit empêcher la création de deux transactions distinctes lorsque celles-ci correspondent au même achat.

Cette protection repose généralement sur les identifiants de transaction et les règles d'idempotence.

---

## Webhook reçu plusieurs fois

De nombreux prestataires renvoient automatiquement les webhooks lorsqu'ils ne reçoivent pas d'accusé de réception.

```
Webhook

↓

Fulfillment

↓

OK

↓

Webhook identique

↓

Ignoré
```

Le deuxième traitement ne doit produire aucun nouvel effet métier.

---

## Retour navigateur avant le webhook

Ce scénario est fréquent.

```
Utilisateur

↓

Retour CampusFR

↓

Quelques secondes plus tard

↓

Webhook
```

Le moteur doit être capable d'afficher un état d'attente tant que la confirmation officielle n'a pas été reçue.

Il ne doit jamais anticiper la validation du paiement.

---

## Webhook avant le retour navigateur

L'ordre inverse est également possible.

```
Webhook

↓

Paiement validé

↓

Retour utilisateur
```

Le moteur doit gérer cette situation de manière totalement transparente.

Le Fulfillment est déjà terminé lorsque l'utilisateur revient sur CampusFR.

---

# Journalisation

Chaque étape importante du workflow doit être enregistrée.

Exemple simplifié :

```
Checkout démarré

↓

Purchase créée

↓

Payment Request créée

↓

Provider sélectionné

↓

Session ouverte

↓

Paiement validé

↓

Fulfillment démarré

↓

Subscription créée

↓

Emails envoyés

↓

Workflow terminé
```

Cette journalisation facilite :

- les audits ;
- le support utilisateur ;
- les investigations techniques ;
- les analyses statistiques.

---

# Rejouabilité des traitements

L'un des objectifs de la refactorisation est de permettre le rejeu de certaines opérations.

Par exemple :

- relancer un Fulfillment interrompu ;
- renvoyer les emails ;
- reconstruire des accès manquants ;
- retraiter un webhook.

En revanche, certaines opérations ne doivent jamais être rejouées :

- création d'un second Payment Request pour la même transaction ;
- validation répétée d'un paiement déjà accepté ;
- création multiple d'une même Subscription.

La distinction entre opérations rejouables et non rejouables est essentielle pour garantir l'intégrité du système.

---

# Gestion des erreurs

Aucun moteur de paiement n'est exempt d'erreurs.

L'objectif de l'architecture Commerce n'est donc pas d'empêcher toute anomalie, mais de les rendre :

- détectables ;
- isolées ;
- traçables ;
- récupérables.

Une erreur ne doit jamais laisser le système dans un état incohérent.

---

## Erreur pendant le Checkout

Exemples :

- produit inexistant ;
- utilisateur invalide ;
- devise interdite ;
- Provider indisponible ;
- paramètres invalides.

Workflow :

```
Checkout

↓

Validation

↓

Erreur

↓

Fin
```

Aucun Payment Request ne doit être envoyé au Provider tant que les validations préalables ne sont pas terminées.

---

## Erreur Provider

Le Provider peut rencontrer différents problèmes.

Par exemple :

- indisponibilité réseau ;
- timeout ;
- erreur HTTP ;
- erreur d'API ;
- refus du prestataire.

Workflow :

```
Payment Request

↓

Provider

↓

Erreur

↓

Journalisation

↓

Retour utilisateur
```

Le moteur Commerce ne tente jamais de créer un Fulfillment si le Provider n'a pas confirmé la transaction.

---

## Erreur de validation

Le Runtime peut recevoir une notification invalide.

Par exemple :

- signature incorrecte ;
- transaction inconnue ;
- devise inattendue ;
- montant incohérent ;
- transaction expirée.

Dans ce cas :

```
Webhook

↓

Validation

↓

Refus

↓

Fin
```

Le traitement s'arrête immédiatement.

---

## Erreur pendant le Fulfillment

Le Fulfillment peut également échouer.

Exemple :

```
Paiement validé

↓

Création Subscription

↓

Erreur SQL
```

ou

```
Paiement validé

↓

Création Cohort

↓

Erreur Moodle
```

Dans ce cas :

- le paiement reste valide ;
- l'erreur est journalisée ;
- le Fulfillment pourra être repris ultérieurement.

Cette séparation constitue l'une des principales raisons d'existence du Runtime.

---

# Robustesse du workflow

Le moteur Commerce est conçu selon le principe suivant :

> **Une erreur locale ne doit jamais compromettre l'ensemble du workflow.**

Exemple :

```
Paiement validé

↓

Subscription créée

↓

Erreur Email
```

La Subscription reste active.

Le paiement reste valide.

Seul l'envoi du mail est concerné.

Il pourra être rejoué indépendamment.

---

# Ordre d'exécution recommandé

Le respect de l'ordre des opérations est essentiel.

Ordre recommandé :

```
Validation

↓

Fulfillment

↓

Historique CRM

↓

Timeline

↓

Notifications

↓

Emails
```

À éviter :

```
Email

↓

Fulfillment
```

ou

```
Timeline

↓

Paiement
```

Le moteur doit toujours construire les droits avant d'informer l'utilisateur.

---

# Exploitation quotidienne

Dans un fonctionnement normal, le moteur Commerce ne nécessite quasiment aucune intervention humaine.

Les principales opérations d'exploitation consistent à :

- surveiller les paiements ;
- contrôler les erreurs ;
- vérifier les webhooks ;
- suivre les statistiques ;
- traiter les incidents exceptionnels.

La plupart des tâches peuvent être réalisées à partir du CRM CampusFR.

---

# Surveillance des paiements

Les administrateurs doivent pouvoir répondre rapidement aux questions suivantes :

- Quels paiements sont en attente ?
- Quels paiements ont échoué ?
- Quels paiements ont été validés aujourd'hui ?
- Existe-t-il des Fulfillments interrompus ?
- Des webhooks sont-ils en erreur ?

Ces informations sont la base du suivi opérationnel.

---

# Procédure type en cas d'incident

Lorsqu'un utilisateur indique avoir payé sans recevoir son accès, la procédure recommandée est la suivante.

```
Identifier le Payment Request

↓

Vérifier le Provider

↓

Contrôler le statut

↓

Vérifier la validation

↓

Contrôler le Fulfillment

↓

Contrôler la Subscription

↓

Contrôler les accès Moodle

↓

Contrôler les emails
```

Cette méthode permet de localiser rapidement le point de blocage.

---

# Commandes CLI

Le moteur Commerce est conçu pour être exploitable aussi bien depuis l'interface CRM que depuis la ligne de commande.

Les commandes CLI ont plusieurs objectifs :

- automatiser les opérations d'administration ;
- faciliter les audits ;
- diagnostiquer un incident ;
- rejouer certains traitements ;
- valider la configuration.

Le détail exact des commandes disponibles dépend de la version du plugin, mais elles suivent généralement les principes suivants.

---

## Audit

Les commandes d'audit permettent de vérifier la cohérence globale du moteur.

Exemples :

```
Vérification des Payment Requests

↓

Contrôle des Subscriptions

↓

Détection des incohérences

↓

Rapport
```

---

## Diagnostic

Les commandes de diagnostic vérifient notamment :

- la configuration des Providers ;
- les clés API ;
- les webhooks ;
- les dépendances ;
- les permissions.

Leur objectif est d'identifier rapidement une anomalie d'exploitation.

---

## Rejeu

Certaines commandes permettent de relancer un traitement interrompu.

Par exemple :

- relancer un Fulfillment ;
- renvoyer les emails ;
- reconstruire les accès ;
- retraiter une transaction.

Le rejeu reste strictement encadré par les mécanismes d'idempotence.

---

## Maintenance

Des commandes peuvent également être utilisées pour :

- nettoyer des données obsolètes ;
- reconstruire certains index ;
- recalculer des statistiques ;
- vérifier la cohérence des données métier.

---

# Recommandations d'exploitation

Pour garantir un fonctionnement fiable du moteur Commerce, les bonnes pratiques suivantes sont recommandées.

---

## Vérifier régulièrement les erreurs

Les erreurs de paiement doivent être surveillées quotidiennement.

Une augmentation soudaine peut révéler :

- une panne Provider ;
- une erreur de configuration ;
- un changement d'API ;
- un problème réseau.

---

## Ne jamais modifier directement les données

Les Payment Requests et les objets métier associés ne doivent pas être corrigés directement en base de données, sauf procédure exceptionnelle et parfaitement maîtrisée.

Toute modification manuelle risque de rompre la cohérence entre :

- le paiement ;
- le Provider ;
- le Fulfillment ;
- les accès.

Lorsque cela est possible, il est préférable d'utiliser les outils d'administration ou les commandes CLI prévues à cet effet.

---

## Conserver les journaux

Les journaux techniques sont indispensables pour :

- comprendre un incident ;
- prouver le déroulement d'une transaction ;
- faciliter le support ;
- réaliser un audit.

Ils doivent être conservés conformément à la politique de rétention définie pour le projet.

---

# Conclusion

Le moteur Commerce repose sur un workflow volontairement simple, mais extrêmement structuré.

Chaque étape possède une responsabilité clairement définie :

```
Checkout

↓

Payment Request

↓

Provider

↓

Validation

↓

Fulfillment

↓

Notifications
```

Cette organisation apporte plusieurs bénéfices :

- robustesse ;
- traçabilité ;
- extensibilité ;
- facilité d'exploitation ;
- simplicité des diagnostics.

Grâce à cette séparation des responsabilités, une anomalie reste localisée et peut généralement être corrigée sans remettre en cause l'ensemble du processus.

Les documents complémentaires permettent d'aller plus loin :

- **`commerce_diagnostics.md`** décrit les procédures d'analyse, les audits, les investigations et les méthodes de reprise après incident.
- **`commerce_extension.md`** explique comment étendre le moteur Commerce avec de nouveaux types d'achats, Providers ou Handlers, tout en respectant les principes d'architecture présentés dans cette documentation.

---

# Voir aussi

- `commerce_overview.md`
- `commerce_diagnostics.md`
- `commerce_extension.md`