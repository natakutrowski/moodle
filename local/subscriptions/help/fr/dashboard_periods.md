# Comprendre les périodes du Dashboard CRM

Le Dashboard CRM de `local_subscriptions` propose plusieurs périodes d’analyse afin de donner aux administrateurs une lecture immédiate de l’activité commerciale et opérationnelle. Le sélecteur de période permet de basculer entre **Aujourd’hui**, **Semaine en cours** et **Mois en cours** sans quitter la page.

Cette fonctionnalité est conçue pour éviter une lecture trompeuse des indicateurs. Une journée calme ne signifie pas nécessairement que l’activité est faible : la vue hebdomadaire ou mensuelle permet de replacer les données dans leur contexte.

## Périodes disponibles

### Aujourd’hui

La période **Aujourd’hui** présente les événements enregistrés depuis le début de la journée dans le fuseau horaire Moodle.

Elle est adaptée pour :

- suivre les paiements et inscriptions récents ;
- contrôler les essais démarrés dans la journée ;
- vérifier les nouvelles conversations ou actions CRM ;
- surveiller l’activité d’une campagne en cours ;
- détecter rapidement une baisse inhabituelle d’activité.

Les données sont volontairement très réactives. Elles peuvent donc varier plusieurs fois au cours de la journée.

### Semaine en cours

La période **Semaine en cours** regroupe les données depuis le premier jour de la semaine défini par la configuration Moodle jusqu’à maintenant.

Elle permet de :

- comparer plusieurs journées sans changer d’écran ;
- identifier les jours les plus actifs ;
- observer la progression des ventes ;
- mesurer le volume de profils à suivre ;
- vérifier si une anomalie quotidienne est isolée ou récurrente.

Cette vue est généralement la plus utile pour le pilotage opérationnel.

### Mois en cours

La période **Mois en cours** couvre l’intervalle allant du premier jour du mois jusqu’à la date actuelle.

Elle est recommandée pour :

- suivre les objectifs mensuels ;
- analyser la répartition des achats par devise ;
- mesurer les conversions d’essai vers achat ;
- suivre l’évolution des profils à risque ;
- préparer un reporting commercial.

Le mois en cours n’est pas une projection. Les valeurs affichées correspondent uniquement aux événements déjà enregistrés.

## Indicateurs du Hero

Le bloc principal, appelé **Hero**, regroupe les indicateurs les plus importants pour la période sélectionnée. Selon la configuration du plugin, il peut inclure :

- le nombre de nouveaux utilisateurs ;
- le nombre d’essais démarrés ;
- les achats digitaux ;
- le chiffre d’affaires digital ;
- les profils nécessitant une attention ;
- les interactions CRM récentes.

Le changement de période doit recalculer l’ensemble du Hero de manière cohérente. Un indicateur ne doit pas conserver une valeur issue d’une période précédente.

## Chiffre d’affaires par devise

Le chiffre d’affaires digital doit être présenté séparément pour chaque devise disponible. Une somme en EUR ne doit jamais être additionnée directement à une somme en RUB ou dans une autre devise.

Exemple :

```text
EUR : 3 420,00 €
RUB : 186 000 ₽
```

Cette séparation garantit une lecture financière correcte et évite les conversions implicites non maîtrisées.

Les montants proviennent des sources de paiement prises en charge par le module **Digital Purchases**. Les éventuels remboursements, paiements incomplets ou statuts non finalisés doivent être traités conformément aux règles métiers du service concerné.

## Navigation depuis CRM Intelligence

Les cartes du bloc **CRM Intelligence** peuvent signaler des groupes d’utilisateurs, par exemple :

- profils à risque ;
- essais sans conversion ;
- utilisateurs sans activité récente ;
- paiements en anomalie ;
- conversations nécessitant une réponse.

Lorsqu’un compteur est cliquable, il doit ouvrir le **User Explorer** avec les filtres correspondants déjà appliqués. L’administrateur doit pouvoir passer du diagnostic à l’action sans reconstruire manuellement la recherche.

## Cohérence technique

Le Dashboard doit respecter la séparation des responsabilités du plugin :

- les requêtes SQL appartiennent aux repositories ou composants d’accès aux données ;
- les calculs métiers appartiennent aux services ;
- les renderers et templates ne doivent contenir aucune logique SQL ;
- les contrôles d’accès doivent être centralisés via `AdminSecurity` ;
- les URLs doivent être générées à partir de `subscription_config` lorsque cela est prévu ;
- les fonctionnalités sensibles doivent être protégées par les capabilities Moodle adaptées.

Cette organisation facilite la maintenance et limite les régressions.

## Vérifications en cas de données incohérentes

Si un indicateur semble incorrect :

1. vérifiez la période sélectionnée ;
2. contrôlez le fuseau horaire Moodle ;
3. confirmez le statut réel des paiements ;
4. purgez les caches Moodle ;
5. exécutez les tâches planifiées associées ;
6. vérifiez que les sources de données utilisent les mêmes bornes temporelles ;
7. contrôlez les logs du plugin.

Il est déconseillé de corriger les valeurs directement dans les templates ou renderers. Toute anomalie doit être traitée à la source, dans la couche SQL ou dans le service métier concerné.

## Bonnes pratiques administrateur

Utilisez la vue **Aujourd’hui** pour la surveillance immédiate, la vue **Semaine en cours** pour les décisions opérationnelles et la vue **Mois en cours** pour le reporting.

Avant d’exporter ou de communiquer un chiffre, vérifiez toujours :

- la période active ;
- la devise ;
- le statut des paiements inclus ;
- la date et l’heure de génération ;
- les éventuels filtres hérités d’une navigation précédente.
