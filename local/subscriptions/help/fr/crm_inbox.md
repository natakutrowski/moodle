# Utiliser la CRM Inbox

La CRM Inbox centralise les emails reçus par `support@campusfr.fr`.

## Contacts externes

Un expéditeur n’a pas besoin de posséder un compte CampusFR.

La conversation est enregistrée avec un contact externe. Si cette personne crée plus tard un compte avec la même adresse email, le CRM peut rattacher automatiquement son historique.

Un rattachement manuel verrouillé n’est jamais remplacé automatiquement.

## Statuts

- **Ouvert** : une action est attendue.
- **En attente** : la réponse d’un tiers ou du contact est attendue.
- **Résolu** : la demande a été traitée.
- **Fermé** : la conversation est terminée.
- **Spam** : le message n’est pas une demande légitime.

## Priorités

Utilisez les priorités élevée et urgente uniquement lorsque la demande exige un traitement rapide.

## Assignation

Une conversation peut être assignée :

- à un administrateur ;
- à une équipe ;
- à un administrateur appartenant à une équipe.

Le rattachement au client et l’assignation administrative sont deux notions différentes.

## Répondre

Vous pouvez enregistrer un brouillon ou envoyer directement la réponse depuis le CRM.

Les réponses sont envoyées depuis `support@campusfr.fr`.

## Pièces jointes

Les pièces jointes sont copiées dans Moodle File API et restent protégées par la capacité de lecture Inbox.

## Archivage et suppression

- **Suppression locale** : masque uniquement la conversation dans le CRM.
- **Archivage** : déplace le message dans le dossier d’archives du fournisseur.
- **Corbeille** : déplace le message dans la corbeille du fournisseur.

Aucune suppression définitive distante n’est effectuée automatiquement.

## Diagnostic

La page de diagnostic vérifie :

- l’extension PHP IMAP ;
- les identifiants ;
- la connexion IMAP ;
- la connexion SMTP ;
- les tables ;
- les dossiers ;
- les erreurs de synchronisation ;
- les pièces jointes en erreur.