# Diagnostiquer les problèmes de paiement digital

Le module **Digital Purchases** centralise les achats de cours, les abonnements, les upgrades et les événements financiers visibles dans le CRM.

Lorsqu’un paiement semble absent, dupliqué ou incohérent, l’objectif est de distinguer rapidement un problème d’affichage, un problème de synchronisation et un problème réel de transaction.

## Statuts à connaître

Selon le fournisseur de paiement, une transaction peut être :

- créée ;
- en attente ;
- autorisée ;
- payée ;
- échouée ;
- annulée ;
- remboursée ;
- partiellement remboursée.

Seuls les statuts considérés comme finalisés par le service métier doivent contribuer au chiffre d’affaires.

## Paiement réussi mais achat absent

Si le prestataire confirme le paiement mais qu’aucun achat n’apparaît :

1. vérifiez l’identifiant externe de transaction ;
2. recherchez l’utilisateur par e-mail ;
3. contrôlez les logs de callback ou webhook ;
4. vérifiez que la tâche de synchronisation a été exécutée ;
5. contrôlez l’existence de la commande locale ;
6. vérifiez les erreurs PHP et DML ;
7. confirmez que le plan ciblé existe et est actif.

Ne créez pas immédiatement un enregistrement manuel. Une reprise automatique peut arriver plus tard et produire un doublon.

## Achat visible mais accès non accordé

L’achat et l’accès sont deux étapes distinctes.

Vérifiez :

- le plan acheté ;
- les entitlements du plan ;
- les scopes d’accès ;
- le course ID concerné ;
- le rôle attendu ;
- la date de début et d’expiration ;
- la présence d’un upgrade ;
- les tâches chargées de synchroniser les inscriptions.

Les règles d’accès doivent être gérées par les services prévus à cet effet, pas par le renderer.

## Montant incorrect

Un montant incorrect peut provenir :

- d’une remise ;
- d’un upgrade facturé par différence ;
- d’une devise mal interprétée ;
- d’un arrondi ;
- d’un remboursement ;
- d’une ancienne configuration de plan ;
- d’un montant provider différent du montant attendu.

Pour un upgrade, le montant attendu est généralement la différence entre le plan cible et le plan source, après application éventuelle de la remise autorisée.

Un montant inférieur ou égal à zéro doit être bloqué par les règles métier.

## Problèmes de devise

Les montants doivent toujours conserver leur devise d’origine.

Ne jamais additionner directement :

```text
100 EUR + 10 000 RUB
```

Le Dashboard doit présenter un total séparé par devise.

Toute conversion nécessite une règle explicite, une source de taux et une date de référence. En l’absence de mécanisme de conversion documenté, aucune conversion implicite ne doit être réalisée.

## Paiements en double

En cas de suspicion de doublon :

- comparez l’identifiant externe ;
- comparez la date et le montant ;
- vérifiez si deux callbacks ont été reçus ;
- contrôlez la clé d’idempotence ;
- vérifiez l’unicité en base ;
- recherchez les tentatives précédentes.

L’idempotence doit être assurée dans la couche service ou repository.

## Remboursements

Un remboursement ne doit pas supprimer l’historique initial.

Le CRM doit conserver :

- l’achat d’origine ;
- le montant payé ;
- la date ;
- le remboursement ;
- son montant ;
- son motif ;
- son statut ;
- l’impact éventuel sur l’accès.

La suppression de l’accès dépend des règles commerciales du produit et ne doit pas être déduite uniquement du statut financier.

## Sécurité

Toute action administrative liée à un paiement doit être protégée par :

- une capability Moodle adaptée ;
- `AdminSecurity` ;
- un sesskey pour les actions mutables ;
- une validation stricte des paramètres ;
- une journalisation exploitable.

Les écrans ne doivent jamais exposer de secret provider ni de donnée bancaire sensible.

## Architecture recommandée

Le traitement doit respecter les responsabilités suivantes :

- repositories : accès SQL ;
- services : règles de paiement ;
- providers/connecteurs : communication externe ;
- renderers/templates : affichage ;
- `subscription_config` : configuration et routes centralisées ;
- capabilities : contrôle fonctionnel.

## Checklist de résolution

Avant de clôturer un incident :

- confirmer le statut chez le provider ;
- confirmer le statut local ;
- vérifier l’utilisateur associé ;
- vérifier la devise ;
- vérifier le montant ;
- vérifier l’idempotence ;
- vérifier l’accès accordé ;
- vérifier la timeline CRM ;
- noter l’action corrective ;
- éviter toute modification SQL non documentée.
