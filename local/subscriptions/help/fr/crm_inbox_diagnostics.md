# Diagnostiquer la CRM Inbox

La page de diagnostics de la CRM Inbox permet de vérifier que la réception, l’envoi, la synchronisation et l’assistance IA fonctionnent correctement.

Elle doit être utilisée avant de modifier manuellement les données en base ou de relancer plusieurs fois une synchronisation.

## Avant de commencer

Vérifiez que vous disposez de la capacité de gestion de la configuration CRM.

Les diagnostics peuvent afficher des informations techniques sur :

- les comptes email configurés ;
- les connecteurs IMAP et SMTP ;
- les dossiers distants ;
- les synchronisations ;
- les pièces jointes ;
- le fournisseur IA ;
- les quotas ;
- le cache ;
- les erreurs récentes.

Ils n’affichent jamais les mots de passe en clair.

## Diagnostic de la connexion IMAP

IMAP est utilisé pour recevoir les emails.

Le diagnostic vérifie notamment :

- que l’extension PHP IMAP est installée ;
- que le compte Inbox est actif ;
- que les identifiants nécessaires sont présents ;
- que le serveur distant peut être contacté ;
- que l’authentification fonctionne ;
- que les dossiers configurés existent.

Une erreur IMAP peut empêcher toute nouvelle réception sans empêcher l’affichage des conversations déjà enregistrées dans le CRM.

### Causes fréquentes

- extension PHP IMAP absente ;
- service PHP-FPM non redémarré après l’installation de l’extension ;
- mot de passe incorrect ;
- nom d’hôte ou port incorrect ;
- connexion TLS refusée ;
- dossier distant renommé ;
- accès IMAP désactivé chez le fournisseur.

## Diagnostic SMTP

SMTP est utilisé pour envoyer les réponses.

Le diagnostic vérifie :

- l’accès au serveur SMTP ;
- l’authentification ;
- le port ;
- le chiffrement ;
- l’adresse d’expédition.

Une connexion IMAP réussie ne garantit pas que SMTP fonctionne également. Les deux connecteurs doivent être testés séparément.

## Synchronisation

La synchronisation Inbox est idempotente.

Cela signifie qu’un même email distant ne doit pas être importé plusieurs fois.

Les principaux compteurs sont :

- **récupérés** : messages lus chez le fournisseur ;
- **créés** : nouveaux messages enregistrés dans Moodle ;
- **mis à jour** : messages déjà connus dont certaines informations ont changé ;
- **ignorés** : messages qui ne nécessitent aucune modification ;
- **erreurs** : messages qui n’ont pas pu être traités ;
- **reste à traiter** : d’autres messages sont disponibles dans le dossier distant.

### En cas de synchronisation incomplète

Ne relancez pas immédiatement plusieurs synchronisations simultanées.

Vérifiez d’abord :

1. la présence d’un verrou actif ;
2. la taille du batch ;
3. la date du dernier curseur ;
4. le nombre d’erreurs ;
5. l’état du dossier distant ;
6. les logs de la tâche planifiée.

La CLI et le cron peuvent poursuivre le traitement par lots.

## Pièces jointes

Les pièces jointes sont copiées dans Moodle File API.

Les diagnostics peuvent signaler :

- un téléchargement distant impossible ;
- un contenu vide ;
- un type MIME incohérent ;
- un fichier trop volumineux ;
- une erreur de stockage Moodle ;
- une référence distante devenue inaccessible.

La suppression d’une conversation dans le CRM ne doit pas être confondue avec la suppression d’un message chez le fournisseur.

## Contacts et matching utilisateur

Un email peut être reçu d’une personne inconnue du CRM.

Dans ce cas :

- un contact externe est créé ;
- la conversation reste accessible ;
- aucun utilisateur Moodle n’est inventé ;
- le rattachement peut être effectué plus tard.

Lorsqu’un compte CampusFR est créé avec la même adresse, le rematching automatique peut rattacher le contact à l’utilisateur correspondant.

Un rattachement manuel verrouillé ne doit jamais être remplacé automatiquement.

## Diagnostic de l’assistance IA

Les diagnostics IA vérifient notamment :

- le provider actif ;
- la configuration OpenAI ;
- le modèle utilisé ;
- les capacités disponibles ;
- les quotas quotidiens ;
- les erreurs récentes ;
- le fonctionnement du cache ;
- la disponibilité du fallback local.

Un provider disponible ne garantit pas que chaque réponse sera valide. Les résultats passent encore par la validation locale et les Structured Outputs.

## États IA

Les résultats IA peuvent être :

- **success** : réponse valide ;
- **partial** : réponse utilisable mais incomplète ;
- **failed** : réponse absente, invalide ou rejetée ;
- **cached** : résultat valide relu depuis le cache.

Une réponse invalide ne doit jamais être présentée comme une suggestion approuvée.

## Quotas

Deux limites peuvent s’appliquer :

- quota global quotidien ;
- quota quotidien par administrateur.

Lorsque le quota est atteint :

- aucune nouvelle requête distante ne doit être envoyée ;
- les résultats déjà mis en cache peuvent rester disponibles ;
- les fonctions métier de l’Inbox continuent de fonctionner sans IA.

## Procédure de dépannage recommandée

En cas de problème :

1. ouvrir les diagnostics Inbox ;
2. vérifier IMAP et SMTP séparément ;
3. vérifier le dernier journal de synchronisation ;
4. contrôler les erreurs de pièces jointes ;
5. lancer une synchronisation manuelle unique ;
6. ouvrir les diagnostics IA si le problème concerne l’analyse ;
7. vérifier les quotas et le provider ;
8. consulter les logs Moodle ;
9. tester avec les CLI prévues par le plugin.

## Ce qu’il ne faut pas faire

Évitez de :

- modifier directement les curseurs de synchronisation ;
- supprimer les provider keys ;
- vider les tables Inbox pour résoudre un doublon ;
- copier des mots de passe dans les logs ;
- désactiver la validation des réponses IA ;
- exécuter plusieurs synchronisations concurrentes ;
- envoyer une suggestion IA sans relecture humaine.

## Validation finale

Après correction :

- la connexion IMAP doit réussir ;
- la connexion SMTP doit réussir ;
- une synchronisation doit terminer sans erreur bloquante ;
- un nouvel email doit être importé une seule fois ;
- une réponse doit être enregistrée dans l’historique ;
- les pièces jointes doivent être accessibles uniquement aux utilisateurs autorisés ;
- les diagnostics IA doivent reconnaître le provider configuré ;
- une suggestion IA doit rester modifiable avant envoi.