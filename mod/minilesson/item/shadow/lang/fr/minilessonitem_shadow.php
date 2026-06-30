<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English language pack for Video Shadowing
 *
 * @package    minilessonitem_shadow
 * @category   string
 * @copyright  2026 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['additem'] = 'Shadowing vidéo';
$string['aihelper_placeholder_shadow'] = 'Ex. : Ajoute la ponctuation et corrige les fautes d’orthographe et de majuscules.';
$string['enablesubtitlefetch'] = 'Activer le bouton de récupération des sous-titres';
$string['enablesubtitlefetch_details'] = 'Affiche un bouton « Récupérer les sous-titres » dans le formulaire de l’activité, qui télécharge les sous-titres d’une vidéo YouTube dans l’éditeur de sous-titres. Remarque : cette fonctionnalité peut ne pas toujours fonctionner et peut cesser de fonctionner à tout moment. Il s’agit d’un outil utilitaire dont Poodll ne garantit pas la disponibilité permanente.';
$string['error:badtimestamp'] = 'Les heures de début et de fin de l’extrait doivent être au format hh:mm:ss, par exemple 00:01:30.';
$string['error:subtitlefetchdisabled'] = 'La récupération des sous-titres est désactivée sur ce site.';
$string['error:badshadowlines'] = 'Les lignes à répéter doivent être * (toutes les lignes) ou une liste de numéros séparés par des virgules, par exemple : 1,4,5,6.';
$string['error:badvtt'] = 'Les sous-titres n’ont pas pu être analysés. Saisissez un fichier WebVTT valide contenant au moins un segment horodaté.';
$string['error:noshadowlines'] = 'Aucun des numéros de ligne sélectionnés ne correspond à une ligne de sous-titre comprise dans les temps de début et de fin de l’extrait.';
$string['fetchvtt'] = 'Récupérer les sous-titres';
$string['fetchvtt_disabled'] = 'La récupération automatique des sous-titres est actuellement désactivée.';
$string['fetchvtt_failed'] = 'Impossible de récupérer les sous-titres depuis YouTube.';
$string['fetchvtt_fetching'] = 'Récupération en cours...';
$string['fetchvtt_invalidurl'] = 'Saisissez d’abord une URL YouTube valide ou un identifiant vidéo de 11 caractères.';
$string['fetchvtt_overwrite'] = 'Les sous-titres actuellement présents dans l’éditeur seront remplacés. Continuer ?';
$string['fetchvtt_overwrite_title'] = 'Remplacer les sous-titres ?';
$string['error:nocuesinclip'] = 'Aucune ligne de sous-titre n’est entièrement comprise entre les temps de début et de fin de l’extrait. Ajustez les temps ou les sous-titres.';
$string['error:novideoid'] = 'Un identifiant ou une URL de vidéo YouTube est requis.';
$string['error:startafterend'] = 'L’heure de fin de l’extrait doit être postérieure à l’heure de début.';
$string['item_desc'] = 'L’élément Shadowing vidéo lit un extrait YouTube ligne par ligne. Les élèves pratiquent le shadowing en écoutant chaque ligne de sous-titre puis en la répétant en même temps que la vidéo lors de sa relecture.';
$string['loopcount'] = 'Nombre de répétitions par ligne';
$string['loopcount_desc'] = 'Nombre de fois que chaque ligne est rejouée pour permettre à l’élève de la répéter.';
$string['loopindicator'] = 'Shadowing : {$a->current} / {$a->total}';
$string['oknext'] = 'OK / Suivant';
$string['pluginname'] = 'Shadowing vidéo';
$string['privacy:metadata'] = 'Le plugin Shadowing vidéo ne stocke aucune donnée personnelle.';
$string['retry'] = 'Réessayer';
$string['rotatedevice'] = 'Veuillez faire pivoter votre appareil en mode portrait pour continuer.';
$string['shadow_instructions1'] = 'Regardez la vidéo. Ensuite, répétez chaque ligne : écoutez, puis parlez en même temps que la vidéo lorsqu’elle est rejouée.';
$string['shadowlines'] = 'Lignes à répéter';
$string['shadowpause'] = 'Pause entre les répétitions (secondes)';
$string['shadowlines_desc'] = 'Numéros des lignes de sous-titres à répéter, en comptant à partir de 1 dans les sous-titres ci-dessous (par exemple : 1,4,5,6). Utilisez * pour répéter toutes les lignes. Les autres lignes restent affichées pendant le visionnage.';
$string['shadowvtt'] = 'Sous-titres (WebVTT)';
$string['shadowvtt_desc'] = 'Collez ou modifiez les sous-titres WebVTT de l’extrait dans la zone ci-dessous.';
$string['startshadowing'] = 'Commencer le shadowing';
$string['watchhint'] = 'Appuyez sur Lecture et regardez l’extrait. Lorsqu’il est terminé, cliquez sur « Commencer le shadowing ».';
$string['wordhighlight'] = 'Activer le surlignage mot par mot';
$string['wordhighlight_details'] = 'Surligne chaque mot au moment où il est prononcé, en utilisant les horodatages des mots présents dans les sous-titres. Les horodatages de YouTube peuvent être imprécis sur certaines vidéos ; désactivez cette option pour surligner les lignes entières à la place. Lorsque cette option est désactivée, la récupération des sous-titres ignore également les horodatages des mots.';
$string['ytclipdetails'] = 'Extrait YouTube (ID/URL, heure de début et heure de fin)';
