<?php
$string['actions'] = 'Actions';
$string['activityname'] = 'Nom de l’activité';
$string['activityname_help'] = 'Texte que le nom de l’activité doit contenir ou auquel il doit être égal. La recherche n’est pas sensible à la casse.';
$string['activityoresourceis'] = 'L’activité ou la ressource est {$a}';
$string['addacondition'] = 'Ajouter une condition';
$string['addarule'] = 'Ajouter une règle';
$string['addondeactivated'] = 'XP+ désactivé';
$string['addondeactivatedinfo'] = 'Les modules XP sont incompatibles entre eux, ce qui a entraîné la désactivation de XP+. La version {$a->localxpversion} de Level Up XP+ (local_xp) est attendue.';
$string['addonnotactivated'] = 'Le module complémentaire n’est pas activé.';
$string['adminnoticeaddondeactivatedsubject'] = 'Extension XP+ désactivée !';
$string['adminnoticeaddondeactivatedmessage'] = 'Level Up XP+ a été désactivé !

Vous recevez cet avertissement car XP+ a été désactivé pour éviter des problèmes potentiels.  
Les deux modules Level Up XP (block_xp) et Level Up XP+ (local_xp) sont actuellement incompatibles.  
Ce problème apparaît quand XP a été mis à jour vers une nouvelle version majeure alors que XP+ est resté obsolète.

Ce décalage peut entraîner la perte de fonctionnalités ou des bugs.  
Pour corriger, mettez à jour Level Up XP+.

- Version Level Up XP (block_xp) : {$a->blockxpversion}  
- Version Level Up XP+ (local_xp) : {$a->localxpversion}  
- Version attendue de XP+ : {$a->localxpversionexpected}

Ressources complémentaires :

- [Documentation de mise à jour](https://docs.levelup.plus/xp/docs/upgrade)  
- [Documentation XP+ désactivé](https://docs.levelup.plus/xp/docs/addon-deactivated)  
- [Compatibilité](https://docs.levelup.plus/xp/docs/requirements-compatibility)

--

Cet avis a été envoyé à tous les administrateurs. Pour désactiver ces notifications, consultez les paramètres d’administration de Level Up XP.';
$string['adminnoticeoutofsyncsubject'] = 'Incompatibilité entre les plugins XP !';
$string['adminnoticeoutofsyncmessage'] = 'Incompatibilité entre Level Up XP et Level Up XP+ !

Vous recevez cet avertissement car les deux modules Level Up XP (block_xp) et XP+ (local_xp) ne sont plus synchronisés.  
Cela arrive quand XP est mis à jour vers une version majeure et pas XP+.

Ce décalage peut causer des pertes de fonction et des erreurs.  
Pour corriger, mettez à jour XP+.

**Important !** À l’avenir, si ces modules ne sont pas synchronisés, XP+ se désactivera automatiquement.  
Évitez cela en mettant toujours à jour XP+ en même temps que XP.

- Version Level Up XP (block_xp) : {$a->blockxpversion}  
- Version Level Up XP+ (local_xp) : {$a->localxpversion}  
- Version attendue de XP+ : {$a->localxpversionexpected}

Ressources :

- [Documentation de mise à jour](https://docs.levelup.plus/xp/docs/upgrade)  
- [Compatibilité](https://docs.levelup.plus/xp/docs/requirements-compatibility)

--

Avis envoyé à tous les administrateurs.';
$string['adminnotices'] = 'Notifications administrateur';
$string['adminnotices_desc'] = 'Lorsqu’elles sont activées, les administrateurs peuvent recevoir ponctuellement des messages importants concernant la compatibilité, la sécurité ou la disponibilité de nouvelles versions de XP+.';
$string['adminscanearnxp'] = 'Les administrateurs peuvent gagner des points';
$string['adminscanearnxp_desc'] = 'Par défaut, les administrateurs ne font pas partie des utilisateurs pouvant gagner des points. Cette option permet de les inclure.';
$string['admindefaultrulesintro'] = 'Les règles suivantes seront utilisées par défaut dans les cours où le bloc est ajouté.';
$string['admindefaultsettingsintro'] = 'Les réglages ci-dessous seront appliqués par défaut lorsqu’un nouveau bloc est ajouté à un cours. Certains paramètres peuvent être verrouillés afin d’être imposés à toutes les instances.';
$string['admindefaultvisualsintro'] = 'Les éléments suivants seront utilisés par défaut lors de l’ajout d’un nouveau bloc.';
$string['additionalresources'] = 'Ressources supplémentaires';
$string['addlevel'] = 'Ajouter un niveau';
$string['addoninstallationerror'] = 'Un problème a été détecté avec le module local_xp ; il semble mal installé. Un administrateur doit finaliser son installation.';
$string['allcoursesreset'] = 'Tous les cours ont été réinitialisés.';
$string['anonymity'] = 'Anonymat';
$string['anonymity_help'] = 'Détermine si les participants peuvent voir les noms et avatars des autres.';
$string['apply'] = 'Appliquer';
$string['awardaxpwhen'] = '<strong>{$a}</strong> points sont attribués lorsque :';
$string['badgeaward'] = 'Badge à attribuer';
$string['badgeawarddesc'] = 'Badge décerné lorsque l’utilisateur atteint ce niveau.';
$string['basepoints'] = 'Points de base';
$string['basepointslineardesc'] = 'Incrément minimal entre chaque niveau.';
$string['basepointsrelativedesc'] = 'Nombre de points de départ.';
$string['blockappearance'] = 'Apparence du bloc';
$string['blockappearancemovedtopluginsettings'] = 'Les paramètres d’apparence ont été déplacés vers la page de configuration du plugin.';
$string['cachedef_block_count'] = 'Nombre d’instances du bloc';
$string['cachedef_filters'] = 'Filtres de niveau';
$string['cachedef_metadata'] = 'Métadonnées';
$string['cachedef_ruleevent_eventslist'] = 'Liste d’événements';
$string['canjoinfromdatex'] = 'Vous pourrez rejoindre à partir du {$a}.';
$string['cannotbesetindefaults'] = 'Ne peut pas être défini dans les valeurs par défaut.';
$string['cannotearnpoints'] = 'Impossible de gagner des points.';
$string['cannotshowblockconfig'] = 'Impossible d’afficher les paramètres d’apparence du bloc ; assurez-vous que le bloc est bien présent dans votre cours, activez le mode édition puis utilisez le menu **Configurer** du bloc. Si le bloc est introuvable, ajoutez-le à nouveau.';
$string['cannotshowblockconfigsys'] = 'Impossible d’afficher les paramètres d’apparence ; le bloc est peut-être absent de la [page d’accueil]({$a->fp}) ou du [tableau de bord]({$a->mysys}) des utilisateurs (ou présent sur les deux). Pour modifier les paramètres ici, il doit apparaître sur une seule page.';
$string['changecourse'] = 'Changer de cours';
$string['changetocourse'] = 'Aller au cours';
$string['changetositewide'] = 'Revenir au site complet';
$string['cheatguard'] = 'Anti-triche';
$string['cheatguardsettingsmovednotice'] = 'Les paramètres de l’anti-triche ont été déplacés vers la [page des règles d’événement]({$a->url}).';
$string['checkaddoncompatibility'] = 'Compatibilité de l’extension Level Up XP';
$string['chooseacondition'] = 'Choisir une condition';
$string['clearfilter'] = 'Effacer le filtre';
$string['clicktoselectcm'] = 'Cliquer pour sélectionner une activité ou ressource';
$string['cmselector'] = 'Sélecteur d’activité';
$string['coefxp'] = 'Coefficient de l’algorithme';
$string['colon'] = '{$a->a} : {$a->b}';
$string['comparisonmethod'] = 'Méthode de comparaison';
$string['compatibilitycheck'] = 'Vérification de compatibilité';
$string['completionrules'] = 'Règles d’achèvement';
$string['completionrules_help'] = 'Les règles d’achèvement se divisent en trois catégories : achèvement d’activité, de section et de cours.  
L’ajout de conditions dans ces catégories détermine quand et combien de points sont attribués.

Les règles sont évaluées dans l’ordre d’affichage. Dès qu’une condition est remplie, les points correspondants sont attribués et les suivantes ne sont pas évaluées.';
$string['completionrulesintro'] = 'Attribuer des points lorsque les étudiants terminent des activités, sections ou cours.';
$string['completionruleslegacyusednotice'] = 'Vous utilisez encore des « Règles d’événement » basées sur les conditions d’achèvement. Il est recommandé de les supprimer au profit des nouvelles règles d’achèvement ; utiliser les deux peut doubler les points attribués.';
$string['condition'] = 'Condition';
$string['configdescription'] = 'Introduction';
$string['configdescription_help'] = 'Message d’introduction court affiché dans le bloc. Les étudiants peuvent le masquer définitivement.';
$string['configheader'] = 'Paramètres';
$string['configtitle'] = 'Titre';
$string['configtitle_help'] = 'Titre du bloc.';
$string['configblockrankingsnapshot'] = 'Afficher un aperçu du classement';
$string['configblockrankingsnapshot_help'] = 'Affiche le rang de l’utilisateur et tente de montrer les deux personnes qui l’entourent. Nécessite que le classement soit activé.';
$string['configrecentactivity'] = 'Afficher les dernières récompenses';
$string['configrecentactivity_help'] = 'Si activé, le bloc affiche une courte liste des événements récents ayant rapporté des points à l’étudiant.';
$string['congratulationsyouleveledup'] = 'Félicitations !';
$string['coolthanks'] = 'Super, merci !';
$string['copiedexcl'] = 'Copié !';
$string['coursea'] = 'Cours : "{$a}"';
$string['courselog'] = 'Journal';
$string['courselogintro'] = 'Le journal affiche les actions observées et les points attribués.';
$string['coursereport'] = 'Rapport';
$string['coursereportintro'] = 'Ce rapport détaille les participants et permet d’agir individuellement ou collectivement.';
$string['courseselectedcolon'] = 'Cours sélectionné :';
$string['coursesettings'] = 'Paramètres du cours';
$string['currencysign'] = 'Symbole des points';
$string['currencysign_help'] = 'Permet de modifier la représentation des points, affichée à côté de leur valeur (par exemple : XP, étoiles, croissants…).  
Choisissez un symbole proposé ou importez le vôtre !';
$string['currencysignxp'] = 'XP (points d’expérience)';
$string['customizelevels'] = 'Personnaliser les niveaux';
$string['dangerzone'] = 'Zone à risque';
$string['dataformat'] = 'Format';
$string['defaultlevels'] = 'Niveaux par défaut';
$string['defaultrules'] = 'Règles par défaut';
$string['defaultsettings'] = 'Paramètres par défaut';
$string['defaultvisuals'] = 'Apparence par défaut';
$string['deletecondition'] = 'Supprimer la condition';
$string['deleterule'] = 'Supprimer la règle';
$string['description'] = 'Description';
$string['difference'] = 'Diff.';
$string['difficulty'] = 'Méthode de calcul des points';
$string['difficultyflat'] = 'Égal';
$string['difficultyflatdesc'] = 'Tous les niveaux demandent le même nombre de points.';
$string['difficultylinear'] = 'Croissant';
$string['difficultylineardesc'] = 'Les niveaux deviennent progressivement plus longs à atteindre.';
$string['difficultylinearincrdesc'] = 'Nombre de points utilisés pour la progression linéaire.';
$string['difficultypointincrease'] = 'Augmentation des points';
$string['difficultyrelative'] = 'Exponentiel';
$string['difficultyrelativedesc'] = 'Les niveaux deviennent exponentiellement plus difficiles à atteindre.';
$string['difficultyrelativeincrdesc'] = 'Pourcentage d’augmentation de points par rapport au niveau précédent.';
$string['discoverlevelupplus'] = 'Découvrir Level Up XP+';
$string['dismissnotice'] = 'Ignorer l’avis';
$string['displayeveryone'] = 'Afficher tout le monde';
$string['displaynneighbours'] = 'Afficher {$a} voisins';
$string['displayoneneigbour'] = 'Afficher un voisin';
$string['displayparticipantsidentity'] = 'Afficher l’identité des participants';
$string['displayrank'] = 'Afficher le rang';
$string['displayrelativerank'] = 'Afficher le rang relatif';
$string['documentation'] = 'Documentation';
$string['drops'] = 'Drops';
$string['dropsintro'] = 'Les drops sont des fragments de code insérés directement dans le contenu, attribuant des points lorsqu’un utilisateur les rencontre.';
$string['drops_help'] = 'Dans les jeux vidéo, certains personnages peuvent “laisser tomber” des objets ou des points d’expérience que le joueur ramasse ensuite.  
Dans Level Up XP, un *drop* est un shortcode (ex. `[xpdrop id=1 secret=abcdef]`) qu’un enseignant peut placer dans un contenu Moodle.  
Lorsqu’un étudiant rencontre ce drop, il gagne automatiquement un certain nombre de points.  

Les drops sont invisibles et ne s’appliquent qu’une seule fois par utilisateur.  
Ils permettent de récompenser la consultation d’un contenu précis (le fond d’un module, une discussion, etc.).';
$string['editcondition'] = 'Modifier la condition';
$string['editingdefaultsettingsincoursemodenotice'] = '**Attention !** Vous modifiez les valeurs par défaut, pas les réglages actifs du cours. Pour modifier un cours spécifique, utilisez le lien “Paramètres” du bloc XP.';
$string['editingdefaultsettingsinwholesitemodenotice'] = '**Attention !** Vous éditez les valeurs par défaut du site. Si vous souhaitez changer les réglages globaux, [cliquez ici]({$a->url}) ou utilisez le lien “Paramètres” du bloc XP.';
$string['embedleaderboard'] = 'Intégrer le classement';
$string['enablecheatguard'] = 'Activer l’anti-triche';
$string['enablecheatguard_help'] = 'Le système anti-triche empêche les abus simples, comme rafraîchir indéfiniment la même page.  
Il bloque la répétition immédiate d’actions identiques.';
$string['enableinfos'] = 'Activer la page d’informations';
$string['enableinfos_help'] = 'Si “Non”, les étudiants ne verront pas la page d’informations.';
$string['enableladder'] = 'Activer le classement';
$string['enableladder_help'] = 'Si “Non”, les étudiants n’auront pas accès au classement.';
$string['enablelevelupnotif'] = 'Activer la notification de niveau atteint';
$string['enablelevelupnotif_help'] = 'Si “Oui”, une fenêtre félicitera l’étudiant à chaque nouveau niveau.';
$string['enablexpgain'] = 'Activer le gain de points';
$string['enablexpgain_help'] = 'Si “Non”, personne ne gagne de points dans le cours (utile pour geler la progression temporairement).';
$string['entersearchterm'] = 'Saisir un mot-clé';
$string['erroraddondeactivated'] = 'Level Up XP+ a été désactivé. Consultez la [documentation]({$a->docsurl}).';
$string['errorlevelsincorrect'] = 'Le nombre minimal de niveaux est 2.';
$string['errorunknownevent'] = 'Erreur : événement inconnu';
$string['errorunknownmodule'] = 'Erreur : module inconnu';
$string['eventsrules'] = 'Règles d’événements';
$string['eventsrules_help'] = 'Ce plugin utilise les événements Moodle pour attribuer des points aux actions réalisées par les étudiants.  
Vous pouvez ajouter vos propres règles ou modifier celles existantes.  
Consultez la page *Journal* pour voir quels événements sont déclenchés.';
$string['eventsrulesintro'] = 'Observer les actions et attribuer des points lorsqu’elles sont effectuées.';
$string['event_user_leveledup'] = 'Utilisateur monté de niveau';
$string['eventis'] = 'L’événement est {$a}';
$string['eventname'] = 'Nom de l’événement';
$string['export'] = 'Exporter';
$string['exportdata'] = 'Exporter les données';
$string['filterbyuser'] = 'Filtrer par utilisateur';
$string['filtermodules'] = 'Filtrer les modules';
$string['filterparticipants'] = 'Filtrer les participants';
$string['forever'] = 'Indéfiniment';
$string['gotofullladder'] = 'Voir le classement complet';
$string['graderules'] = 'Règles de notation';
$string['graderules_help'] = 'Les étudiants gagnent autant de points que leur note :  
une note de 5/10 et 5/100 donnent toutes deux 5 points.  
Les points ne sont jamais retirés.';
$string['graderulesintro'] = 'Les règles de notation permettent d’attribuer des points proportionnels aux notes obtenues.';
$string['hideparticipantsidentity'] = 'Masquer l’identité des participants';
$string['hiderank'] = 'Masquer le rang';
$string['importpoints'] = 'Importer des points';
$string['importpoints_help'] = 'Permet d’augmenter ou de remplacer les points d’un étudiant à partir d’un fichier CSV.  
Voir la [documentation](https://docs.levelup.plus/xp/docs/how-to/import-points/importing-points-from-csv).';
$string['importpointsintro'] = 'Importer les points depuis un fichier CSV et, si besoin, envoyer un message au destinataire.';
$string['infos'] = 'Informations';
$string['infos_help'] = 'La page d’informations affiche la liste des niveaux et les points nécessaires pour les atteindre.';
$string['infosintro'] = 'La page d’informations liste les niveaux et leurs détails.';
$string['instructions'] = 'Instructions';
$string['instructions_help'] = 'Ces instructions sont visibles sur la page d’informations. Utilisez-les pour expliquer comment gagner des points ou progresser.';
$string['joinleaderboard'] = 'Rejoindre le classement';
$string['keeplogs'] = 'Conserver les journaux';
$string['ladder'] = 'Classement';
$string['ladder_help'] = 'Le classement ordonne les étudiants selon leurs points.  
En mode groupes, un classement est généré par groupe.';
$string['ladderintro'] = 'Le classement affiche l’ordre des participants selon leur total de points.';
$string['ladderadditionalcols'] = 'Colonnes supplémentaires';
$string['ladderempty'] = 'Le classement est vide pour le moment ; revenez plus tard.';
$string['ladderparticipation'] = 'Participation au classement';
$string['ladderparticipation_help'] = 'Définit si les utilisateurs participent automatiquement ou doivent rejoindre manuellement le classement.  
Trois modes existent : participation automatique sans retrait, automatique avec retrait possible, ou sur inscription volontaire.';
$string['ladderparticipationforced'] = 'Automatique, sans désinscription';
$string['ladderparticipationoptin'] = 'Optionnelle (inscription volontaire)';
$string['ladderparticipationoptout'] = 'Automatique, retrait possible';
$string['ladderparticipationreset'] = 'Réinitialiser la participation de tout le monde';
$string['levels'] = 'Niveaux';
$string['level'] = 'Niveau';
$string['levelbadge'] = 'Badge de niveau';
$string['levelbadges'] = 'Badges de niveau';
$string['levelbadges_help'] = 'Importer des images (ex. 1.png, 2.jpg…) pour remplacer l’apparence des niveaux.  
Taille conseillée : 100 × 100 px.';
$string['leveldescriptiondesc'] = 'Courte description du niveau affichée aux étudiants.';
$string['levelpointsstart'] = 'Début';
$string['levelpointslength'] = 'Longueur';
$string['levelsappearance'] = 'Apparence des niveaux';
$string['levelssaved'] = 'Les niveaux ont été enregistrés.';
$string['levelup'] = 'Niveau supérieur !';
$string['levelupplus'] = 'Level Up XP+';
$string['levelx'] = 'Niveau #{$a}';
$string['limitparticipants'] = 'Limiter les participants';
$string['limitparticipants_help'] = 'Contrôle les participants visibles dans le classement (voisins, tous, etc.).';
$string['logging'] = 'Journalisation';
$string['manually'] = 'Manuellement';
$string['maxactionspertime'] = 'Actions max. dans un intervalle';
$string['maxactionspertime_help'] = 'Nombre maximum d’actions comptabilisées pour les points pendant la période donnée.  
Les suivantes seront ignorées.';
$string['menu'] = 'Menu';
$string['messageprovider:adminnotice'] = 'Notification administrateur';
$string['missing'] = 'Manquant';
$string['movecondition'] = 'Déplacer la condition';
$string['moverule'] = 'Déplacer la règle';
$string['name'] = 'Nom';
$string['namecontains'] = 'Contient "{$a}"';
$string['navbardisplay'] = 'Afficher dans la barre de navigation';
$string['navbardisplay_desc'] = 'Affiche le niveau de l’utilisateur dans la barre supérieure (fonctionne selon le thème).';
$string['navcompletionrules'] = 'Achèvement';
$string['naveventrules'] = 'Événements';
$string['navgraderules'] = 'Notes';
$string['navimport'] = 'Import';
$string['navinfos'] = 'Infos';
$string['navladder'] = 'Classement';
$string['navlevels'] = 'Niveaux';
$string['navlog'] = 'Journal';
$string['navpoints'] = 'Points';
$string['navreport'] = 'Rapport';
$string['navsettings'] = 'Paramètres';
$string['navvisuals'] = 'Apparence';
$string['newversioninstallednotice'] = 'Une nouvelle version est installée ! Consultez les [notes de version]({$a->releasenotesurl}).';
$string['nextlevelin'] = 'prochain niveau dans';
$string['noconditionsyet'] = 'Aucune condition pour le moment !';
$string['nodescription'] = 'Aucune description';
$string['noissuesidentified'] = 'Aucun problème détecté';
$string['nologsrecordedyet'] = 'Aucun journal n’a encore été enregistré.';
$string['noname'] = 'Sans nom';
$string['noneareavailable'] = 'Aucun disponible.';
$string['notecompatibilityissues'] = 'Attention : problèmes de compatibilité listés ci-dessous.';
$string['notesomesettingslocked'] = 'Certains paramètres peuvent être verrouillés par l’administrateur.';
$string['nothingmatchesfilter'] = 'Aucun résultat pour ce filtre.';
$string['notparticipating'] = 'Ne participe pas';
$string['notranked'] = 'Non classé';
$string['numberoflevels'] = 'Nombre de niveaux';
$string['onlyparticipantscanaccessranking'] = 'Seuls les participants peuvent voir le classement.';
$string['outofsync'] = 'Incompatibilité des modules XP';
$string['pagecurrentvisibletoviewers'] = 'Cette page est actuellement visible par les étudiants.';
$string['pagecurrentnotvisibletoviewers'] = 'Cette page n’est pas visible des étudiants.';
$string['participant'] = 'Participant';
$string['participants'] = 'Participants';
$string['participatesinleaderboard'] = 'Participe au classement';
$string['participatesnotinleaderboard'] = 'Ne participe pas au classement';
$string['participating'] = 'Participant';
$string['perpagecolon'] = 'Par page :';
$string['pluginname'] = 'Level Up XP';
$string['pointsperlevel'] = 'Points par niveau';
$string['pointsrequired'] = 'Points requis';
$string['popupnotificationmessage'] = 'Message de notification';
$string['popupnotificationmessagedesc'] = 'Message facultatif affiché dans la fenêtre de félicitations.';
$string['progress'] = 'Progression';
$string['progressbar'] = 'Barre de progression';
$string['rank'] = 'Rang';
$string['ranking'] = 'Classement';
$string['reallyresetdata'] = 'Réinitialiser les points et niveaux de tout le monde dans ce cours ? Action irréversible.';
$string['recentrewards'] = 'Dernières récompenses';
$string['remaining'] = 'restant';
$string['removefilter'] = 'Retirer le filtre';
$string['reportisempty'] = 'Le rapport est vide ; aucun étudiant n’a encore gagné de points.';
$string['resetcoursedata'] = 'Réinitialiser les données du cours';
$string['resettodefaults'] = 'Réinitialiser aux valeurs par défaut';
$string['resultsfilteredforn'] = 'Résultats filtrés pour {$a}.';
$string['reward'] = 'Récompense';
$string['rule'] = 'Règle';
$string['rule:contains'] = 'contient';
$string['rule:eq'] = 'est égal à';
$string['rule:gt'] = 'est supérieur à';
$string['rule:lt'] = 'est inférieur à';
$string['ruleset'] = 'Ensemble de conditions';
$string['ruleset:all'] = 'TOUTES les conditions sont vraies';
$string['ruleset:any'] = 'AU MOINS une condition est vraie';
$string['ruleset:none'] = 'AUCUNE condition n’est vraie';
$string['rulesetinfo'] = 'Combine plusieurs conditions en une seule.';
$string['ruletypecmcompletion'] = 'Achèvement d’activité';
$string['ruletypecoursecompletion'] = 'Achèvement de cours';
$string['ruletypesectioncompletion'] = 'Achèvement de section';
$string['rulesscope'] = 'Portée des règles';
$string['rulesscope_help'] = 'Détermine où s’appliquent les règles : site entier ou cours spécifique.';
$string['searchandselectcourse'] = 'Rechercher et sélectionner un cours';
$string['selectcourse'] = 'Sélectionner un cours';
$string['send'] = 'Envoyer';
$string['setpoints'] = 'Définir les points';
$string['shortcode:xppoints'] = 'Afficher un nombre de points formaté en XP.';
$string['sitewide'] = 'À l’échelle du site';
$string['someoneelse'] = 'Quelqu’un d’autre';
$string['somethinghappened'] = 'Quelque chose s’est passé';
$string['taskadminnotices'] = 'Notifications administrateur';
$string['teamleaderboard'] = 'Classement par équipes';
$string['teams'] = 'Équipes';
$string['thankyou'] = 'Merci !';
$string['timebetweensameactions'] = 'Délai entre actions identiques';
$string['timeformaxactions'] = 'Fenêtre temporelle pour actions max.';
$string['tinytimenow'] = 'maintenant';
$string['tinytimeseconds'] = '{$a}s';
$string['tinytimeminutes'] = '{$a} min';
$string['tinytimehours'] = '{$a} h';
$string['tinytimedays'] = '{$a} j';
$string['tinytimeweeks'] = '{$a} sem';
$string['tryme'] = 'Essaye-moi';
$string['unavailable'] = 'Indisponible';
$string['upgradingplugins'] = 'Mise à jour des plugins';
$string['xp'] = 'Points d’expérience';
$string['youleveledupexcl'] = 'Niveau supérieur !';
$string['youreachedlevel'] = 'Vous avez atteint le niveau :';
$string['youreachedlevela'] = 'Vous avez atteint le niveau {$a} !';
$string['privacy:path:addon'] = 'Extension';
$string['privacy:path:level'] = 'Niveau';
$string['privacy:path:logs'] = 'Journaux';
$string['privacy:metadata:log'] = 'Stocke le journal des événements';
$string['privacy:metadata:log:eventname'] = 'Nom de l’événement';
$string['privacy:metadata:log:time'] = 'Date à laquelle l’événement s’est produit';
$string['privacy:metadata:log:userid'] = 'Utilisateur ayant gagné les points';
$string['privacy:metadata:log:xp'] = 'Points attribués pour l’événement';
$string['privacy:metadata:prefintro'] = 'Enregistre si l’utilisateur a fermé le message d’introduction du bloc';
$string['privacy:metadata:preflevelup'] = 'Enregistre si l’utilisateur doit voir la notification de niveau atteint';
$string['privacy:metadata:prefnotices'] = 'Enregistre si l’utilisateur a fermé l’avis de support';
$string['privacy:metadata:prefseenpromo'] = 'Enregistre quand l’utilisateur a vu la page promotionnelle';
$string['privacy:metadata:prefladderpagesize'] = 'Taille de page préférée de l’utilisateur pour le classement';
$string['privacy:metadata:xp'] = 'Stocke les points et niveaux des utilisateurs';
$string['privacy:metadata:xp:xp'] = 'Points de l’utilisateur';
$string['privacy:metadata:xp:userid'] = 'Utilisateur';
$string['privacy:metadata:xp:lvl'] = 'Niveau de l’utilisateur';
$string['progressbar'] = 'Barre de progression';
$string['property:action'] = 'Action de l’événement';
$string['property:component'] = 'Composant de l’événement';
$string['property:crud'] = 'CRUD de l’événement';
$string['property:eventname'] = 'Nom de l’événement';
$string['property:target'] = 'Cible de l’événement';
$string['promogetnow'] = 'Obtenir XP+ dès maintenant !';
$string['promointro'] = 'Devenez maître du jeu ! Débloquez des fonctionnalités supplémentaires et poussez la gamification plus loin avec Level Up XP+ !';
$string['promointroinstalled'] = 'L’extension _Level Up XP+_ est installée et toutes ses fonctionnalités sont activées.';
$string['provisionstates'] = 'Création automatique d’utilisateurs';
$string['provisionstates_desc'] = 'Par défaut, les utilisateurs n’apparaissent dans le classement qu’après avoir été détectés par XP.  
La création automatique ajoute les utilisateurs manquants selon leur rôle, via une tâche planifiée quotidienne.';
$string['questpromonotice'] = 'Passez au niveau supérieur de la gamification : découvrez [Level Up Quest]({$a->questurl}).';
$string['quickeditpoints'] = 'Édition rapide des points';
$string['ranked'] = 'Classé';
$string['ranking_help'] = 'Le rang est la position absolue de l’utilisateur dans le classement. Le rang relatif indique l’écart de points avec ses voisins.';
$string['reallydeleteuserstate'] = 'Supprimer un utilisateur ne sert qu’à le retirer du classement. Pour réinitialiser ses points, mettez-les simplement à 0.';
$string['reallydeleteuserstateandlogs'] = 'Supprimer un utilisateur le retire du classement et efface tous ses journaux associés.  
Cela peut lui permettre de regagner des points sur des actions déjà effectuées. Pour un simple reset, préférez fixer les points à 0.';
$string['reallyresetallcoursessettingstodefaults'] = 'Réinitialiser TOUS les cours aux réglages par défaut ? Action irréversible.';
$string['reallyresetallcoursestodefaults'] = 'Réinitialiser les règles de TOUS les cours aux règles par défaut ? Action irréversible.';
$string['reallyresetcourserulestodefaults'] = 'Réinitialiser les règles du cours aux règles par défaut ? Action irréversible.';
$string['reallyresetallcourselevelstodefaults'] = 'Réinitialiser les niveaux de TOUS les cours aux niveaux par défaut ?';
$string['reallyresetcourselevelstodefaults'] = 'Réinitialiser les niveaux de ce cours aux valeurs par défaut ?';
$string['reallyresetallcoursevisualstodefaults'] = 'Réinitialiser l’apparence des niveaux de TOUS les cours ?';
$string['reallyresetcoursevisualstodefaults'] = 'Réinitialiser l’apparence des niveaux de ce cours ?';
$string['resetallcoursestodefaults'] = 'Réinitialiser tous les cours';
$string['resetcourses'] = 'Réinitialiser les cours';
$string['resetgroupdata'] = 'Réinitialiser les données du groupe';
$string['reverttopluginsdefaults'] = 'Revenir aux valeurs par défaut du plugin';
$string['rulefilterany'] = 'Tout';
$string['rulefilteranydesc'] = 'Cette condition correspond à tout.';
$string['rulefilternone'] = 'Rien';
$string['rulefiltersection'] = 'Section spécifique';
$string['rulefilterthiscourse'] = 'Ce cours';
$string['ruleproperty'] = 'Propriété de l’événement';
$string['rulesetinfo'] = 'Combiner plusieurs conditions en une seule.';
$string['ruletypecoursecompletiondesc'] = 'Attribuer des points lorsqu’un cours est marqué comme terminé.';
$string['ruletypecmcompletiondesc'] = 'Attribuer des points lorsqu’une activité est terminée.';
$string['rulesscope_help'] = 'La portée des règles détermine quand elles s’appliquent : site entier ou cours spécifique.';
$string['searchandselectmodule'] = 'Rechercher et sélectionner une activité ou ressource';
$string['shortcode:xplevelname'] = 'Afficher le nom du niveau.';
$string['shortcode:xplevelname_help'] = 'Par défaut, affiche le nom du niveau actuel de l’utilisateur.  
Vous pouvez préciser un numéro de niveau avec `level=5`.';
$string['shortcode:xpprogressbar'] = 'Barre de progression de l’utilisateur vers le niveau suivant.';
$string['shortcodeinactiveleaderboarddisabled'] = 'Le classement est désactivé ; ce shortcode sera inactif. Activez-le dans les paramètres du classement.';
$string['shortcodexpladderembedintro'] = 'Avec ce shortcode, le classement peut être intégré n’importe où sur le site. Voir la [documentation](https://docs.levelup.plus/xp/docs/how-to/use-shortcodes).';
$string['somefeaturesrequireotherplugins'] = 'Certaines fonctionnalités nécessitent l’installation de plugins supplémentaires.';
$string['taskcollectionloggerpurge'] = 'Purger les journaux collectés';
$string['taskstateprovisioner'] = 'Provisionneur d’état';
$string['taskusagereport'] = 'Rapport d’utilisation';
$string['total'] = 'Total';
$string['timebetweensameactions_help'] = 'Délai minimal avant qu’une action identique soit à nouveau comptée.  
Deux lectures du même message de forum, par exemple, ne sont pas comptées deux fois.';
$string['timeformaxactions_help'] = 'Durée (en secondes) pendant laquelle l’utilisateur ne doit pas dépasser un certain nombre d’actions.';
$string['unlockfeaturewithxpplus'] = 'Débloquez cette fonctionnalité avec XP+. <a href="{$a}">En savoir plus</a>';
$string['unstableversioninstalled'] = 'Version instable installée';
$string['userladderparticipation'] = 'Participation au classement';
$string['userladderparticipation_help'] = 'Indique si l’utilisateur participe actuellement au classement. N’affecte pas le classement des équipes.';
$string['userladderparticipationlocked'] = 'Participation verrouillée jusqu’au';
$string['userladderparticipationlocked_help'] = 'Date à partir de laquelle l’utilisateur pourra modifier sa participation.';
$string['value'] = 'Valeur';
$string['visualsintro'] = 'Personnalisez l’apparence des niveaux et la signification des points.';
$string['wherearexpused'] = 'Où les points sont-ils utilisés ?';
$string['wherearexpused_desc'] = 'Détermine si les points gagnés s’appliquent par cours ou à l’ensemble du site.';
$string['updateandpreview'] = 'Mettre à jour et prévisualiser';
$string['urlaccessdeprecated'] = 'L’accès via cette URL est obsolète ; mettez vos liens à jour.';
$string['usagereport'] = 'Partager le rapport d’utilisation';
$string['usagereport_desc'] = 'Partage anonymement des statistiques d’utilisation du plugin avec ses développeurs. Aide à améliorer Level Up XP.';
$string['usealgo'] = 'Utiliser l’algorithme';
$string['usecustomlevelbadges'] = 'Utiliser des badges personnalisés';
$string['usecustomlevelbadges_help'] = 'Si activé, vous devez fournir une image pour chaque niveau.';
$string['unknownactivitya'] = 'Activité inconnue ({$a})';
$string['unknownbadgea'] = 'Badge inconnu ({$a})';
$string['unknownconditiona'] = 'Condition inconnue ({$a})';
$string['unknowneventa'] = 'Événement inconnu ({$a})';
$string['unknowntypea'] = 'Type inconnu ({$a})';
$string['unknownsectiona'] = 'Section inconnue ({$a})';
$string['viewas'] = 'Voir en tant que';
$string['viewlogs'] = 'Voir les journaux';
$string['when'] = 'Quand';
$string['whoops'] = 'Oups !';
$string['xp:addinstance'] = 'Ajouter un nouveau bloc';
$string['xp:earnxp'] = 'Gagner des points';
$string['xp:manage'] = 'Gérer tous les aspects des points d’expérience';
$string['xp:myaddinstance'] = 'Ajouter le bloc à mon tableau de bord';
$string['xp:view'] = 'Afficher le bloc et ses pages associées';
$string['xp:viewlogs'] = 'Voir les journaux';
$string['xp:viewreport'] = 'Voir le rapport';
$string['xpplusrequired'] = 'XP+ requis';
$string['xpgaindisabled'] = 'Gain de points désactivé';
$string['yourmessage'] = 'Votre message';
$string['yourownrules'] = 'Vos propres règles';

// --- Chaînes dépréciées (anciennes versions, conservées pour compatibilité) ---
$string['enablelogging'] = 'Activer la journalisation';
$string['levelswillbereset'] = 'Attention ! Enregistrer ce formulaire recalculera les niveaux de tous les utilisateurs !';
$string['viewtheladder'] = 'Voir le classement';
$string['xp'] = 'Points d’expérience';
$string['xprequired'] = 'XP requis';
$string['for1day'] = 'Pour 1 jour';
$string['for1week'] = 'Pour 1 semaine';
$string['for1month'] = 'Pour 1 mois';
$string['for3days'] = 'Pour 3 jours';
$string['xptogo'] = '[[{$a}]] restants';
$string['basexp'] = 'Base de l’algorithme';
$string['coursevisuals'] = 'Visuels du cours';
$string['levelbadgesformhelp'] = 'Nommez les fichiers [niveau].[extension], ex. : 1.png, 2.jpg. Taille recommandée : 100×100 px.';
$string['levelcount'] = 'Nombre de niveaux';
$string['leveldesc'] = 'Description du niveau';
$string['leveldesc_help'] = 'Courte description du niveau, affichée sur la page d’information à côté du niveau.';
$string['levelname'] = 'Nom du niveau';
$string['levelname_help'] = 'Nom affiché à la place de “Niveau #1”, “Niveau #2”, etc.';
$string['usingalgo'] = 'Utilise l’algorithme';
$string['valuessaved'] = 'Les valeurs ont été enregistrées avec succès.';
$string['forthewholesite'] = 'Pour l’ensemble du site';
$string['addinstructions'] = 'Ajouter des informations';
$string['courserules'] = 'Règles du cours';
$string['defaultrulesformhelp'] = 'Règles par défaut fournies par le plugin. Vos règles personnelles les remplacent.';
$string['editinstructions'] = 'Modifier les informations';
$string['grid'] = 'Grille';
$string['list'] = 'Liste';
$string['navrules'] = 'Règles';
$string['resetcourserulestodefaults'] = 'Réinitialiser les règles du cours';
$string['resetlevelstodefaults'] = 'Réinitialiser les niveaux';
$string['resetvisualstodefaults'] = 'Réinitialiser l’apparence';
$string['questreleasenotice'] = 'Passez à la vitesse supérieure de la gamification avec **Level Up Quest** ! Transformez vos cours en **aventures** pleines de **réengagement** et de **récompenses** 🎉 ! Découvrez [le site de Quest]({$a->questurl}) et notre [article de lancement]({$a->questblogurl}).';

$string['addrulesformhelp'] = 'La dernière colonne définit le nombre de points d’expérience attribués lorsque le critère est rempli.';
$string['changelevelformhelp'] = 'Si vous changez le nombre de niveaux, les badges de niveau personnalisés seront temporairement désactivés pour éviter des niveaux sans badge. Après avoir enregistré ce formulaire, allez sur la page « Apparence » pour réactiver les badges personnalisés.';
$string['envcheckaddonincompatibilitymessage'] = 'Le plugin Level Up XP+ (local_xp) est incompatible avec Level Up XP (block_xp). XP+ sera donc désactivé. Pour éviter cela, mettez à jour les deux plugins. Plus d’informations : https://docs.levelup.plus/xp/docs/compatibility.';
$string['errorcontextcoursemismatchforwholesite'] = 'L’URL de cette page <em>Level Up XP</em> ne correspond pas à la configuration actuelle du plugin. Votre configuration déclare « À l’échelle du site », alors que cette page attend « Par cours ». Veuillez <a href="{$a->nexturl}">cliquer ici</a> pour accéder à la bonne page. Recherchez le paramètre d’administration « block_xp_context » si vous souhaitez modifier votre configuration.';
$string['errorcontextcoursemismatchpercourse'] = 'L’URL de cette page <em>Level Up XP</em> ne correspond pas à la configuration actuelle du plugin. Votre configuration déclare « Par cours », mais cette page attend « À l’échelle du site ». Cela provient probablement d’un <em>bloc</em> ajouté au tableau de bord ou à la page d’accueil dans une configuration différente. Vous devriez retirer ce bloc de ces pages et ne l’utiliser qu’à l’intérieur des cours.';
$string['errorformvalues'] = 'Certaines valeurs du formulaire posent problème, veuillez les corriger.';
$string['errornotalllevelsbadgesprovided'] = 'Tous les badges de niveau n’ont pas été fournis. Manquants : {$a}';
$string['errorxprequiredlowerthanpreviouslevel'] = 'Les points requis sont inférieurs ou égaux à ceux du niveau précédent.';
$string['eventproperty'] = 'Propriété de l’événement';
$string['eventtime'] = 'Date/heure de l’événement';
$string['filterellipsis'] = 'Filtrer…';
$string['give'] = 'donner';
$string['hasbadgeaward'] = 'Badge à attribuer défini';
$string['hasdescription'] = 'Description définie';
$string['hasname'] = 'Nom défini';
$string['hasnobadgeaward'] = 'Aucun badge à attribuer';
$string['hasnodescription'] = 'Aucune description';
$string['hasnoname'] = 'Aucun nom';
$string['hasnopopupmessage'] = 'Aucun message contextuel';
$string['haspopupmessage'] = 'Message contextuel défini';
$string['incourses'] = 'Dans les cours';
$string['ineffective'] = 'Inefficace';
$string['installed'] = 'Installé';
$string['invalidxp'] = 'Valeur de points invalide';
$string['join'] = 'Rejoindre';
$string['joinleadeboardconfirmnote'] = 'Fantastique, nous sommes ravis de vous compter parmi nous !

Veuillez noter qu’après avoir rejoint le classement, un délai d’attente est appliqué avant de pouvoir le quitter si vous changez d’avis.';
$string['joinleadeboardlockednote'] = 'Vous ne pouvez pas rejoindre le classement.';
$string['ladderadditionalcols_help'] = 'Ce réglage détermine quelles colonnes supplémentaires s’affichent dans le classement. Maintenez la touche CTRL (ou CMD) en cliquant pour sélectionner plusieurs colonnes, ou pour en désélectionner.';
$string['ladderiso'] = 'Isoler les participants';
$string['ladderiso_help'] = 'Crée des classements séparés pour différents groupes de personnes.

- Par défaut (mode groupes) : suit le mode de groupes du cours pour créer un classement par groupe.
- Cohortes : seules les personnes d’une même cohorte apparaissent dans le classement d’un utilisateur.

[En savoir plus](https://docs.levelup.plus/xp/docs/leaderboard-isolation)';
$string['ladderisocohorts'] = 'Cohortes';
$string['ladderisodefault'] = 'Par défaut (mode groupes)';
$string['ladderparticipationreset_help'] = 'Si coché, l’état de participation de tous les utilisateurs sera effacé ; chacun devra à nouveau s’inscrire ou se désinscrire.';
$string['laddersettingsmovednotice'] = 'Les paramètres du classement ont été déplacés vers la [page du classement]({$a->url}).';
$string['learnmore'] = 'En savoir plus';
$string['leave'] = 'Quitter';
$string['leaveleadeboardconfirmnote'] = 'Voulez-vous vraiment quitter le classement ?

En quittant, vous perdez l’accès aux rangs, mais vous pourrez le rejoindre de nouveau plus tard si vous changez d’avis.';
$string['leaveleadeboardlockednote'] = 'Vous ne pouvez pas quitter le classement.';
$string['leaveleadeboardlockeduntilnote'] = 'Vous ne pouvez pas quitter le classement avant le {$a}.';
$string['leaveleaderboard'] = 'Quitter le classement';
$string['levelupoptionsunavailableforlevelone'] = 'Les options liées à l’atteinte du niveau ne sont pas disponibles pour le premier niveau.';
$string['likenotice'] = 'Vous appréciez Level Up XP ? Prenez un instant pour <a href="{$a->moodleorg}" target="_blank">l’ajouter à vos favoris</a> sur Moodle.org.';
$string['maxlevelexcl'] = 'niveau max !';
$string['nameequalsto'] = 'Est égal à « {$a} »';
$string['navdrops'] = 'Drops';
$string['navlevelssetup'] = 'Configuration';
$string['navpromo'] = 'XP+';
$string['noconditionsyetintro'] = 'Commencez par ajouter une condition.';
$string['occasionally'] = 'Occasionnellement';
$string['outofsyncexcessive'] = 'Décalage excessif';
$string['outofsyncexcessiveinfo'] = 'XP+ est beaucoup plus ancien que XP, ce qui peut entraîner des problèmes. À l’avenir, XP+ se désactivera automatiquement dans ce cas.';
$string['outofsyncinfo'] = 'Les plugins XP sont incompatibles entre eux, ce qui peut provoquer des problèmes. À l’avenir, XP+ se désactivera automatiquement. La version attendue de Level Up XP+ (local_xp) est {$a->localxpversion}.';
$string['pagesettings'] = 'Paramètres de la page';
$string['participatetolevelup'] = 'Participez au cours pour gagner des points d’expérience et monter de niveau !';
$string['pickaconditiontype'] = 'Choisir un type de condition';
$string['pluginavailabilityxpdesc'] = 'Ce plugin permet aux enseignants de restreindre l’accès aux activités selon le niveau des étudiants.';
$string['pluginenrolxpdesc'] = 'Ce plugin permet des inscriptions automatiques aux cours en fonction du niveau d’un étudiant dans un autre cours.';
$string['pluginshortcodesdesc'] = 'Ce plugin permet d’enrichir le contenu avec des éléments liés à XP (points, niveau, classement, …) et d’afficher/masquer du contenu selon le niveau de l’étudiant.';
$string['pluginshortcodesrequiredtousefeature'] = 'Le plugin [Shortcodes](https://docs.levelup.plus/xp/docs/getting-started/installation/recommended-plugins) doit être installé et activé pour utiliser cette fonctionnalité.';
$string['pluginsoutofsync'] = '__Incompatibilité des plugins XP !__

Des problèmes de compatibilité existent entre Level Up XP et Level Up XP+. À l’avenir, XP+ se désactivera automatiquement s’il n’est pas compatible. Pour éviter cela, contactez l’administrateur de votre site. [En savoir plus]({$a->url})';
$string['pluginxmaybeincompatible'] = 'Cette version de {$a->name} ({$a->component}) peut être incompatible avec Moodle {$a->version}.';
$string['pointsintimelinker'] = 'par';
$string['pointstoaward'] = 'Points à attribuer';
$string['pointstoaward_help'] = 'Nombre de points attribués lorsque la condition est remplie.';
$string['potentialmoodleincompatibility'] = 'Incompatibilité potentielle avec Moodle';
$string['previewpopupnotification'] = 'Prévisualiser la notification';
$string['promocheatguard'] = 'Cet anti-triche n’est pas prévu pour de longues périodes. Pensez à passer à <em>Level Up XP+</em> pour débloquer des fenêtres plus longues et d’autres fonctionnalités. <a href="{$a->url}">En savoir plus</a>.';
$string['promocontactintro'] = 'Contactez-nous pour plus d’informations. Nous répondons rapidement 😉';
$string['promocontactus'] = 'Nous contacter';
$string['promoemailusat'] = 'Écrivez-nous à _levelup@branchup.tech_.';
$string['promoerrorsendingemail'] = 'Aïe ! Message non envoyé… écrivez-nous directement à : {$a}. Merci !';
$string['promoifpreferemailusat'] = 'Chut ! Si vous préférez, écrivez-nous directement à _{$a}_.';
$string['promorulesdidyouknow'] = 'Le saviez-vous ? Avec <em>Level Up XP+</em>, les étudiants peuvent recevoir des points pour <em>l’achèvement des cours</em> et des <em>activités</em>, ou encore selon leurs <em>notes</em>. <a href="{$a->url}">Découvrez-en plus</a>.';
$string['promoyourmessagewassent'] = 'Merci, votre message a été envoyé. Nous revenons vers vous très vite.';
$string['reallyresetgroupdata'] = 'Réinitialiser vraiment les niveaux et points de tous les membres de ce groupe ?';
$string['reallyreverttopluginsdefaults'] = 'Réinitialiser vraiment les règles par défaut aux valeurs proposées par le plugin ? Action irréversible.';
$string['recommended'] = 'Recommandé';
$string['recommendedplugins'] = 'Plugins recommandés';
$string['releasenotes'] = 'Notes de version';
$string['reportisemptyenrolstudents'] = 'Le rapport est vide ; des étudiants ont-ils été inscrits à ce cours ?';
$string['requires'] = 'Requiert';
$string['resetallcoursessettingstodefaults'] = 'Suivez ce lien pour [réinitialiser tous les cours aux paramètres par défaut]({$a->url}). Si vous avez fait des modifications, enregistrez-les d’abord. Cela écrasera les paramètres de tous les cours et est irréversible. Les niveaux, l’apparence et les règles par défaut ne sont pas affectés ; pour les réinitialiser, consultez leurs pages d’administration respectives.';
$string['resetallcoursestodefaultsintro'] = 'Cliquez sur le bouton ci-dessous pour réinitialiser tous les cours aux valeurs par défaut ci-dessus.';
$string['resetladderparticiptionofeveryone'] = 'Réinitialiser la participation de tout le monde';
$string['reverttopluginsdefaultsintro'] = 'Utilisez le bouton ci-dessous pour revenir aux valeurs par défaut du plugin. Cela n’affecte pas les règles des cours existants.';
$string['rule:eqs'] = 'est strictement égal à';
$string['rule:gte'] = 'est supérieur ou égal à';
$string['rule:lte'] = 'est inférieur ou égal à';
$string['rule:regex'] = 'correspond à l’expression régulière';
$string['ruleadded'] = 'La condition a été ajoutée.';
$string['rulecm'] = 'Activité ou ressource';
$string['rulecm_help'] = 'Cette condition est remplie lorsque l’événement se produit dans l’activité ou la ressource spécifiée.';
$string['rulecmdesc'] = 'L’activité ou la ressource est « {$a->contextname} ».';
$string['rulecmdescwithcourse'] = 'L’activité ou la ressource est « {$a->contextname} » dans « {$a->coursename} ».';
$string['rulecminfo'] = 'Cette condition exige que l’action ait lieu dans une activité ou ressource précise.';
$string['ruleevent'] = 'Événement spécifique';
$string['ruleeventdesc'] = 'L’événement est « {$a->eventname} »';
$string['ruleeventinfo'] = 'Choisissez, dans une liste d’événements, l’action que les utilisateurs doivent réaliser.';
$string['rulefilteranycm'] = 'Toute activité';
$string['rulefilteranycmdesc'] = 'Cette condition correspond à n’importe quelle activité.';
$string['rulefilteranycourse'] = 'N’importe quel cours';
$string['rulefilteranycoursedesc'] = 'Cette condition correspond à n’importe quel cours.';
$string['rulefilteranysection'] = 'N’importe quelle section';
$string['rulefilteranysectiondesc'] = 'Cette condition correspond à n’importe quelle section.';
$string['rulefiltercm'] = 'Activité spécifique';
$string['rulefiltercmdesc'] = 'Cibler une activité ou une ressource précise du cours.';
$string['rulefiltercmname'] = 'Nom de l’activité';
$string['rulefiltercmnamedesc'] = 'Condition basée sur le nom de l’activité.';
$string['rulefiltersectiondesc'] = 'Cibler une section précise du cours.';
$string['rulefilterthiscoursedesc'] = 'Cibler le cours actuel.';
$string['rulepropertydesc'] = 'La propriété « {$a->property} » {$a->compare} « {$a->value} ».';
$string['rulepropertyinfo'] = 'Condition destinée aux utilisateurs avancés ayant une compréhension technique des événements et de leurs propriétés.';
$string['rulesformhelp'] = '<p>Ce plugin utilise les événements pour attribuer des points aux actions réalisées par les étudiants. Utilisez le formulaire ci-dessous pour ajouter vos propres règles et consulter celles par défaut.</p>
<p>Il est conseillé de consulter le <a href="{$a->log}">journal</a> du plugin pour identifier les événements déclenchés lors de vos actions dans le cours, et de lire la documentation : <a href="{$a->list}">liste des événements</a>, <a href="{$a->doc}">documentation développeur</a>.</p>
<p>Notez que le plugin ignore toujours :
<ul>
    <li>les actions des administrateurs, invités ou utilisateurs non connectés ;</li>
    <li>les actions des utilisateurs n’ayant pas la capacité <em>block/xp:earnxp</em> ;</li>
    <li>les actions répétées dans un court intervalle, pour éviter la triche ;</li>
    <li>les événements marqués <em>anonymes</em> (p. ex. dans un Feedback anonyme) ;</li>
    <li>et les événements dont le niveau pédagogique n’est pas <em>Participating</em>.</li>
</ul>
</p>';
$string['ruletypesectioncompletiondesc'] = 'Attribuer des points lorsqu’une section du cours est marquée comme terminée.';
$string['settingsoutdatedxppnotice'] = 'Si vous voyez des paramètres ci-dessous, cela signifie qu’une version obsolète de XP+ est installée. Demandez à votre administrateur d’installer les dernières versions.';
$string['shortcode:xpbadge'] = 'Badge correspondant au niveau actuel de l’utilisateur.';
$string['shortcode:xpiflevel'] = 'Afficher le contenu lorsque le niveau de l’utilisateur correspond.';
$string['shortcode:xpiflevel_help'] = '
Reportez-vous aux exemples ci-dessous pour formater ce shortcode. Lorsqu’un niveau est spécifié explicitement, le contenu s’affiche indépendamment des autres règles.
Les conditions « supérieur à » et « inférieur à » doivent toutes correspondre pour que le contenu s’affiche. Attention, certaines combinaisons peuvent rendre le contenu impossible à afficher !
Les enseignants et les utilisateurs avec des capacités d’édition voient toujours tout.

```
[xpiflevel 1 3 5]
Affiché si le niveau de l’utilisateur est exactement 1, 3 ou 5.
[/xpiflevel]

[xpiflevel >3]
Affiché si le niveau de l’utilisateur est supérieur à 3.
[/xpiflevel]

[xpiflevel >=3]
Affiché si le niveau de l’utilisateur est supérieur ou égal à 3.
[/xpiflevel]

[xpiflevel >=10 <20 30]
Affiché si le niveau de l’utilisateur est supérieur ou égal à 10 ET strictement inférieur à 20
OU exactement égal à 30.
[/xpiflevel]

[xpiflevel <=10 >=20]
Jamais affiché car le niveau ne peut pas être ≤ 10 ET ≥ 20 en même temps.
[/xpiflevel]
```

Note : ces shortcodes NE PEUVENT PAS être imbriqués les uns dans les autres.
';
$string['shortcode:xpladder'] = 'Afficher une portion du classement.';
$string['shortcode:xpladder_help'] = '
Par défaut, on affiche la portion du classement autour de l’utilisateur courant.

```
[xpladder]
```

Pour afficher le top 10 au lieu des voisins, utilisez le paramètre `top`. Vous pouvez préciser le nombre : `top=20`.

```
[xpladder top]
[xpladder top=15]
```

Un lien vers le classement complet est affiché automatiquement sous le tableau ; pour le masquer, ajoutez l’argument `hidelink`.

```
[xpladder hidelink]
```

Par défaut, la colonne de progression n’est pas incluse. Si elle a été sélectionnée dans les colonnes supplémentaires du classement, utilisez `withprogress` pour l’afficher.

```
[xpladder withprogress]
```

En présence de groupes, le classement essaiera d’afficher le groupe le plus pertinent.
';
$string['shortcode:xppoints_help'] = '
Par défaut, affiche le nombre de points de l’utilisateur courant. Vous pouvez fournir un nombre pour forcer la valeur.

Le style varie selon qu’il s’agit d’une valeur arbitraire ou des points de l’utilisateur. L’argument `plain` supprime tout style.

```
[xppoints]
[xppoints 500]
[xppoints 123 plain]
```
';
$string['shortcodexpteamladderembedintro'] = 'Avec le shortcode suivant, le classement peut être intégré n’importe où sur le site. Plus d’options et d’informations dans la [documentation](https://docs.levelup.plus/xp/docs/how-to/use-shortcodes).';
$string['teamleaderboard_help'] = 'Le classement par équipes classe des équipes selon le total cumulé des points de leurs membres.

Les équipes peuvent être basées sur les groupes de cours ou les cohortes. Des options existent pour compenser des tailles d’équipe différentes.

[En savoir plus](https://docs.levelup.plus/xp/docs/how-to/setup-team-leaderboard/team-leaderboard?ref=blockxp_help)';
$string['teamleaderboardintro'] = 'Le classement par équipes est un classement des équipes selon les points de leurs membres.';
$string['tinytimeolderyearformat'] = '%b %Y';
$string['tinytimewithinayearformat'] = '%b %e';
$string['unstableversioninstalledinfo'] = 'Cette version de Level Up XP (block_xp) est encore en développement et considérée instable ; utilisez une version officielle.';
$string['wewillreplyat'] = 'Nous vous répondrons à : _{$a}_.';

$string['actionrules'] = 'Règles d’action';
$string['actionrules_help'] = "Les règles d’action permettent de créer des conditions déterminant quand et combien de points sont attribués aux étudiants.

Pour chaque action, les conditions sont évaluées dans l’ordre dans lequel elles apparaissent à l’écran. Dès qu’une condition est satisfaite, ses points sont attribués et les autres conditions ne sont pas évaluées pour cette même action.

Des limites peuvent être définies pour chaque condition. Lorsqu’une condition a atteint sa limite, aucun point n’est attribué et toute l’action est ignorée.

[En savoir plus](https://docs.levelup.plus/xp/docs/action-rules?ref=blockxp_help)";
$string['actionrulesintro'] = 'Attribue des points aux étudiants pour les actions qu’ils effectuent.';
$string['addaction'] = 'Ajouter une action';
$string['addanaction'] = 'Ajouter une action';
$string['addcondition'] = 'Ajouter une condition';
$string['admindefaultactionrulesintro'] = 'Les règles d’action suivantes seront utilisées par défaut.';
$string['alreadyused'] = 'Déjà utilisé';
$string['availabilityinfonotincourse'] = 'Nécessite d’être dans le contexte d’un cours.';
$string['certificateobtained'] = 'Certificat obtenu';
$string['conditions'] = 'Conditions';
$string['defaultactionrules'] = 'Règles d’action par défaut';
$string['editlimits'] = 'Modifier les limites';
$string['eventsrulesintro'] = 'Observe les événements et attribue des points aux étudiants lorsqu’ils se produisent. Nous recommandons désormais d’utiliser les nouvelles règles « Action » et « Achèvement ».';
$string['filterbyrule'] = 'Filtrer par règle';
$string['intotal'] = 'Au total';
$string['keeplogsdesc'] = 'Durée après laquelle les journaux sont supprimés de la base de données. Les journaux jouent un rôle important : ils permettent de suivre les points attribués, d’identifier l’activité récente et bien d’autres choses. La suppression des journaux peut affecter la distribution des points au fil du temps.';
$string['limits'] = 'Limites';
$string['navactionrules'] = 'Règles d’action';
$string['noactionsyet'] = 'Aucune action pour le moment !';
$string['noactionsyetintro'] = 'Commencez par ajouter une action à observer.';
$string['nolimit'] = 'Aucune limite';
$string['notyetused'] = 'Pas encore utilisé';
$string['nperhoursmall'] = '{$a}/h';
$string['nperdaysmall'] = '{$a}/jour';
$string['nperweeksmall'] = '{$a}/sem.';
$string['npermonthsmall'] = '{$a}/mois';
$string['ntimes'] = '{$a} fois';
$string['once'] = 'Une fois';
$string['onceperactivity'] = 'Une fois par activité';
$string['onceperassignment'] = 'Une fois par devoir';
$string['onceperchapter'] = 'Une fois par chapitre';
$string['oncepercontentpiece'] = 'Une fois par contenu';
$string['oncepercourse'] = 'Une fois par cours';
$string['onceperdiscussion'] = 'Une fois par discussion';
$string['onceperforum'] = 'Une fois par forum';
$string['onceperpage'] = 'Une fois par page';
$string['onceperquiz'] = 'Une fois par quiz';
$string['overalllimit'] = 'Limite globale';
$string['overalllimitdesc'] = 'La limite globale définit combien de fois une condition peut attribuer des points.';
$string['overalllimit_help'] = "La limite globale définit combien de fois une condition peut attribuer des points.

Une fois la limite atteinte, aucun point ne sera attribué pour l’action. Utilisez la limite globale pour contrôler le nombre maximal de fois où des points peuvent être attribués pendant une période donnée.

[En savoir plus](https://docs.levelup.plus/xp/docs/action-rules/limits)";
$string['peractivity'] = 'Par activité';
$string['perassignment'] = 'Par devoir';
$string['perchapter'] = 'Par chapitre';
$string['percontentpiece'] = 'Par contenu';
$string['percourse'] = 'Par cours';
$string['perday'] = 'Par jour';
$string['perdiscussion'] = 'Par discussion';
$string['perforum'] = 'Par forum';
$string['perhour'] = 'Par heure';
$string['permonth'] = 'Par mois';
$string['perpage'] = 'Par page';
$string['perquiz'] = 'Par quiz';
$string['perweek'] = 'Par semaine';
$string['pluginnotenabled'] = 'Le plugin « {$a->name} » ({$a->component}) n’est pas activé.';
$string['pluginoutdated'] = 'Le plugin « {$a->name} » ({$a->component}) est obsolète, la version « {$a->release} » est requise.';
$string['points'] = 'Points';
$string['privacy:path:userflags'] = 'Indicateurs utilisateur';
$string['privacy:metadata:logs'] = 'Stocke le journal des points';
$string['privacy:metadata:log:reason'] = 'La raison';
$string['privacy:metadata:log:subtype'] = 'Le sous-type de la raison.';
$string['reason'] = 'Raison';
$string['reasonactivityviewed'] = 'Activité consultée';
$string['reasonassignfeedbackread'] = 'Feedback lu';
$string['reasonassignsubmitted'] = 'Devoir remis';
$string['reasonchapterread'] = 'Chapitre lu';
$string['reasondatabaseentrycreated'] = 'Entrée de base de données créée';
$string['reasondiscussioncreated'] = 'Discussion créée';
$string['reasondiscussionread'] = 'Discussion lue';
$string['reasondiscussionrepliedto'] = 'Réponse à une discussion';
$string['reasonfeedbackanswered'] = 'Feedback répondu';
$string['reasonglossaryentrypublished'] = 'Entrée de glossaire publiée';
$string['reasonlessoncontentviewed'] = 'Contenu de leçon consulté';
$string['reasonlessonendreached'] = 'Leçon terminée';
$string['reasonlessonstarted'] = 'Leçon commencée';
$string['reasonquizattemptfinished'] = 'Tentative de quiz terminée';
$string['reasonquizattemptstarted'] = 'Tentative de quiz commencée';
$string['repeatsallowed'] = 'Répétitions autorisées';
$string['repetitionlimit'] = 'Limite de répétition';
$string['repetitionlimitdesc'] = 'La limite de répétition détermine quand les utilisateurs peuvent répéter des actions similaires pour gagner à nouveau des points.';
$string['repetitionlimit_help'] = "La limite de répétition détermine si les utilisateurs peuvent répéter des actions similaires et gagner à nouveau des points.

L’objectif de cette limite est d’éviter les comportements abusifs et d’encourager une participation plus variée. Par exemple, dans un forum, vous pouvez limiter les répétitions à une fois par discussion.

La limite globale et la limite de répétition s’appliquent toutes les deux. Lorsque l’une des deux limites est atteinte, aucun point n’est attribué.

[En savoir plus](https://docs.levelup.plus/xp/docs/action-rules/limits)";
$string['repetitionlimitset'] = 'Limite de répétition définie';
$string['repetitiontimeframe'] = 'Période de répétition';
$string['requiresplugin'] = 'Nécessite le plugin « {$a->name} » ({$a->component}).';
$string['resultsfilteredforrulen'] = 'Résultats filtrés pour la règle « {$a} ».';
$string['rulefilteryalreadyusedbyaction'] = 'Cette condition est déjà utilisée par cette action et ne peut pas être ajoutée plusieurs fois.';
$string['rulefiltercmtag'] = 'Étiquette d’activité';
$string['rulefiltercmtagdesc'] = 'Cette condition correspondra si l’activité possède une étiquette spécifique.';
$string['rulefiltercmtagfield'] = 'Nom de l’étiquette';
$string['rulefiltercmtaghelp'] = 'Saisissez le nom de l’étiquette exactement comme vous le feriez lors de l’étiquetage de l’activité.';
$string['ruletypeanswerfeedback'] = 'Répondre aux questions de feedback';
$string['ruletypeanswerfeedbackdesc'] = 'Lorsque l’utilisateur répond aux questions d’une activité feedback.';
$string['ruletypecreatedatabaseentry'] = 'Créer une entrée de base de données';
$string['ruletypecreatedatabaseentrydesc'] = 'Lorsqu’un utilisateur crée une nouvelle entrée dans une activité base de données.';
$string['ruletypecreateforumdiscussion'] = 'Créer une discussion de forum';
$string['ruletypecreateforumdiscussiondesc'] = 'Lorsque l’utilisateur crée une nouvelle discussion dans une activité forum.';
$string['ruletypefinishquizattempt'] = 'Terminer une tentative de quiz';
$string['ruletypefinishquizattemptdesc'] = 'Lorsque l’utilisateur termine une tentative de quiz.';
$string['ruletypeobtaincertificate'] = 'Obtenir un certificat';
$string['ruletypeobtaincertificatedesc'] = 'Lorsque l’utilisateur reçoit un certificat via l’activité « Certificat personnalisé ».';
$string['ruletypepublishglossaryentry'] = 'Publier une entrée de glossaire';
$string['ruletypepublishglossaryentrydesc'] = 'Lorsqu’une entrée de glossaire d’un utilisateur est publiée.';
$string['ruletypereachlessonend'] = 'Atteindre la fin de la leçon';
$string['ruletypereachlessonenddesc'] = 'Lorsque l’utilisateur atteint la fin d’une activité leçon.';
$string['ruletypereadassignfeedback'] = 'Lire le feedback du devoir';
$string['ruletypereadassignfeedbackdesc'] = 'Lorsque l’utilisateur lit le feedback fourni pour sa remise de devoir.';
$string['ruletypereadchapter'] = 'Lire un chapitre';
$string['ruletypereadchapterdesc'] = 'Lorsque l’utilisateur ouvre un chapitre d’une activité livre.';
$string['ruletypereadforumdiscussion'] = 'Lire une discussion de forum';
$string['ruletypereadforumdiscussiondesc'] = 'Lorsque l’utilisateur consulte une discussion dans une activité forum.';
$string['ruletypereplyforumdiscussion'] = 'Répondre à une discussion de forum';
$string['ruletypereplyforumdiscussiondesc'] = 'Lorsque l’utilisateur publie une réponse à une discussion de forum.';
$string['ruletypesectioncompletiondesc'] = 'Lorsque toutes les activités d’une section de cours sont marquées comme terminées.';
$string['ruletypestartlesson'] = 'Commencer une leçon';
$string['ruletypestartlessondesc'] = 'Lorsque l’utilisateur commence une activité leçon.';
$string['ruletypestartquizattempt'] = 'Commencer une tentative de quiz';
$string['ruletypestartquizattemptdesc'] = 'Lorsque l’utilisateur commence une tentative dans un quiz.';
$string['ruletypesubmitassignment'] = 'Remettre un devoir';
$string['ruletypesubmitassignmentdesc'] = 'Lorsqu’un utilisateur remet un devoir.';
$string['ruletypeviewactivity'] = 'Consulter une activité';
$string['ruletypeviewactivitydesc'] = 'Lorsque l’utilisateur accède à une page dans une activité.';
$string['ruletypeviewconsumecontent'] = 'Consulter du contenu';
$string['ruletypeviewconsumecontentdesc'] = 'Lorsque l’utilisateur consulte tout type de contenu, au sens large.';
$string['ruletypeviewcourse'] = 'Consulter la page du cours';
$string['ruletypeviewcoursedesc'] = 'Lorsque l’utilisateur accède à la page du cours.';
$string['ruletypeviewlessoncontent'] = 'Consulter le contenu de la leçon';
$string['ruletypeviewlessoncontentdesc'] = 'Lorsque l’utilisateur consulte le contenu d’une page dans une activité leçon.';
$string['ruletypeviewproducecontent'] = 'Rédiger du contenu';
$string['ruletypeviewproducecontentdesc'] = 'Lorsque l’utilisateur crée tout type de contenu, au sens large.';
$string['shortcodexpteamladderembedintro'] = 'Avec le shortcode suivant, le classement peut être intégré n’importe où sur ce site. Vous trouverez plus d’options et d’informations dans la [documentation](https://docs.levelup.plus/xp/docs/leaderboard-embed).';
$string['shortcodexpladderembedintro'] = 'Avec le shortcode suivant, le classement peut être intégré n’importe où sur ce site. Vous trouverez plus d’options et d’informations dans la [documentation](https://docs.levelup.plus/xp/docs/leaderboard-embed).';
$string['timeframe'] = 'Période';
$string['timesallowed'] = 'Nombre de fois autorisé';
$string['unavailablebecause'] = 'Indisponible pour la raison suivante :';
$string['unknown'] = 'Inconnu';
$string['unlimitedrepeats'] = 'Répétitions illimitées';
$string['upgradetoaddmore'] = 'Passez à la version supérieure pour en ajouter davantage.';
$string['visitpagetoeditdefaultactionrules'] = 'Les règles d’action sont désormais la méthode recommandée pour définir les règles. Consultez [cette page]({$a}) pour personnaliser leurs valeurs par défaut.';
$string['usedefaultlimits'] = 'Utiliser les limites par défaut';
$string['xppremiumrequired'] = 'XP+ Premium requis';