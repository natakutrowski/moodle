# Commerce — Diagnostics, audits et reprise après incident

> **Version :** 7.93H  
> **Plugin :** `local_subscriptions`  
> **Public :** Développeurs, administrateurs, exploitation, support technique  
> **Document associé :**
>
> - `commerce_overview.md`
> - `commerce_operations.md`
> - `commerce_extension.md`

---

# Table des matières

1. Introduction
2. Philosophie des diagnostics
3. Architecture des audits
4. Sources d'information
5. Cycle d'investigation
6. Diagnostic d'un paiement
7. Diagnostic d'un Fulfillment
8. Audit des données
9. Vérifications de cohérence
10. Journalisation
11. Gestion des incidents
12. Reprise et rollback
13. Outils CLI
14. Bonnes pratiques
15. Conclusion

---

# Introduction

Le moteur Commerce est conçu pour être **observable**.

Autrement dit, il doit toujours être possible de répondre rapidement aux questions suivantes :

- Pourquoi un paiement a-t-il échoué ?
- Pourquoi une Subscription n'a-t-elle pas été créée ?
- Pourquoi un utilisateur n'a-t-il pas reçu son accès ?
- Pourquoi un webhook a-t-il été rejeté ?
- Où le workflow s'est-il arrêté ?

Cette capacité d'observation est indispensable pour :

- l'exploitation quotidienne ;
- le support utilisateur ;
- les audits financiers ;
- les investigations techniques ;
- les procédures de reprise.

L'objectif de ce document est de décrire les méthodes permettant de diagnostiquer efficacement le moteur Commerce.

---

# Philosophie des diagnostics

Le moteur Commerce repose sur un principe simple :

> **Toute opération importante doit pouvoir être reconstruite a posteriori.**

Autrement dit, il doit être possible de reconstituer l'intégralité du cycle de vie d'un achat.

Exemple :

```
Checkout

↓

Purchase

↓

Payment Request

↓

Provider

↓

Validation

↓

Fulfillment

↓

Emails
```

Même plusieurs semaines après la transaction.

---

# Pourquoi les diagnostics sont essentiels ?

Un paiement met en jeu :

- de l'argent ;
- des accès Moodle ;
- des données CRM ;
- des obligations comptables.

Une erreur peut donc avoir des conséquences importantes.

Les diagnostics doivent permettre de distinguer rapidement :

```
Erreur utilisateur

Erreur Provider

Erreur réseau

Erreur métier

Erreur technique
```

Cette distinction accélère considérablement la résolution des incidents.

---

# Architecture des audits

Le moteur Commerce produit plusieurs niveaux d'information.

```
Commerce Runtime

        │

        ▼

Journal technique

        │

        ▼

Audit métier

        │

        ▼

Historique CRM

        │

        ▼

Statistiques
```

Chaque niveau répond à un objectif différent.

---

## Journal technique

Le journal technique décrit les événements internes.

Par exemple :

```
Payment Request créée

↓

Provider sélectionné

↓

Webhook reçu

↓

Validation terminée

↓

Fulfillment lancé
```

Ces informations sont principalement destinées aux développeurs.

---

## Audit métier

L'audit métier décrit les conséquences fonctionnelles.

Exemple :

```
Subscription créée

↓

Entitlements créés

↓

Cours ouverts

↓

Emails envoyés
```

Ces informations intéressent davantage les administrateurs fonctionnels.

---

## Historique CRM

Le CRM conserve les événements visibles liés au client.

Par exemple :

```
Paiement accepté

↓

Nouvel abonnement

↓

Email envoyé

↓

Accès activés
```

Cet historique facilite le support utilisateur.

---

# Sources d'information

Lorsqu'un incident est signalé, plusieurs sources d'information peuvent être consultées.

Les principales sont :

- Payment Request ;
- Commerce Purchase ;
- logs Provider ;
- historique CRM ;
- Timeline utilisateur ;
- Subscription ;
- journaux Moodle.

Le diagnostic consiste à corréler ces différentes informations.

---

# Cycle d'investigation

Une investigation suit généralement le processus suivant.

```
Signalement utilisateur

↓

Identification de la transaction

↓

Analyse Payment Request

↓

Analyse Provider

↓

Analyse Validation

↓

Analyse Fulfillment

↓

Analyse métier

↓

Conclusion
```

Chaque étape élimine progressivement des hypothèses.

---

# Premier principe

Toujours commencer par identifier la transaction concernée.

Il est fortement déconseillé de commencer directement par :

- Stripe ;
- Alfa Bank ;
- Moodle ;
- la base de données.

Le Payment Request constitue normalement le point d'entrée principal.

---

# Diagnostic d'un paiement

Le premier objectif est de répondre à une question simple :

> **Le paiement a-t-il réellement eu lieu ?**

Le diagnostic suit généralement cette séquence.

```
Payment Request

↓

Provider

↓

Validation

↓

Statut final
```

Si le paiement n'a jamais été validé, le Fulfillment n'a pas vocation à être exécuté.

---

## Vérification du Payment Request

Les premières informations à contrôler sont :

- identifiant ;
- utilisateur ;
- montant ;
- devise ;
- Provider ;
- statut.

Ces données permettent de vérifier que la transaction attendue existe réellement.

---

## Vérification du Provider

Une fois la transaction identifiée, il convient de vérifier :

- le statut retourné par le Provider ;
- l'identifiant distant ;
- la date ;
- les éventuels messages d'erreur.

Cette étape permet de distinguer :

```
Erreur CampusFR

ou

Erreur Provider
```

---

## Vérification de la validation

Le paiement peut avoir été accepté par le Provider mais rejeté localement.

Par exemple :

```
Signature invalide

Montant incohérent

Transaction inconnue

Devise incorrecte
```

Dans ce cas, le problème se situe dans la phase de validation et non dans le paiement lui-même.

---

# Diagnostic du Fulfillment

Si le paiement est validé mais que l'utilisateur ne dispose toujours pas de son produit, l'investigation doit se poursuivre sur le Fulfillment.

```
Paiement validé

↓

Fulfillment Runtime

↓

Handler

↓

Résultat
```

Le premier objectif consiste à déterminer si le Fulfillment a bien démarré.

---

## Questions à se poser

- Le Runtime a-t-il lancé le Fulfillment ?
- Quel Handler a été sélectionné ?
- Une exception a-t-elle été levée ?
- Les droits ont-ils été créés partiellement ?
- Les notifications ont-elles été envoyées ?

Ces questions permettent généralement de localiser rapidement le problème.

---

# Différencier paiement et Fulfillment

Cette distinction est fondamentale.

Les scénarios suivants sont très différents.

```
Paiement refusé

↓

Aucun Fulfillment
```

et

```
Paiement accepté

↓

Fulfillment interrompu
```

Dans le premier cas, le client n'a pas payé.

Dans le second, le client a payé mais les droits n'ont pas été correctement créés.

Les procédures de résolution ne sont évidemment pas les mêmes.

---

# Audit des données

L'objectif d'un audit est de vérifier que l'ensemble des informations manipulées par le moteur Commerce reste cohérent.

Contrairement à un diagnostic, qui cherche à résoudre un incident précis, un audit vise à détecter des anomalies avant qu'elles n'affectent les utilisateurs.

Un audit peut être réalisé :

- après une mise en production ;
- après une migration de données ;
- après une évolution majeure ;
- de manière périodique dans le cadre de l'exploitation.

---

# Types d'audits

Le moteur Commerce distingue plusieurs catégories d'audits.

```
Audit fonctionnel

Audit technique

Audit de cohérence

Audit sécurité

Audit financier
```

Chaque catégorie poursuit un objectif spécifique.

---

## Audit fonctionnel

Il vérifie que les règles métier sont respectées.

Exemples :

- une Subscription active possède bien des accès Moodle ;
- un produit numérique acheté est effectivement accessible ;
- les entitlements correspondent au plan souscrit ;
- les règles d'upgrade ont été correctement appliquées.

---

## Audit technique

Il contrôle le bon fonctionnement de l'infrastructure Commerce.

Exemples :

- Providers disponibles ;
- webhooks configurés ;
- services accessibles ;
- dépendances installées ;
- configuration valide.

---

## Audit de cohérence

Il recherche les incohérences internes.

Exemples :

```
Payment validé

↓

Subscription absente
```

ou

```
Subscription active

↓

Aucun Payment Request
```

ou encore :

```
Paiement réussi

↓

Utilisateur supprimé
```

Ces situations doivent être exceptionnelles.

---

## Audit sécurité

L'audit sécurité vérifie notamment :

- les signatures des webhooks ;
- les clés API ;
- les permissions ;
- les accès aux endpoints ;
- les protections CSRF lorsque pertinentes ;
- la confidentialité des données sensibles.

---

## Audit financier

L'objectif est de rapprocher les informations du moteur Commerce avec celles du prestataire de paiement.

Exemple :

```
Stripe

↓

100 paiements

↓

CampusFR

↓

100 Payment Requests validés
```

Toute différence doit être expliquée.

---

# Vérifications de cohérence

Les contrôles de cohérence constituent le cœur du moteur d'audit.

Ils permettent d'identifier automatiquement les anomalies les plus courantes.

---

## Payment Request orpheline

Une Payment Request ne devrait jamais exister sans Purchase associée.

```
Payment Request

↓

Purchase absente
```

Cette situation révèle généralement :

- une suppression manuelle ;
- une migration incomplète ;
- une erreur de développement.

---

## Purchase sans paiement

Certaines Purchases peuvent rester sans Payment Request.

Cela peut être normal si le Checkout a été interrompu très tôt.

En revanche, une Purchase finalisée ne devrait jamais rester durablement dans cet état.

---

## Paiement validé sans Fulfillment

C'est l'un des contrôles les plus importants.

```
Paiement validé

↓

Aucun Fulfillment
```

Cette anomalie doit être traitée rapidement car l'utilisateur a payé sans recevoir son produit.

---

## Fulfillment sans paiement

Le scénario inverse est encore plus critique.

```
Fulfillment

↓

Paiement absent
```

Il signifie qu'un produit a été livré sans contrepartie financière.

Cette situation ne devrait jamais être possible en fonctionnement normal.

---

## Subscription incohérente

Exemples :

```
Subscription active

↓

Cours fermés
```

ou

```
Subscription expirée

↓

Accès toujours ouverts
```

Ces incohérences relèvent généralement du domaine métier plutôt que du moteur Commerce lui-même.

---

## Notifications manquantes

Il peut arriver que les traitements métier aient correctement abouti mais que certaines notifications aient échoué.

Par exemple :

```
Subscription créée

↓

Email non envoyé
```

Cette situation est généralement moins critique.

Les notifications peuvent être rejouées indépendamment.

---

# Journalisation

Une journalisation de qualité est indispensable.

Chaque événement important doit pouvoir être retrouvé rapidement.

---

## Principes

Les journaux doivent être :

- lisibles ;
- horodatés ;
- corrélables ;
- exploitables automatiquement.

Ils doivent également éviter toute information sensible inutile.

---

## Exemple de chronologie

```
10:02:14

Checkout démarré

↓

10:02:15

Purchase créée

↓

10:02:15

Payment Request créée

↓

10:02:16

Provider Stripe sélectionné

↓

10:02:17

Session ouverte

↓

10:03:11

Webhook reçu

↓

10:03:11

Signature valide

↓

10:03:11

Paiement accepté

↓

10:03:12

Fulfillment démarré

↓

10:03:13

Subscription créée

↓

10:03:14

Emails envoyés

↓

10:03:14

Workflow terminé
```

Une telle chronologie permet de reconstruire très rapidement une transaction.

---

# Corrélation des événements

Toutes les opérations liées à un achat doivent partager un identifiant commun.

Schéma simplifié :

```
Purchase

↓

Payment Request

↓

Webhook

↓

Fulfillment

↓

Timeline

↓

Emails
```

Ainsi, une recherche permet de retrouver immédiatement l'ensemble du workflow.

---

# Gestion des incidents

Tous les incidents ne présentent pas le même niveau de gravité.

Une bonne exploitation consiste à les classer afin de définir la bonne procédure de traitement.

---

## Incident mineur

Exemples :

- email non envoyé ;
- journal incomplet ;
- notification CRM absente.

Le paiement et les droits sont corrects.

L'incident peut être traité ultérieurement.

---

## Incident majeur

Exemples :

```
Paiement accepté

↓

Aucun accès
```

ou

```
Fulfillment interrompu
```

Ces incidents nécessitent une intervention rapide.

---

## Incident critique

Exemples :

```
Produit livré

↓

Paiement absent
```

ou

```
Paiement dupliqué
```

ou

```
Fulfillment exécuté plusieurs fois
```

Ces situations peuvent avoir des conséquences financières importantes.

Elles doivent être investiguées immédiatement.

---

# Reprise après incident

L'un des objectifs majeurs de l'architecture 7.93 est de permettre des reprises ciblées.

Plutôt que de rejouer l'ensemble du workflow, il est préférable de ne relancer que la partie concernée.

Exemple :

```
Paiement validé

↓

Erreur Email
```

La bonne stratégie consiste à :

```
Rejouer uniquement l'envoi du mail.
```

et non :

```
Relancer le paiement.
```

Cette approche réduit considérablement les risques.

---

# Principe du rollback

Le terme *rollback* recouvre deux réalités différentes.

Il est essentiel de les distinguer.

## Rollback technique

Le rollback technique consiste à annuler ou corriger une opération interne qui n'a pas encore produit d'effet irréversible.

Exemples :

- annulation d'une transaction SQL ;
- suppression d'une Purchase incomplète ;
- retour à un état cohérent après une exception.

Ces opérations sont généralement automatiques.

---

## Rollback métier

À l'inverse, certaines opérations ne peuvent pas être annulées automatiquement.

Par exemple :

```
Paiement bancaire confirmé
```

Il n'est évidemment pas possible de « revenir en arrière » simplement en supprimant quelques enregistrements.

Dans ce cas, le rollback métier passe généralement par une procédure explicite :

- remboursement ;
- révocation d'accès ;
- annulation d'une Subscription ;
- émission d'un avoir ;
- correction comptable.

Le moteur Commerce doit toujours privilégier des opérations explicites plutôt que des suppressions silencieuses.

---

# Outils CLI

Le moteur Commerce a été conçu pour être entièrement administrable sans interface graphique.

Les commandes CLI jouent un rôle central dans :

- l'exploitation quotidienne ;
- les audits ;
- les migrations ;
- les diagnostics ;
- les procédures de reprise.

Elles permettent également d'automatiser de nombreuses tâches via `cron`, `systemd` ou tout autre ordonnanceur.

---

## Principes

Une commande CLI ne doit jamais modifier silencieusement des données critiques.

Par défaut, elle doit privilégier un fonctionnement en lecture seule.

Schéma recommandé :

```
Analyse

↓

Rapport

↓

Confirmation

↓

Action éventuelle
```

Cette approche réduit fortement le risque d'erreur humaine.

---

## Catégories de commandes

Le moteur Commerce distingue généralement plusieurs familles de commandes.

```
Audit

Diagnostic

Validation

Maintenance

Réparation

Migration

Statistiques
```

Chaque catégorie répond à un objectif différent.

---

## Commandes d'audit

Les commandes d'audit recherchent des incohérences.

Exemples de contrôles :

- Payment Requests orphelines ;
- Fulfillments incomplets ;
- Subscriptions incohérentes ;
- achats sans droits ;
- droits sans achat.

Leur objectif est exclusivement informatif.

---

## Commandes de diagnostic

Les commandes de diagnostic permettent de vérifier le bon fonctionnement de l'environnement Commerce.

Par exemple :

```
Configuration

↓

Providers

↓

Connectivité

↓

Clés API

↓

Webhooks
```

Elles permettent de détecter rapidement une erreur de configuration.

---

## Commandes de maintenance

Les commandes de maintenance réalisent des opérations planifiées.

Par exemple :

- nettoyage ;
- recalculs ;
- reconstruction d'index ;
- suppression de données temporaires.

Ces opérations ne doivent jamais modifier les règles métier.

---

## Commandes de réparation

Certaines commandes permettent de corriger automatiquement des incohérences identifiées.

Exemples :

```
Reconstruction des accès

↓

Recalcul des Entitlements

↓

Réparation d'une Timeline

↓

Recréation d'un lien métier
```

Ces commandes doivent être idempotentes autant que possible.

---

## Commandes de migration

Les migrations permettent d'accompagner les évolutions de l'architecture Commerce.

Elles peuvent notamment :

- convertir un ancien modèle ;
- compléter des données manquantes ;
- créer de nouvelles structures ;
- harmoniser des informations existantes.

Une migration doit toujours être :

- reproductible ;
- documentée ;
- testée ;
- journalisée.

---

# Procédure d'investigation recommandée

Lorsqu'un incident est signalé, il est recommandé de suivre systématiquement la même méthode.

Cette standardisation permet :

- d'éviter les oublis ;
- d'accélérer le diagnostic ;
- de faciliter la collaboration entre développeurs.

---

## Étape 1 : identifier l'utilisateur

Toujours commencer par identifier précisément :

- l'utilisateur ;
- le produit acheté ;
- la date ;
- le Provider utilisé.

Ces informations permettront de retrouver la transaction concernée.

---

## Étape 2 : retrouver la Purchase

Une fois l'utilisateur identifié, retrouver la Commerce Purchase correspondante.

Vérifier notamment :

- son type ;
- son état ;
- ses métadonnées.

Cette étape permet de confirmer que le Checkout a bien été réalisé.

---

## Étape 3 : analyser le Payment Request

Le Payment Request constitue le point central du diagnostic.

Contrôler notamment :

- son identifiant ;
- son statut ;
- le Provider associé ;
- les montants ;
- la devise.

À ce stade, il est généralement possible de déterminer si le problème concerne :

- le paiement ;
- la validation ;
- le Fulfillment.

---

## Étape 4 : vérifier le Provider

Comparer les informations locales avec celles du prestataire.

Par exemple :

```
Transaction Stripe

↓

Payment Request

↓

Même montant ?

↓

Même devise ?

↓

Même identifiant ?

↓

Même statut ?
```

Toute divergence doit être expliquée avant d'aller plus loin.

---

## Étape 5 : analyser le Fulfillment

Si le paiement est valide :

- le Fulfillment a-t-il démarré ?
- s'est-il terminé ?
- quelle exception éventuelle a été levée ?
- quels droits ont été créés ?

Cette étape permet de localiser précisément le point de rupture.

---

## Étape 6 : vérifier les effets métier

Enfin, contrôler les conséquences visibles :

- Subscription créée ;
- Entitlements ;
- accès Moodle ;
- Timeline CRM ;
- emails.

L'analyse doit toujours partir de la transaction et se terminer par les effets métier, jamais l'inverse.

---

# Checklist de diagnostic

La checklist suivante peut être utilisée lors d'un incident.

```
□ Utilisateur identifié

□ Produit identifié

□ Purchase retrouvée

□ Payment Request retrouvée

□ Provider confirmé

□ Paiement validé

□ Signature correcte

□ Fulfillment exécuté

□ Subscription créée

□ Entitlements créés

□ Accès Moodle ouverts

□ Timeline créée

□ Emails envoyés

□ Incident documenté
```

Cette procédure garantit un diagnostic reproductible.

---

# Bonnes pratiques

L'expérience acquise lors de la conception du moteur Commerce a permis d'identifier plusieurs règles importantes.

---

## Ne jamais modifier directement la base

Une correction manuelle doit rester exceptionnelle.

Une modification directe peut rompre les liens entre :

- Purchase ;
- Payment Request ;
- Fulfillment ;
- CRM ;
- Subscription.

Lorsqu'une correction est nécessaire, privilégier :

- les outils d'administration ;
- les commandes CLI ;
- les procédures prévues par le moteur.

---

## Toujours conserver les journaux

Les journaux constituent souvent la seule preuve du déroulement exact d'une transaction.

Ils permettent notamment :

- d'expliquer un incident ;
- de répondre à une contestation ;
- de reproduire un bug ;
- d'améliorer le moteur.

Ils doivent donc être conservés selon une politique adaptée au projet.

---

## Ne jamais contourner le Runtime

Même lors d'une intervention d'urgence, il est fortement déconseillé d'appeler directement :

- un Provider ;
- un Fulfillment Handler ;
- un Purchase Handler.

Toutes les opérations doivent passer par le Runtime afin de préserver :

- la cohérence ;
- la journalisation ;
- l'idempotence ;
- les événements.

---

## Tester les procédures de reprise

Une procédure de reprise non testée ne peut pas être considérée comme fiable.

Il est recommandé de tester régulièrement :

- les reprises après erreur ;
- les webhooks rejoués ;
- les interruptions réseau ;
- les Fulfillments incomplets.

Ces exercices permettent de vérifier que l'architecture reste robuste face aux incidents réels.

---

# Conclusion

Le moteur Commerce ne se limite pas à exécuter des paiements.

Il a également été conçu pour être :

- observable ;
- auditable ;
- diagnostiquable ;
- réparable.

Cette philosophie constitue un élément essentiel de sa robustesse.

En distinguant clairement :

- le paiement ;
- la validation ;
- le Fulfillment ;
- les effets métier,

le moteur permet de localiser rapidement une anomalie et de mettre en œuvre une stratégie de correction adaptée, sans compromettre l'intégrité des données.

Les diagnostics, les audits et les procédures de reprise ne sont donc pas des fonctionnalités annexes : ils font partie intégrante de l'architecture Commerce.

Le document suivant complète cette documentation en expliquant comment étendre proprement le moteur avec de nouveaux composants.

---

# Voir aussi

- **`commerce_overview.md`** — Architecture générale et concepts métier.
- **`commerce_operations.md`** — Déroulement opérationnel du cycle de vie d'un achat.
- **`commerce_extension.md`** — Création de nouveaux Providers, Purchase Handlers, Fulfillment Handlers et points d'extension.