# Utiliser les filtres du User Explorer

Le **User Explorer** est l’outil principal de recherche, de segmentation et d’investigation des profils dans le CRM de `local_subscriptions`. Il permet de transformer un volume important d’utilisateurs en listes exploitables pour le support, le suivi commercial et l’analyse.

Les filtres peuvent être utilisés seuls ou combinés. Leur comportement doit rester prévisible : chaque critère réduit le périmètre affiché sans modifier les données sources.

## Principes de fonctionnement

Le User Explorer repose sur trois niveaux :

1. une couche SQL responsable de la sélection et du tri ;
2. des services responsables des règles métiers ;
3. des renderers responsables de l’affichage.

Les templates ne doivent jamais exécuter de requêtes ni recalculer des statuts complexes.

## Recherche textuelle

Le champ de recherche peut généralement interroger :

- le nom affiché ;
- l’adresse e-mail principale ;
- l’objet d’une conversation ;
- certains identifiants fonctionnels ;
- des informations indexées par le CRM.

La recherche doit être normalisée en minuscules côté SQL à l’aide des helpers Moodle compatibles avec les bases prises en charge. Il faut éviter les fonctions SQL spécifiques à un moteur.

Une recherche textuelle ne remplace pas les filtres métier. Pour trouver les utilisateurs en essai sans achat, utilisez les filtres dédiés plutôt qu’un mot-clé approximatif.

## Filtres de statut commercial

Les filtres commerciaux peuvent inclure :

- essai actif ;
- essai expiré ;
- achat de cours ;
- abonnement actif ;
- abonnement expiré ;
- client lifetime ;
- upgrade disponible ;
- paiement en attente ou en anomalie.

Les statuts doivent être calculés par les services métier du plugin. Un même utilisateur peut apparaître dans plusieurs segments selon le contexte.

## Filtres d’activité

Les filtres d’activité servent à identifier :

- les utilisateurs récemment actifs ;
- les utilisateurs inactifs depuis une durée donnée ;
- les comptes sans connexion ;
- les profils avec activité CRM récente ;
- les profils sans interaction depuis une période définie.

Les dates doivent être interprétées dans le fuseau horaire Moodle et converties proprement pour les requêtes.

## Filtres CRM Intelligence

Les segments issus de **CRM Intelligence** peuvent être transmis directement au User Explorer via l’URL.

Exemples :

- profils à risque ;
- essais à forte intention ;
- utilisateurs à relancer ;
- paiements échoués ;
- profils nécessitant une réponse ;
- clients ayant commencé un parcours sans le terminer.

Lorsqu’un filtre est ouvert depuis le Dashboard, l’interface doit indiquer clairement le segment actif.

## Filtres liés aux achats digitaux

Les filtres Digital Purchases peuvent porter sur :

- le plan acheté ;
- la devise ;
- le statut du paiement ;
- la période d’achat ;
- le type d’opération ;
- l’origine de la commande ;
- la présence d’un remboursement ;
- l’existence d’un upgrade.

Pour éviter les incohérences, les valeurs affichées doivent provenir des mêmes services que le Dashboard et la fiche utilisateur.

## Combinaison des filtres

La plupart des filtres doivent être combinés avec une logique **ET**.

Exemple :

```text
Essai actif
ET
Aucun achat
ET
Dernière activité supérieure à 7 jours
```

Cette combinaison permet de produire une liste de relance ciblée.

Les filtres multivalués, comme plusieurs plans ou plusieurs devises, peuvent utiliser une logique **OU** à l’intérieur d’un même groupe.

## Tri et pagination

Le tri doit être appliqué côté SQL avant la pagination.

Les tris les plus utiles sont :

- dernière activité ;
- date d’inscription ;
- date du dernier achat ;
- montant cumulé ;
- niveau de risque ;
- ordre alphabétique.

Un tri effectué uniquement dans le navigateur peut produire des résultats incorrects lorsque plusieurs pages existent.

## Accès et sécurité

L’accès au User Explorer doit être protégé par une capability Moodle appropriée.

Les actions sensibles, telles que la modification de tags, le renvoi d’e-mails ou l’accès à certaines informations personnelles, doivent passer par `AdminSecurity`.

Les liens doivent utiliser `moodle_url` et, lorsque prévu, les routes définies dans `subscription_config`.

## Réinitialiser les filtres

Le bouton de réinitialisation doit :

- vider les paramètres de recherche ;
- supprimer les segments hérités du Dashboard ;
- revenir à la page 0 ;
- restaurer le tri par défaut ;
- éviter de conserver des paramètres cachés dans l’URL.

Après réinitialisation, le nombre total de résultats doit correspondre au périmètre autorisé pour l’administrateur connecté.

## Dépannage

Si un profil attendu n’apparaît pas :

1. réinitialisez tous les filtres ;
2. recherchez l’adresse e-mail exacte ;
3. vérifiez le statut du compte Moodle ;
4. contrôlez les droits de l’administrateur ;
5. vérifiez les données d’achat et d’abonnement ;
6. contrôlez les bornes de date ;
7. purgez les caches si un service vient d’être modifié.

Évitez toute correction directement dans la vue. Les problèmes de sélection doivent être corrigés dans le repository ou le service responsable.
