<?php
// CampusFR 1.1.60a: French language pack, including new strings.
defined('MOODLE_INTERNAL') || die();

$string['additem'] = 'Shadowing vidéo';
$string['aihelper_placeholder_shadow'] = 'p. ex. Ajoute la ponctuation et corrige toutes les fautes d\'orthographe et de majuscules';
$string['enablesubtitlefetch'] = 'Activer le bouton de récupération des sous-titres';
$string['enablesubtitlefetch_details'] = 'Affiche un bouton « Récupérer les sous-titres » dans le formulaire de l\'élément, qui télécharge les sous-titres d\'une vidéo YouTube dans l\'éditeur de sous-titres. Remarque : la récupération des sous-titres ne fonctionne pas toujours et peut cesser de fonctionner à tout moment. C\'est un outil utilitaire dont Poodll ne garantit pas la disponibilité permanente.';
$string['error:badtimestamp'] = 'Les heures de début et de fin de l\'extrait doivent être au format hh:mm:ss, p. ex. 00:01:30.';
$string['error:subtitlefetchdisabled'] = 'La récupération des sous-titres est désactivée sur ce site.';
$string['error:badshadowlines'] = 'Les lignes sur lesquelles faire du shadowing doivent être * (toutes les lignes) ou une liste de numéros de ligne séparés par des virgules, p. ex. 1,4,5,6.';
$string['error:badvtt'] = 'Les sous-titres n\'ont pas pu être analysés. Saisis un WebVTT valide comportant au moins une entrée horodatée.';
$string['error:noshadowlines'] = 'Aucun des numéros de ligne sélectionnés ne correspond à une ligne de sous-titre comprise entre les heures de début et de fin de l\'extrait.';
$string['fetchvtt'] = 'Récupérer les sous-titres';
$string['fetchvtt_disabled'] = 'La récupération automatique des sous-titres est actuellement désactivée';
$string['fetchvtt_failed'] = 'Impossible de récupérer les sous-titres depuis YouTube.';
$string['fetchvtt_fetching'] = 'Récupération...';
$string['fetchvtt_invalidurl'] = 'Saisis d\'abord une URL YouTube valide ou un ID de vidéo de 11 caractères.';
$string['fetchvtt_overwrite'] = 'Cela remplacera les sous-titres actuellement dans l\'éditeur. Continuer ?';
$string['fetchvtt_overwrite_title'] = 'Remplacer les sous-titres ?';
$string['error:nocuesinclip'] = 'Aucune ligne de sous-titre ne tient entièrement entre les heures de début et de fin de l\'extrait. Ajuste les heures ou les sous-titres.';
$string['error:novideoid'] = 'Un ID de vidéo YouTube ou une URL est requis.';
$string['error:startafterend'] = 'L\'heure de fin de l\'extrait doit être postérieure à l\'heure de début.';
$string['item_desc'] = 'L\'élément Shadowing vidéo lit un extrait YouTube ligne par ligne. Les apprenants font du « shadowing » de chaque ligne de sous-titre, en parlant en même temps que la vidéo pendant que les mots sont mis en évidence.';
$string['loopcount'] = 'Nombre de shadowings par ligne';
$string['loopcount_desc'] = 'Nombre de fois que chaque ligne est rejouée pour que l\'apprenant fasse du shadowing.';
$string['loopindicator'] = 'Shadowing : {$a->current} / {$a->total}';
$string['oknext'] = 'OK / Suivant';
$string['pluginname'] = 'Shadowing vidéo';
$string['privacy:metadata'] = 'Le plugin Shadowing vidéo ne stocke aucune donnée personnelle.';
$string['retry'] = 'Réessayer';
$string['rotatedevice'] = 'Tourne ton appareil en mode portrait pour continuer.';
$string['shadow_instructions1'] = 'Regarde la vidéo. Puis fais du shadowing sur chaque ligne : écoute, et parle en même temps que la vidéo pendant sa relecture.';
$string['shadowlines'] = 'Lignes pour le shadowing';
$string['shadowpause'] = 'Pause entre les shadowings (secondes)';
$string['shadowlines_desc'] = 'Numéros des lignes de sous-titres sur lesquelles faire du shadowing, comptés à partir de 1 dans les sous-titres ci-dessous, p. ex. 1,4,5,6. Utilise * pour toutes les lignes. Les autres lignes s\'affichent quand même pendant le visionnage.';
$string['shadowvtt'] = 'Sous-titres (WebVTT)';
$string['shadowvtt_desc'] = 'Colle ou modifie les sous-titres WebVTT de l\'extrait dans la zone ci-dessous.';
$string['startshadowing'] = 'Commencer le shadowing';
$string['watchhint'] = 'Appuie sur lecture et regarde l\'extrait. Quand il est terminé, appuie sur « Commencer le shadowing ».';
$string['wordhighlight'] = 'Activer la mise en évidence mot par mot';
$string['wordhighlight_details'] = 'Met en évidence chaque mot au moment où il est prononcé, à l\'aide des horodatages par mot dans les sous-titres. Les temps par mot de YouTube ne sont pas fiables sur certaines vidéos : désactive cette option pour mettre en évidence des lignes entières. Lorsqu\'elle est désactivée, la récupération des sous-titres ne récupère pas non plus les horodatages par mot.';
$string['ytclipdetails'] = 'Extrait YouTube (ID/URL, secondes de début et de fin)';
$string['autoforward'] = 'Avance automatique';
$string['autoforward_details'] = 'Après le dernier shadowing d\'une ligne, passe tout seul à la ligne suivante au lieu d\'attendre OK / Suivant. Il n\'y a pas de bouton Réessayer dans ce mode.';
$string['paste_empty'] = 'Colle d\'abord la transcription que tu as copiée depuis YouTube.';
$string['paste_failed'] = 'Impossible de convertir la transcription.';
$string['paste_label'] = 'Transcription copiée depuis YouTube';
$string['paste_placeholder'] = '0:00
la première ligne de la transcription
0:03
la deuxième ligne de la transcription';
$string['paste_step_copy'] = 'Sélectionne toute la transcription, copie-la, puis colle-la dans la zone ci-dessous';
$string['paste_step_open_novideo'] = 'Ouvre la vidéo sur YouTube (saisis d\'abord l\'ID de la vidéo dans le formulaire pour obtenir un lien ici)';
$string['paste_step_showtranscript'] = 'Sous la vidéo, développe la description et clique sur « Afficher la transcription »';
$string['paste_step_timestamps'] = 'Dans le menu ⋮ du panneau de transcription, vérifie que les horodatages sont activés';
$string['paste_success'] = 'Lignes de sous-titres ajoutées : {$a}';
$string['paste_wordhighlightoff'] = 'La mise en évidence mot par mot a été désactivée, car une transcription collée ne contient que les temps de lignes entières. L\'extrait mettra en évidence une ligne à la fois.';
$string['pastevtt'] = 'Coller la transcription';
$string['pastevtt_convert'] = 'Convertir en sous-titres';
$string['pastevtt_title'] = 'Coller la transcription YouTube';

// CampusFR 1.1.60a — newly translated strings (FR/RU).
$string['paste_step_open_link'] = 'Ouvrir la vidéo sur YouTube dans un nouvel onglet';
