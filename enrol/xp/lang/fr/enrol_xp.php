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
 * Language file.
 *
 * @package    enrol_xp
 * @copyright  2017 Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// CampusFR custom FR/RU translations - enrol_xp 1.0.8a.

$string['adhocsyncscheduled'] = 'Les inscriptions manquantes seront synchronisées dans les prochaines minutes (lors de l\'exécution du cron).';
$string['course'] = 'Accéder au cours';
$string['invalidlevel'] = 'Le niveau doit être un nombre supérieur ou égal à 2.';
$string['levelincourse'] = 'Cours dans lequel atteindre le niveau';
$string['levelninvalid'] = '{$a} (invalide)';
$string['leveltoattain'] = 'Niveau à atteindre';
$string['messageprovider:welcomemessage'] = 'Message de bienvenue de l\'inscription Level Up XP';
$string['noteaboutexistingenrolment'] = 'Cette méthode d\'inscription est sans effet si l\'utilisateur est déjà inscrit au cours, même si son inscription a expiré, a été suspendue ou a été désactivée.';
$string['notethat'] = 'Remarque';
$string['pluginname'] = 'Inscription Level Up XP';
$string['privacy:metadata'] = 'Ce plugin ne stocke aucune information sur les utilisateurs.';
$string['syncpossiblemissing'] = 'Synchroniser et rechercher les inscriptions potentiellement manquantes.';
$string['welcomemessage'] = 'Message de bienvenue';
$string['welcomemessage_help'] = 'Message envoyé aux utilisateurs lors de leur inscription au cours. Laissez ce champ vide si vous ne souhaitez envoyer aucun message.

Les variables suivantes sont disponibles :

* [level] : le niveau qu\'ils devaient atteindre
* [fullname] : le nom complet de l\'utilisateur
* [firstname] : le prénom de l\'utilisateur

Le format pris en charge est [Markdown](https://en.wikipedia.org/wiki/Markdown).
';
$string['wholesite'] = 'Ensemble du site';
$string['xp:config'] = 'Configurer les instances d\'inscription';
$string['xp:unenrol'] = 'Désinscrire des utilisateurs';
$string['youhavebeenenrolled'] = 'Vous avez été inscrit à un nouveau cours.';
