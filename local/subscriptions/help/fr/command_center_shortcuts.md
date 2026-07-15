# Raccourcis et recherche dans le Command Center

Le **Command Center** permet aux administrateurs de naviguer rapidement dans le CRM et d’exécuter des recherches sans parcourir manuellement tous les menus.

Il agrège des commandes provenant de plusieurs providers internes : navigation, utilisateurs, paiements, aide, outils et actions contextuelles.

## Ouvrir le Command Center

Le raccourci clavier dépend de l’interface déployée. Les combinaisons habituelles sont :

```text
Ctrl + K
Cmd + K
```

Le raccourci ne doit pas entrer en conflit avec les champs de saisie standards de Moodle.

## Rechercher une commande

Saisissez quelques caractères pour filtrer les commandes.

Exemples :

```text
dashboard
users
payments
inbox
help
```

La recherche peut accepter des alias ou des préfixes.

## Préfixe d’aide

Lorsqu’il est disponible, le préfixe :

```text
> help
```

permet d’afficher les catégories ou articles du Help Center.

La commande simple :

```text
help
```

peut aussi retourner les résultats d’aide si le provider est configuré pour accepter les recherches non préfixées.

## Navigation clavier

Les comportements recommandés sont :

- `Flèche bas` : résultat suivant ;
- `Flèche haut` : résultat précédent ;
- `Entrée` : ouvrir ou exécuter ;
- `Échap` : fermer ;
- `Tab` : conserver une navigation accessible ;
- `Ctrl/Cmd + K` : ouvrir ou fermer.

Le focus doit toujours rester visible.

## Commandes récentes

Le Command Center peut mémoriser les commandes récentes dans le stockage local du navigateur.

Une action **Effacer les commandes récentes** doit supprimer uniquement l’historique local et ne pas affecter les données Moodle.

Après nettoyage, l’état initial doit être recalculé immédiatement.

## Providers

Chaque famille de commandes doit être fournie par un provider conforme au contrat du Command Center.

Un provider doit :

- recevoir une requête normalisée ;
- retourner des résultats structurés ;
- respecter les permissions ;
- générer des URLs sûres ;
- éviter les requêtes lourdes inutiles ;
- ne pas rendre directement du HTML arbitraire.

## Commandes liées aux utilisateurs

Les commandes utilisateur peuvent permettre :

- d’ouvrir un profil CRM ;
- de rechercher par nom ou e-mail ;
- d’accéder au User Explorer ;
- d’ouvrir une conversation ;
- de consulter les achats récents.

Les résultats doivent respecter les capabilities du compte connecté.

## Commandes d’aide

Le provider d’aide doit interroger les métadonnées du Help Center et les fichiers Markdown disponibles dans la langue active.

Si un article manque dans la langue active, la stratégie de fallback doit être explicite. Une erreur de validation doit signaler le fichier absent.

## Sécurité

Le Command Center n’accorde aucun privilège supplémentaire.

Chaque destination doit refaire ses propres contrôles via :

- `require_login()` ;
- `AdminSecurity` ;
- les capabilities Moodle ;
- les sesskeys pour les actions mutables.

Masquer une commande ne suffit pas à sécuriser sa destination.

## Dépannage

Si une commande n’apparaît pas :

1. purgez les caches ;
2. vérifiez que le provider est enregistré ;
3. contrôlez les alias ;
4. testez avec et sans préfixe ;
5. vérifiez les capabilities ;
6. inspectez la réponse AJAX ;
7. contrôlez les erreurs JavaScript ;
8. vérifiez que la commande retourne un score positif.

Les problèmes de visibilité proviennent souvent d’un filtre trop strict, d’un alias absent ou d’un provider non déclaré.

## Bonnes pratiques

Utilisez des noms courts, distinctifs et traduisibles.

Préférez :

```text
Ouvrir le Dashboard CRM
Rechercher un utilisateur
Voir les achats digitaux
Ouvrir la boîte CRM
Consulter l’aide
```

Évitez les libellés trop techniques dans l’interface administrateur.
