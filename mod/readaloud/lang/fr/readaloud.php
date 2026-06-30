<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English strings for readaloud
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_readaloud
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Poodll ReadAloud';
$string['modulenameplural'] = 'Poodll ReadAloud';
$string['modulename_help'] =
        'ReadAloud permet aux étudiants de s’entraîner à lire des textes à voix haute et de recevoir un retour sur leur performance. L’activité peut être entièrement évaluée automatiquement et aide les enseignants à évaluer et mieux comprendre les compétences de lecture en langue étrangère de leurs étudiants.

Le déroulement est le suivant :

1. Les étudiants ÉCOUTENT un texte préparé par l’enseignant.

2. Les étudiants S’ENTRAÎNENT à lire le texte ligne par ligne à l’aide de leur microphone.

3. Les étudiants LISENT ensuite le texte entier à voix haute.

4. Les étudiants et les enseignants peuvent consulter les RÉSULTATS et les COMMENTAIRES.';
$string['readaloudfieldset'] = 'Exemple de groupe de champs personnalisé';
$string['readaloudname'] = 'Poodll ReadAloud';
$string['readaloudname_help'] =
        'Contenu de l’infobulle associée au champ « Nom de ReadAloud ». La syntaxe Markdown est prise en charge.';
// $string['readaloud'] = 'readaloud';
$string['activitylink'] = 'Lien vers l’activité suivante';
$string['activitylink_help'] = 'Pour afficher un lien vers une autre activité du cours après la tentative, sélectionnez l’activité souhaitée dans la liste déroulante.';
$string['activitylinkname'] = 'Continuer vers l’activité suivante : {$a}';
$string['pluginadministration'] = 'Administration de ReadAloud';
$string['pluginname'] = 'Poodll ReadAloud';
$string['readaloud:addinstance'] = 'Ajouter une nouvelle activité ReadAloud';
$string['readaloud:view'] = 'Voir ReadAloud';
$string['readaloud:view'] = 'Prévisualiser ReadAloud';
$string['readaloud:itemview'] = 'Voir les éléments';
$string['readaloud:itemedit'] = 'Modifier les éléments';
$string['readaloud:tts'] = 'Peut utiliser la synthèse vocale (TTS)';
$string['readaloud:manageattempts'] = 'Peut gérer les tentatives ReadAloud';
$string['readaloud:manage'] = 'Peut gérer les activités ReadAloud';
$string['readaloud:preview'] = 'Peut prévisualiser les activités ReadAloud';
$string['readaloud:submit'] = 'Peut soumettre des tentatives ReadAloud';
$string['readaloud:viewreports'] = 'Peut consulter les notes et les rapports ReadAloud';
$string['readaloud:pushtoclones'] = 'Peut appliquer les paramètres aux clones';
$string['privacy:metadata'] = 'Le plugin Poodll ReadAloud stocke des données personnelles.';

$string['id'] = 'ID';
$string['name'] = 'Nom';
$string['timecreated'] = 'Date de création';
$string['basicheading'] = 'Rapport général';
$string['attemptsheading'] = 'Rapport des tentatives';
// $string['attemptsbyuserheading'] = 'Rapport des tentatives par utilisateur';
$string['attemptssummaryheading'] = 'Résumé des tentatives';
$string['gradingheading'] = 'Évaluation de la dernière tentative de chaque utilisateur.';
$string['machinegradingheading'] = 'Évaluation automatique de la dernière tentative de chaque utilisateur.';
$string['gradingbyuserheading'] = 'Évaluation de toutes les tentatives de : {$a}';
$string['machinegradingbyuserheading'] = 'Évaluation automatique des tentatives de : {$a}';
$string['totalattempts'] = 'Tentatives';
$string['overview'] = 'Vue d’ensemble';
$string['overview_help'] = 'Aide de la vue d’ensemble';
$string['view'] = 'Voir';
$string['preview'] = 'Prévisualiser';
$string['viewreports'] = 'Voir les rapports';
$string['reports'] = 'Rapports';
$string['viewgrading'] = 'Voir les évaluations';
$string['grading'] = 'Évaluation';
$string['gradenow'] = 'Évaluer maintenant';
$string['cannotgradenow'] = ' - ';
// $string['gradenowtitle'] = 'Évaluation : {$a}';
$string['showingattempt'] = 'Affichage de la tentative de : {$a}';
$string['showingmachinegradedattempt'] = 'Affichage de la tentative évaluée automatiquement de : {$a}';
$string['basicreport'] = 'Rapport général';
$string['returntoreports'] = 'Retour aux rapports';
$string['returntogradinghome'] = 'Retour à la page principale des évaluations';
$string['returntomachinegradinghome'] = 'Retour à la page principale des évaluations automatiques';
$string['exportexcel'] = 'Exporter au format CSV';
// $string['mingradedetails'] = 'Note minimale (%) requise pour valider cette activité.';
$string['mingrade'] = 'Note minimale';
$string['deletealluserdata'] = 'Supprimer toutes les données utilisateur';
$string['maxattempts'] = 'Nombre maximal de tentatives';
$string['unlimited'] = 'Illimité';
$string['gradeoptions'] = 'Options de notation';
$string['gradeoptions_help'] =
        'Lorsque plusieurs tentatives sont effectuées par un utilisateur, ce paramètre détermine laquelle sera prise en compte pour la note.';
$string['gradeoptions_details'] =
        'Remarque : ce paramètre détermine la note enregistrée dans le carnet de notes. La page d’évaluation ReadAloud affichera toujours la dernière tentative.';
$string['gradenone'] = 'Aucune note';
$string['gradelowest'] = 'Tentative avec la note la plus faible';
$string['gradehighest'] = 'Tentative avec la meilleure note';
$string['gradelatest'] = 'Note de la dernière tentative';
$string['gradeaverage'] = 'Moyenne de toutes les tentatives';
// $string['defaultsettings'] = 'Paramètres par défaut';
$string['exceededattempts'] = 'Vous avez atteint le nombre maximal de tentatives ({$a}).';
$string['exceededallattempts'] = 'Vous avez utilisé toutes vos tentatives.';
$string['readaloudtask'] = 'Exercice de lecture à voix haute';
$string['passagelabel'] = 'Texte à lire';
$string['welcomelabel'] = 'Instructions par défaut';
$string['welcomelabel_details'] = 'Instructions affichées par défaut. Elles peuvent être modifiées lors de la création d’une nouvelle activité ReadAloud.';
$string['feedbacklabel'] = 'Commentaires par défaut';
$string['feedbacklabel_details'] = 'Texte affiché par défaut dans le champ de commentaires lors de la création d’une nouvelle activité ReadAloud.';
$string['welcomelabel'] = 'Instructions avant la tentative';
$string['feedbacklabel'] = 'Instructions après la tentative';
$string['alternatives'] = 'Variantes';
$string['alternatives_descr'] =
        'Indiquez les variantes acceptées pour certains mots du texte. Une série de mots par ligne. Exemple : their|there|they\'re. Consultez la <a href="https://support.poodll.com/support/solutions/articles/19000096937-tuning-your-read-aloud-activity">documentation</a> pour plus d’informations.';
$string['attemptsheading'] = 'Rapport des tentatives';
$string['attemptsreport'] = 'Rapport des tentatives';
$string['attemptssummaryheading'] = 'Résumé des tentatives';
$string['attemptssummaryreport'] = 'Résumé des tentatives';
$string['audiofile'] = 'Audio';
$string['averages'] = 'Moyenne';
$string['basicheading'] = 'Rapport général';
$string['basicreport'] = 'Rapport général';
$string['beginreading'] = 'Commencer la lecture';
$string['cannotgradenow'] = ' - ';
$string['complete'] = 'Terminé';
$string['defaultfeedback'] = 'Merci pour votre lecture.';
$string['defaultwelcome'] = 'Réalisez cette activité en suivant les étapes affichées à l’écran. Vous écouterez un texte, vous vous entraînerez à le lire, puis vous le lirez entièrement à voix haute avant de consulter vos résultats dans le rapport. Vous devrez peut-être autoriser l’accès à votre microphone.';
$string['deletealluserdata'] = 'Supprimer toutes les données utilisateur';
$string['done'] = 'Terminé';
$string['enabletts'] = 'Activer la synthèse vocale (TTS) (expérimental)';
$string['enabletts_details'] = 'La synthèse vocale (TTS) n’est actuellement pas encore implémentée.';
$string['errorheader'] = 'Erreur';
$string['evaluatedmessage'] = 'Votre dernière tentative a bien été enregistrée et son évaluation est affichée ci-dessous.';
$string['exceededallattempts'] = 'Vous avez utilisé toutes vos tentatives.';
$string['exceededattempts'] = 'Vous avez atteint le nombre maximal de tentatives ({$a}).';
$string['exportexcel'] = 'Exporter au format CSV';
$string['feedbacklabel_details'] = 'Texte affiché par défaut dans le champ de commentaires lors de la création d’une nouvelle activité ReadAloud.';
$string['gotnosound'] = 'Nous ne vous avons pas entendu. Veuillez vérifier les autorisations et les paramètres de votre microphone, puis réessayer.';
$string['gradehighest'] = 'Tentative avec la meilleure note';
$string['gradelatest'] = 'Note de la dernière tentative';
$string['gradenone'] = 'Aucune note';
$string['gradenow'] = 'Évaluer maintenant';
$string['gradeoptions'] = 'Options de notation';
$string['gradeoptions_details'] =
        'Remarque : ce paramètre détermine la note enregistrée dans le carnet de notes. La page d’évaluation ReadAloud n’est pas affectée et affichera toujours la dernière tentative.';
$string['gradeoptions_help'] =
        'Lorsqu’un utilisateur effectue plusieurs tentatives de lecture, ce paramètre détermine laquelle sera prise en compte pour la notation.';
$string['grading'] = 'Évaluation';
$string['gradingbyuserheading'] = 'Évaluation de toutes les tentatives de : {$a}';
$string['gradingheading'] = 'Évaluation de la dernière tentative de chaque utilisateur.';
$string['hiddenevaluationmessage'] = 'Votre tentative a bien été reçue. Merci.';
$string['highest'] = 'Le plus élevé';
$string['id'] = 'ID';
$string['instructions'] = 'Instructions';
$string['locked'] = 'Verrouillé';
$string['machinegradingbyuserheading'] = 'Évaluation automatique des tentatives de : {$a}';
$string['machinegradingheading'] = 'Évaluation automatique de la dernière tentative de chaque utilisateur.';
$string['maxattempts'] = 'Nombre maximal de tentatives';
$string['mingrade'] = 'Note minimale';
$string['modulename'] = 'Poodll ReadAloud';
$string['modulename_help'] =
        'ReadAloud permet aux étudiants de s’entraîner à lire des textes à voix haute et de recevoir un retour sur leur performance. L’activité peut être entièrement évaluée automatiquement et aide les enseignants à évaluer et mieux comprendre les compétences de lecture en langue étrangère de leurs étudiants.

Le déroulement est le suivant :

1. Les étudiants ÉCOUTENT un texte préparé par l’enseignant.
2. Les étudiants S’ENTRAÎNENT à lire le texte ligne par ligne à l’aide de leur microphone.
3. Les étudiants LISENT le texte entier à voix haute.
4. Les étudiants VÉRIFIENT LEUR COMPRÉHENSION grâce à un quiz (facultatif).
5. Les étudiants et les enseignants peuvent consulter les COMMENTAIRES et les RÉSULTATS.';
$string['modulenameplural'] = 'Poodll ReadAloud';
$string['name'] = 'Nom';
$string['notaddedtogradebook'] = 'Il s’agissait d’un exercice d’entraînement et cette tentative n’a pas été ajoutée au carnet de notes.';
$string['notgradedyet'] = 'Votre réponse a bien été reçue, mais n’a pas encore été évaluée. Cela peut prendre quelques minutes.';
$string['notmanuallygradedyet'] = 'Votre réponse a bien été reçue, mais n’a pas encore été évaluée.';
$string['overview_help'] = 'Aide de la vue d’ensemble';
$string['passagelabel'] = 'Texte à lire';
$string['pluginadministration'] = 'Administration de ReadAloud';
$string['pluginname'] = 'Poodll ReadAloud';
$string['preview'] = 'Prévisualiser';
$string['privacy:metadata'] = 'Le plugin Poodll ReadAloud stocke des données personnelles.';
$string['processing'] = 'Traitement en cours';
$string['readaloud:addinstance'] = 'Ajouter une nouvelle activité ReadAloud';

$string['readaloud:manage'] = 'Peut gérer les activités ReadAloud';
$string['readaloud:manageattempts'] = 'Peut gérer les tentatives ReadAloud';
$string['readaloud:preview'] = 'Peut prévisualiser les activités ReadAloud';
$string['readaloud:pushtoclones'] = 'Peut appliquer les paramètres aux clones';
$string['readaloud:submit'] = 'Peut soumettre des tentatives ReadAloud';
$string['readaloud:viewreports'] = 'Peut consulter les notes et les rapports ReadAloud';
$string['readaloudname'] = 'Poodll ReadAloud';
$string['readaloudname_help'] =
        'Contenu de l’infobulle associée au champ « Nom de ReadAloud ». La syntaxe Markdown est prise en charge.';

$string['readaloudtask'] = 'Exercice de lecture à voix haute';
$string['reattempt'] = 'Réessayer';
$string['reports'] = 'Rapports';
$string['returntogradinghome'] = 'Retour à la page principale des évaluations';
$string['returntomachinegradinghome'] = 'Retour à la page principale des évaluations automatiques';
$string['returntoreports'] = 'Retour aux rapports';
$string['saveandnext'] = 'Enregistrer… et suivant';
$string['showingattempt'] = 'Affichage de la tentative de : {$a}';
$string['showingmachinegradedattempt'] = 'Affichage de la tentative évaluée automatiquement de : {$a}';
$string['submitted'] = 'Soumis';
$string['timelimit'] = 'Limite de temps';
$string['totalattempts'] = 'Tentatives';

$string['unlimited'] = 'Illimité';
$string['uploadconverterror'] =
        'Une erreur est survenue lors de l’envoi de votre fichier vers le serveur. Votre réponse n’a PAS été reçue. Veuillez actualiser la page et réessayer.';
$string['username'] = 'Utilisateur';
$string['view'] = 'Voir';
$string['viewgrading'] = 'Voir les évaluations';
$string['viewreports'] = 'Voir les rapports';

$string['welcomelabel_details'] = 'Instructions affichées par défaut. Elles peuvent être modifiées lors de la création d’une nouvelle activité ReadAloud.';

$string['wpm'] = 'MPM';

// We hijacked this setting for both TTS STT .... bad ... but they are always the same aren't they?
$string['ttslanguage'] = 'Langue du texte';
$string['deleteattemptconfirm'] = 'Êtes-vous sûr de vouloir supprimer cette tentative ?';
$string['deletenow'] = '';
$string['allowearlyexit'] = 'Autoriser une fin anticipée';
$string['allowearlyexit_details'] =
        'Si cette option est activée, les étudiants peuvent terminer avant la limite de temps en cliquant sur un bouton « Terminer ». Le MPM est alors calculé à partir de la durée réelle de leur enregistrement.';
$string['allowearlyexit_defaultdetails'] =
        'Définit la valeur par défaut de l’option « Autoriser une fin anticipée ». Ce paramètre peut être remplacé au niveau de chaque activité. Si activé, les étudiants peuvent terminer avant la limite de temps et le MPM est calculé à partir de leur durée réelle d’enregistrement.';
$string['itemsperpage'] = 'Éléments par page';
$string['accuracy'] = 'Précision';
$string['accuracy_p'] = 'Précision (%)';
$string['av_accuracy_p'] = 'Précision moy. (%)';
$string['h_accuracy_p'] = 'Précision max. (%)';
$string['mistakes'] = 'Erreurs';
$string['grade'] = 'Note';
$string['grade_p'] = 'Note finale (%)';
$string['readgrade_p'] = 'Note de lecture (%)';
$string['quizscore_p'] = 'Note du quiz (%)';
$string['av_readgrade_p'] = 'Note de lecture moy. (%)';
$string['h_readgrade_p'] = 'Meilleure note de lecture (%)';
$string['av_quizscore_p'] = 'Note moyenne du quiz (%)';
$string['h_quizscore_p'] = 'Meilleure note du quiz (%)';
$string['av_wpm'] = 'MPM moyen';
$string['h_wpm'] = 'MPM max.';
$string['targetwpm'] = 'MPM cible';
$string['targetwpm_details'] =
        'Valeur cible par défaut du MPM. La note enregistrée dans le carnet de notes est calculée en utilisant cette valeur comme score maximal. Si le MPM de l’étudiant est égal ou supérieur à cette valeur, il obtient 100 %. Cette valeur peut également être définie pour chaque activité.';
$string['targetwpm_help'] =
        'Score MPM cible. La note est calculée en utilisant cette valeur comme score maximal. Si le MPM de l’étudiant est égal ou supérieur à cette valeur, il obtient 100 %.';
$string['passage'] = 'Texte à lire';
$string['passage_help'] = 'Le texte qui sera affiché à l’étudiant pour être lu.';
$string['passage_descr'] = 'Saisissez le texte de lecture ci-dessus. Il ne doit pas dépasser 3000 caractères si vous souhaitez générer automatiquement un audio.';
$string['timelimit_help'] = 'Définit une limite de temps pour la lecture. Cette durée est utilisée dans le calcul du MPM. Pensez également à activer l’option « Autoriser une fin anticipée ».';
$string['ttslanguage_help'] = 'Cette valeur est utilisée pour la reconnaissance vocale et la synthèse vocale.';
$string['ttsvoice_descr'] = 'Voix de synthèse utilisée pour lire le texte. Si elle est suivie du symbole « + », il s’agit d’une voix de meilleure qualité. Si elle est suivie du symbole « ! », vous devrez ajouter manuellement les pauses dans l’onglet Audio modèle.';
$string['ttsvoice_help'] = 'Voix de synthèse utilisée pour lire le texte. Choisissez une voix correspondant à la langue du texte. Les voix marquées d’un « + » sont de meilleure qualité. Les voix marquées d’un « ! » nécessitent l’ajout manuel des pauses dans l’onglet Audio modèle. Vous pouvez également enregistrer ou importer un audio modèle personnalisé.';
$string['ttsspeed_help'] = 'Vitesse de lecture de la synthèse vocale. Les vitesses « Lente » ou « Très lente » conviennent généralement mieux aux apprenants, mais peuvent légèrement déformer le son.';
$string['alternatives_help'] = 'Indiquez les variantes acceptées pour certains mots du texte. Une série de mots par ligne. Exemple : their|there|they\'re. Consultez la documentation pour plus d’informations.';

$string['accadjust'] = 'Correction fixe';
$string['accadjust_details'] =
        'Nombre d’erreurs de lecture à compenser dans le calcul du MPM. Si la méthode de correction est définie sur « Correction fixe », cette valeur sera utilisée pour compenser les erreurs de transcription automatique.';
$string['accadjust_help'] =
        'Cette valeur doit correspondre autant que possible au nombre moyen estimé d’erreurs de transcription automatique pour le texte.';

$string['accadjustmethod'] = 'Correction du MPM (IA)';
$string['accadjustmethod_details'] =
        'Ajuste le score MPM en ignorant ou en réduisant l’impact de certaines erreurs de lecture détectées par l’IA. L’option « Aucune correction » soustrait toutes les erreurs du score final.';
$string['accadjustmethod_help'] =
        'Pour ajuster le MPM, vous pouvez : ne jamais corriger, appliquer une correction fixe ou ignorer les erreurs lors du calcul.';
$string['accmethod_none'] = 'Aucune correction';
$string['accmethod_auto'] = 'Correction automatique';
$string['accmethod_fixed'] = 'Correction fixe';
$string['accmethod_noerrors'] = 'Ignorer toutes les erreurs';

$string['apiuser'] = 'Utilisateur de l’API Poodll';
$string['apiuser_details'] = 'Nom d’utilisateur du compte Poodll autorisé à utiliser les services Poodll sur ce site.';
$string['apisecret'] = 'Clé secrète de l’API Poodll';
$string['enableai'] = 'Activer l’IA';
$string['enableai_details'] = 'Permet à ReadAloud d’évaluer automatiquement les résultats des tentatives des étudiants à l’aide de l’intelligence artificielle.';

$string['useast1'] = 'Est des États-Unis';
$string['tokyo'] = 'Tokyo, Japon';
$string['sydney'] = 'Sydney, Australie';
$string['dublin'] = 'Dublin, Irlande';
$string['capetown'] = 'Le Cap, Afrique du Sud';
$string['bahrain'] = 'Bahreïn';
$string['ottawa'] = 'Ottawa, Canada';
$string['frankfurt'] = 'Francfort, Allemagne';
$string['london'] = 'Londres, Royaume-Uni';
$string['saopaulo'] = 'São Paulo, Brésil';
$string['singapore'] = 'Singapour';
$string['mumbai'] = 'Mumbai, Inde';
$string['ningxia'] = 'Ningxia, Chine';
$string['forever'] = 'N’expire jamais';

$string['azureapikey'] = 'Clé API Azure Speech';
$string['azureapiregion'] = 'Région Azure Speech';
$string['otherapikeys'] = 'Autres clés API (BYOK)';

$string['en-us'] = 'Anglais (États-Unis)';
$string['es-us'] = 'Espagnol (États-Unis)';
$string['en-au'] = 'Anglais (Australie)';
$string['en-ph'] = 'Anglais (Philippines)';
$string['en-gb'] = 'Anglais (Royaume-Uni)';
$string['fr-ca'] = 'Français (Canada)';
$string['fr-fr'] = 'Français (France)';
$string['it-it'] = 'Italien (Italie)';
$string['pt-br'] = 'Portugais (Brésil)';
$string['en-in'] = 'Anglais (Inde)';
$string['es-es'] = 'Espagnol (Espagne)';
$string['fr-fr'] = 'Français (France)';
$string['fil-ph'] = 'Filipino';
$string['de-de'] = 'Allemand (Allemagne)';
$string['de-ch'] = 'Allemand (Suisse)';
$string['de-at'] = 'Allemand (Autriche)';
$string['da-dk'] = 'Danois (Danemark)';
$string['hi-in'] = 'Hindi';
$string['ko-kr'] = 'Coréen';
$string['ar-ae'] = 'Arabe (Golfe)';
$string['ar-sa'] = 'Arabe (arabe standard moderne)';
$string['zh-cn'] = 'Chinois (mandarin continental)';
$string['nl-nl'] = 'Néerlandais (Pays-Bas)';
$string['nl-be'] = 'Néerlandais (Belgique)';
$string['en-ie'] = 'Anglais (Irlande)';
$string['en-wl'] = 'Anglais (Pays de Galles)';
$string['en-ab'] = 'Anglais (Écosse)';
$string['en-nz'] = 'Anglais (Nouvelle-Zélande)';
$string['en-za'] = 'Anglais (Afrique du Sud)';
$string['fa-ir'] = 'Persan';

$string['he-il'] = 'Hébreu';
$string['id-id'] = 'Indonésien';
$string['ja-jp'] = 'Japonais';
$string['ms-my'] = 'Malais';
$string['mi-nz'] = 'Maori';
$string['pt-pt'] = 'Portugais (Portugal)';
$string['ru-ru'] = 'Russe';
$string['ta-in'] = 'Tamoul';
$string['te-in'] = 'Télougou';
$string['tr-tr'] = 'Turc';

$string['uk-ua'] = 'Ukrainien';
$string['eu-es'] = 'Basque';
$string['fi-fi'] = 'Finnois';
$string['hu-hu'] = 'Hongrois';

$string['sv-se'] = 'Suédois';
$string['no-no'] = 'Norvégien';
$string['nb-no'] = 'Norvégien (bokmål)';
$string['nn-no'] = 'Norvégien (nynorsk)';
$string['pl-pl'] = 'Polonais';
$string['ro-ro'] = 'Roumain';

$string['bg-bg'] = 'Bulgare'; // Bulgarian
$string['cs-cz'] = 'Tchèque'; // Czech
$string['el-gr'] = 'Grec'; // Greek
$string['hr-hr'] = 'Croate'; // Croatian
$string['lt-lt'] = 'Lituanien'; // Lithuanian
$string['lv-lv'] = 'Letton'; // Latvian
$string['sk-sk'] = 'Slovaque'; // Slovak
$string['sl-si'] = 'Slovène'; // Slovenian
$string['so-so'] = 'Somali'; // Slovenian
$string['ps-af'] = 'Pachto'; // Afghan Pashto
$string['is-is'] = 'Islandais'; // Icelandic
$string['mk-mk'] = 'Macédonien'; // Macedonian
$string['sr-rs'] = 'Serbe'; // Serbian
$string['vi-vn'] = 'Vietnamien'; // Vietnamese

$string['awsregion'] = 'Région AWS';
$string['region'] = 'Région AWS';
$string['awsregion_details'] = 'Choisissez la région la plus proche de vous. Vos données resteront dans cette région. La région du Cap ne prend en charge que l’anglais et l’allemand.';
$string['expiredays'] = 'Nombre de jours de conservation du fichier';
$string['aigradenow'] = 'Évaluation par IA';

$string['machinegrading'] = 'Évaluations automatiques';
$string['viewmachinegrading'] = 'Évaluation automatique';
$string['review'] = 'Vérifier';
$string['regrade'] = 'Réévaluer';

$string['spotcheckbutton'] = 'Mode vérification ponctuelle';
$string['gradingbutton'] = 'Mode évaluation';
$string['transcriptcheckbutton'] = 'Mode vérification de la transcription';
$string['doclear'] = 'Effacer tous les marqueurs';

$string['gradethisattempt'] = 'Évaluer cette tentative';
$string['rawwpm'] = 'MPM';
$string['rawaccuracy_p'] = 'Précision (%)';
$string['rawgrade_p'] = 'Note (%)';
$string['adjustedwpm'] = 'MPM ajusté';
$string['adjustedaccuracy_p'] = 'Précision ajustée (%)';
$string['adjustedgrade_p'] = 'Note ajustée (%)';

$string['evaluationview'] = 'Affichage de l’évaluation';
$string['evaluationview_details'] = 'Ce qui doit être affiché aux étudiants après une tentative évaluée';
$string['humanpostattempt'] = 'Affichage de l’évaluation (humaine)';
$string['machinepostattempt'] = 'Affichage de l’évaluation (automatique)';
$string['machinepostattempt_details'] = 'Ce qui doit être affiché aux étudiants après une tentative évaluée automatiquement';
$string['postattempt_none'] = 'Afficher le texte. Ne pas afficher l’évaluation ni les erreurs.';
$string['postattempt_eval'] = 'Afficher le texte et l’évaluation (MPM, précision, note)';
$string['postattempt_evalerrorsnograde'] = 'Afficher le texte, l’évaluation (MPM, précision) et les erreurs';
$string['postattempt_evalerrors'] = 'Afficher le texte, l’évaluation (MPM, précision, note) et les erreurs';

$string['attemptsperpage'] = 'Tentatives à afficher par page : ';
$string['backtotop'] = 'Vérifier les résultats';
$string['transcript'] = 'Transcription';
$string['quickgrade'] = 'Évaluation rapide';
$string['ok'] = 'OK';
$string['ng'] = 'Pas OK';
$string['notok'] = 'Pas OK';
$string['machinegrademethod'] = 'Évaluation humaine/automatique';
$string['machinegrademethod_help'] = 'Utiliser les évaluations automatiques ou humaines comme notes dans le carnet de notes.';
$string['machinegradenone'] = 'Ne jamais utiliser l’évaluation automatique pour la note';
$string['machinegradehybrid'] = 'Utiliser l’évaluation humaine ou automatique pour la note';
$string['machinegrademachineonly'] = 'Toujours utiliser la note de l’évaluation automatique';
$string['admintab'] = 'Administrateur';
$string['viewadmintab'] = 'Voir l’onglet administrateur';
$string['machineregradeall'] = 'Enregistrer et réévaluer toutes les tentatives';
$string['pushalltogradebook'] = 'Renvoyer les évaluations vers le carnet de notes';
$string['currenterrorestimate'] = 'Estimation actuelle des erreurs : {$a}';
$string['admintabtitle'] = 'Administrateur';
$string['admintabinstructions'] =
        'Sur cette page, vous pouvez modifier les variantes du texte tout en consultant un résumé des erreurs de transcription. Lors de l’enregistrement, toutes les tentatives seront réévaluées et les notes ajustées seront envoyées au carnet de notes.';

$string['noattemptsregrade'] = 'Aucune tentative à réévaluer';
$string['machineregraded'] = '{$a->done} tentative(s) réévaluée(s) avec succès. {$a->skipped} tentative(s) ignorée(s).';
$string['machinegradespushed'] = 'Les notes ont bien été envoyées vers le carnet de notes.';

$string['notimelimit'] = 'Aucune limite de temps';
$string['xsecs'] = '{$a} secondes';
$string['onemin'] = '1 minute';
$string['xmins'] = '{$a} minutes';
$string['oneminxsecs'] = '1 minute {$a} secondes';
$string['xminsecs'] = '{$a->minutes} minutes {$a->seconds} secondes';

$string['postattemptheader'] = 'Options après la tentative';
$string['recordingaiheader'] = 'Options d’enregistrement et d’IA';
$string['grader'] = 'Évalué par';
$string['grader_ai'] = 'IA';
$string['grader_human'] = 'Humain';
$string['grader_ungraded'] = 'Non évalué';

$string['displaysubs'] = '{$a->subscriptionname} : expire le {$a->expiredate}';
$string['noapiuser'] = 'Aucun utilisateur API saisi. ReadAloud ne fonctionnera pas correctement.';
$string['noapisecret'] = 'Aucune clé secrète API saisie. ReadAloud ne fonctionnera pas correctement.';
$string['credentialsinvalid'] = 'L’utilisateur API et la clé secrète saisis n’ont pas permis d’obtenir l’accès. Veuillez les vérifier.';
$string['appauthorised'] = 'Poodll ReadAloud est autorisé pour ce site.';
$string['appnotauthorised'] = 'Poodll ReadAloud n’est PAS autorisé pour ce site.';
$string['refreshtoken'] = 'Actualiser les informations de licence';
$string['notokenincache'] = 'Actualisez pour afficher les informations de licence. Contactez le support Poodll en cas de problème.';
// These errors are displayed on activity page.
$string['nocredentials'] = 'Utilisateur API et clé secrète non saisis. Veuillez les renseigner sur <a href="{$a}">la page des paramètres.</a> Vous pouvez les obtenir sur <a href="https://poodll.com/member">Poodll.com.</a>';
$string['novalidcredentials'] = 'L’utilisateur API et la clé secrète ont été refusés et n’ont pas permis d’obtenir l’accès. Veuillez les vérifier sur <a href="{$a}">la page des paramètres.</a> Vous pouvez les obtenir sur <a href="https://poodll.com/member">Poodll.com.</a>';
$string['nosubscriptions'] = 'Aucun abonnement actif pour ce site/plugin.';

$string['privacy:metadata:attemptid'] = 'Identifiant unique d’une tentative ReadAloud d’un utilisateur.';
$string['privacy:metadata:readaloudid'] = 'Identifiant unique d’une instance d’activité ReadAloud.';
$string['privacy:metadata:userid'] = 'Identifiant utilisateur associé à la tentative ReadAloud.';
$string['privacy:metadata:filename'] = 'URL des fichiers des enregistrements soumis.';
$string['privacy:metadata:wpm'] = 'Score en mots par minute pour la tentative.';
$string['privacy:metadata:accuracy'] = 'Score de précision pour la tentative.';
$string['privacy:metadata:sessionscore'] = 'Score de session pour la tentative.';
$string['privacy:metadata:sessiontime'] = 'Durée de la session, c’est-à-dire durée d’enregistrement, pour la tentative.';
$string['privacy:metadata:sessionerrors'] = 'Erreurs de lecture pour la tentative.';
$string['privacy:metadata:sessionendword'] = 'Position du dernier mot pour la tentative.';
$string['privacy:metadata:errorcount'] = 'Nombre d’erreurs de lecture pour la tentative.';
$string['privacy:metadata:timemodified'] = 'Dernière date de modification de la tentative.';
$string['privacy:metadata:attempttable'] = 'Stocke les scores et autres données utilisateur associés à une tentative de lecture à voix haute.';
$string['privacy:metadata:aitable'] =
        'Stocke les scores et autres données utilisateur associés à une tentative de lecture à voix haute évaluée automatiquement.';
$string['privacy:metadata:transcriptpurpose'] = 'Transcriptions courtes des enregistrements.';
$string['privacy:metadata:fulltranscriptpurpose'] = 'Transcriptions complètes des enregistrements.';
$string['privacy:metadata:cloudpoodllcom:userid'] =
        'Le plugin ReadAloud inclut l’identifiant utilisateur Moodle dans les URL des enregistrements et des transcriptions.';
$string['privacy:metadata:cloudpoodllcom'] = 'Le plugin ReadAloud stocke les enregistrements dans des compartiments AWS S3 via cloud.poodll.com.';

$string['mistranscriptions_summary'] = 'Résumé des erreurs de transcription.';
$string['nomistranscriptions'] = 'Aucune erreur de transcription.';
$string['passageindex'] = 'Index du texte';
$string['passageword'] = 'Mot du texte';
$string['mistranscriptions'] = 'Erreurs de transcription';
$string['mistrans_count'] = 'Nombre';
$string['total_mistranscriptions'] = 'Nombre total d’erreurs de transcription : {$a}';
$string['startreading'] = 'Lire';
$string['readagain'] = 'Relire';
$string['transcriber_guided'] = 'STT guidé (Poodll)';
$string['transcriber_strict'] = 'STT ouvert (strict)';

$string['stricttranscribe'] = 'Transcripteur du texte';
$string['stricttranscribe_details'] = 'Transcripteur à utiliser pour les lectures du texte complet.';

$string['sessionscoremethod'] = 'Calcul de la note';
$string['sessionscoremethod_help'] = 'La valeur (%) du carnet de notes est calculée sous forme de pourcentage : soit MPM / MPM cible (normal), soit (MPM - erreurs) / MPM cible (strict).';
$string['sessionscorenormal'] = 'Normal : total des mots corrects par minute / MPM cible';
$string['sessionscorestrict'] = 'Strict : (total des mots corrects - erreurs) par minute / MPM cible';
$string['modelaudio'] = 'Audio modèle';
$string['ttsvoice'] = 'Voix TTS';
$string['enablepreview'] = 'Activer le mode écoute';
$string['enableshadow'] = 'Activer le mode entraînement (shadowing)';
$string['enablelandr'] = 'Activer le mode entraînement (écouter et répéter)';
$string['savemodelaudio'] = 'Enregistrer l’audio';
$string['uploadmodelaudio'] = 'Téléverser un fichier audio';
$string['modelaudioclear'] = 'Effacer l’audio';
$string['modelaudiobreaksgenerate'] = 'Regénérer le balisage de l’audio modèle';
$string['modelaudio_recordinstructions'] = 'Enregistrez ici l’audio qui servira d’audio modèle. Vous pouvez aussi téléverser un fichier audio en cliquant sur le bouton correspondant. Un délai de quelques minutes sera nécessaire avant que les points de pause du texte et l’audio soient synchronisés automatiquement.';
$string['modelaudio_playerinstructions'] = 'L’audio modèle actuel peut être écouté avec le lecteur ci-dessous.';
$string['modelaudio_breaksinstructions'] = 'Touchez les mots du texte ci-dessous pour ajouter une pause à cet endroit dans la lecture audio des modes écoute et entraînement. Le système synchronisera automatiquement l’audio et le texte. Cochez <i>temporisation manuelle des pauses</i> pour placer les pauses touchées à la position actuelle de l’audio en cours de lecture.';
$string['modelaudio_recordtitle'] = 'Enregistrer l’audio modèle';
$string['modelaudio_playertitle'] = 'Écouter l’audio modèle';
$string['modelaudio_breakstitle'] = 'Baliser l’audio modèle';
$string['viewmodeltranscript'] = 'Voir la transcription du modèle';

$string['ttsspeed'] = 'Vitesse TTS';
$string['mediumspeed'] = 'Moyenne';
$string['slowspeed'] = 'Lente';
$string['extraslowspeed'] = 'Très lente';

$string['welcomemenu'] = 'Choisissez parmi les options ci-dessous.';
$string['returnmenu'] = 'Retour au menu';
$string['attemptno'] = 'Tentative {$a}';
$string['previewhelp'] = 'Écoutez un locuteur lire le texte à voix haute. Vous n’avez pas besoin de lire à voix haute.';
$string['readhelp'] = 'Lisez le texte à voix haute. Parlez à une vitesse naturelle pour vous.';
$string['shadowhelp'] = 'Lisez le texte à voix haute en même temps que l’enseignant. Il est conseillé de porter un casque.';
$string['practicehelp'] = 'Écoutez le locuteur. Répétez après chaque phrase et vérifiez votre prononciation.';
$string['quizhelp'] = 'Lisez le texte silencieusement. Répondez ensuite aux questions sur le texte.';
$string['quizfinishedhelp'] = 'Consultez vos résultats. Avez-vous bien compris le texte ?';
$string['playbutton'] = 'Lire';
$string['recordbutton'] = 'Enregistrer';
$string['stopbutton'] = 'Arrêter';
$string['taptolisten'] = 'Touchez pour écouter';

$string['returntomenu'] = 'Retour au menu';
$string['fullreport'] = 'Voir le rapport complet';
$string['fullreportnoeval'] = 'Voir le texte';

$string['secs_till_check'] = 'Vérification des résultats dans : ';
$string['checking'] = ' ... vérification ... ';

$string['recorder'] = 'Type d’enregistreur audio';
$string['recorder_help'] = 'Choisissez le type d’enregistreur audio le mieux adapté à vos étudiants et à votre situation.';
$string['defaultrecorder'] = 'Enregistreur par défaut';
$string['defaultrecorder_details'] = 'Choisissez l’enregistreur affiché par défaut aux étudiants.';
$string['rec_readaloud'] = 'Test micro puis démarrage';
$string['rec_once'] = 'Démarrer directement';
$string['rec_upload'] = 'Téléversement (développeurs/admins)';

$string['close'] = 'Fermer';
$string['modelaudiowarning'] = 'Audio modèle non balisé.';
$string['modelaudiobreaksclear'] = ' Effacer le balisage de l’audio modèle';
$string['savemodelaudiomarkup'] = ' Enregistrer le balisage de l’audio modèle';
$string['enablesetuptab'] = 'Activer l’onglet de configuration';
$string['enablesetuptab_details'] = 'Afficher aux administrateurs un onglet contenant les paramètres de l’instance d’activité. Peu utile dans la plupart des cas.';
$string['setup'] = 'Configuration';
$string['manualbreaktiming'] = ' Temporisation manuelle des pauses';

// rsquestions
$string['numeric'] = 'Doit être numérique ';
$string['iteminuse'] = 'Cet élément fait partie de l’historique des tentatives des utilisateurs. Il ne peut pas être supprimé.';

// Questions.
$string['rsquestions'] = 'Questions';
$string['managersquestions'] = 'Gérer les questions';
$string['correctanswer'] = 'Bonne réponse';
$string['incorrectanswer'] = 'Mauvaise réponse';
$string['whatdonow'] = 'Ajoutez ou modifiez les questions du quiz après lecture.';
$string['editingitem'] = 'Modification d’une question';
$string['createaitem'] = 'Créer une question';
$string['edit'] = 'Modifier';
$string['item'] = 'Élément';
$string['itemtitle'] = 'Titre de la question';
$string['itemcontents'] = 'Texte de la question';
$string['answer'] = 'Réponse';
$string['saveitem'] = 'Enregistrer l’élément';
$string['itemname'] = 'Nom de la question';
$string['itemorder'] = 'Ordre de l’élément';
$string['actions'] = 'Actions';
$string['edititem'] = 'Modifier l’élément';
$string['previewitem'] = 'Prévisualiser l’élément';
$string['duplicateitem'] = 'Dupliquer l’élément';
$string['confirmitemdelete'] = 'Voulez-vous vraiment <i>SUPPRIMER</i> cet élément ? : {$a}';
$string['confirmitemdeletetitle'] = 'Supprimer vraiment cet élément ?';
$string['noitems'] = 'Ce quiz ne contient aucune question';
$string['textchoice'] = 'Choix avec zone de texte';
$string['textboxchoice'] = 'Choix avec champ de texte';
$string['quiz'] = 'Quiz';
$string['waiting'] = '-- en attente --';
$string['waitingforteacher'] = 'Votre enseignant vérifiera bientôt votre lecture.';
$string['quizcompletedwarning'] = 'Quiz terminé. Touchez pour le revoir.';

$string['notmasterinstance'] = 'Vous ne pouvez pas appliquer les paramètres depuis cette activité ReadAloud, sauf si l’option « Instance maître » est activée dans les paramètres de l’activité.';
$string['push'] = 'Appliquer';
$string['pushpage'] = 'Page d’application des paramètres';
$string['pushalternatives'] = 'Appliquer les variantes';
$string['pushalternatives_done'] = 'Les variantes ont été appliquées';

$string['pushpassage'] = 'Appliquer le texte et les paramètres associés';
$string['pushpassage_done'] = 'Le texte a été appliqué';

$string['pushquestions'] = 'Appliquer les questions';
$string['pushquestions_done'] = 'Les questions ont été appliquées';

$string['pushtargetwpm'] = 'MPM cible';
$string['pushtargetwpm_done'] = 'Le MPM cible a été appliqué';

$string['pushtimelimit'] = 'Limite de temps';
$string['pushtimelimit_done'] = 'La limite de temps a été appliquée';

$string['pushcanexitearly'] = 'Autoriser une fin anticipée';
$string['pushcanexitearly_done'] = 'Le paramètre de fin anticipée a été appliqué';

$string['pushmodes'] = 'Modes';
$string['pushmodes_done'] = 'Les modes ont été appliqués';

$string['pushgradesettings'] = 'Paramètres de notation';
$string['pushgradesettings_done'] = 'Les paramètres de notation ont été appliqués';

$string['pushttsmodelaudio'] = 'Appliquer le TTS et l’audio modèle';
$string['pushttsmodelaudio_done'] = 'Le TTS et l’audio modèle ont été appliqués';

$string['masterinstance'] = 'Instance maître';
$string['masterinstance_details'] = 'L’instance maître permet à l’auteur d’appliquer les paramètres individuels d’une activité ReadAloud aux copies existantes de cette même activité. Elles doivent avoir exactement le même nom.';

$string['pushpage_explanation'] = 'Utilisez les boutons de cette page pour appliquer les paramètres de cette instance ReadAloud à ses clones, c’est-à-dire aux activités portant le même nom. Attention : cette action est irréversible. Assurez-vous donc de votre choix avant de l’utiliser.';
$string['pushpage_clonecount'] = 'Cette activité possède {$a} clone(s). <br><br>';
$string['pushpage_noclones'] = 'Cette activité EST une instance maître, mais aucune autre activité ne porte le même nom, c’est-à-dire qu’il n’existe aucun clone. Il n’y a donc aucun paramètre à appliquer. Vérifiez qu’il s’agit de la bonne activité. Si vous faites simplement un test, dupliquez cette activité et renommez la copie avec le même nom.<br><br>';

$string['disableshadowgrading'] = 'Désactiver la notation du mode shadowing';
$string['disableshadowgrading_details'] = 'Si cette option est activée, les tentatives effectuées en mode shadowing seront évaluées, mais aucune note ne sera envoyée au carnet de notes.';
$string['developer'] = 'Développeur';

$string['freetrial'] = 'Obtenir des identifiants API Cloud Poodll et un essai gratuit';
$string['freetrial_desc'] = 'Une fenêtre devrait apparaître pour vous permettre de vous inscrire à un essai gratuit avec Poodll. Après l’inscription, connectez-vous au tableau de bord membre pour récupérer votre utilisateur API et votre clé secrète, puis enregistrez l’URL de votre site.';
$string['fillcredentials'] = 'Renseigner l’utilisateur API et la clé secrète avec des identifiants existants';
$string['viewstart'] = 'Ouverture de l’activité';
$string['viewend'] = 'Fermeture de l’activité';
$string['viewstart_help'] = 'Si défini, empêche l’étudiant d’accéder à l’activité avant la date/heure de début.';
$string['viewend_help'] = 'Si défini, empêche l’étudiant d’accéder à l’activité après la date/heure de fermeture.';
$string['activitydate:submissionsdue'] = 'À rendre le :';
$string['activitydate:submissionsopen'] = 'Ouverture :';
$string['activitydate:submissionsopened'] = 'Ouvert le :';
$string['open'] = 'Ouverture : ';
$string['until'] = 'Jusqu’au : ';
$string['activityopenscloses'] = 'Dates d’ouverture et de fermeture de l’activité';
$string['nottsvoice'] = 'Aucune voix TTS';

$string['guidedtranscriptionadmin'] = 'Administration de la transcription guidée';
$string['usecorpus'] = 'Type de transcription guidée';
$string['usecorpuschanged'] = 'Le type de transcription guidée a été modifié';

$string['applysettingsrange'] = 'Appliquer le paramètre à :';
$string['apply_activity'] = 'cette activité';
$string['apply_course'] = 'les activités de ce cours';
$string['apply_site'] = 'les activités de ce site';

$string['corpusrange'] = 'Portée du corpus';
$string['corpusrange_course'] = 'Ce cours';
$string['corpusrange_site'] = 'Ce site';
$string['guidedtrans_corpus'] = 'Utiliser le corpus (tous les textes ReadAloud)';
$string['guidedtrans_passage'] = 'Utiliser le texte de cette activité';
$string['guidedtransinstructions'] = 'Lors de l’utilisation de la transcription guidée, le transcripteur orientera la transcription vers le guide, c’est-à-dire les mots/expressions du texte de cette activité ou les mots/expressions du corpus complet des textes ReadAloud. L’utilisation du corpus complet des textes ReadAloud permet de détecter davantage d’erreurs de lecture.';
$string['pushcorpus_details'] = 'Le corpus du cours/site sera mis à jour automatiquement, mais vous pouvez utiliser le bouton ci-dessous pour mettre à jour et appliquer le guide du corpus si nécessaire. Cela générera un guide à partir de la portée du corpus et configurera toutes les activités ReadAloud utilisant la transcription guidée dans cette portée pour utiliser ce guide.';
$string['pushcorpus_button'] = 'Mettre à jour et appliquer le guide du corpus';
$string['corpuspushed'] = 'Guide du corpus appliqué';
$string['passagekey'] = 'Clé du texte';
$string['passagekey_details'] =
        'La clé du texte est simplement une étiquette qui sera exportée au format CSV avec certains rapports afin de faciliter leur traitement ultérieur dans un tableur. Vous pouvez la laisser vide.';
$string['passagekey_help'] =
        'La clé du texte est simplement une étiquette qui sera exportée au format CSV avec certains rapports afin de faciliter leur traitement ultérieur dans un tableur.';

$string['courseattemptsreport'] = 'Rapport des tentatives du cours';
$string['courseattemptsheading'] = 'Rapport des tentatives du cours';
$string['studentid'] = 'N° étud.';
$string['studentname'] = 'Nom de l’étudiant';
$string['activityname'] = 'Nom RA';
$string['errorcount'] = 'Nb d’erreurs';
$string['activitywords'] = 'Nb de mots dans le texte';
$string['readingtime'] = 'Temps de lecture (s)';
$string['oralreadingscore'] = 'Score de lecture orale';
$string['oralreadingscore_p'] = 'Score de lecture orale (%)';
$string['reportsmenutoptext'] = 'Consultez les tentatives des activités ReadAloud à l’aide des rapports ci-dessous.';
$string['courseattempts_explanation'] = 'Toutes les tentatives des activités ReadAloud de ce cours';
$string['attemptssummary_explanation'] = 'Résumé des tentatives ReadAloud par utilisateur dans cette activité.';

$string['customfont'] = 'Police personnalisée';
$string['customfont_help'] = 'Nom d’une police qui remplacera la police par défaut du site pour l’affichage de ce texte. L’orthographe et la casse doivent être exactes, par exemple Andika ou Comic Sans MS.';
$string['advancedheader'] = 'Avancé';

$string['missedwords'] = 'Mots manqués';
$string['missedwordsheading'] = 'Mots manqués';
$string['missedwordsreport'] = 'Mots manqués';
$string['missedwords_explanation'] = 'Les mots avec le plus d’erreurs dans les tentatives les plus récentes';
$string['missed_count'] = 'Nombre de mots manqués';
$string['rank'] = 'Rang';

$string['unit_percent'] = '%';

$string['totalwords'] = 'Nombre total de mots';
$string['sentences'] = 'Phrases';
$string['uniquewords'] = 'Mots uniques';
$string['ideacount'] = 'Concepts';
$string['relevance'] = 'Pertinence';
$string['original'] = 'Original';
$string['corrected'] = 'Corrigé';

$string['confirm_cancel_recording'] = 'Annuler l’enregistrement et quitter cette tentative ?';
$string['confirm_read_again'] = 'Annuler cette lecture et en faire une nouvelle ?';
$string['aitextutilsshow'] = 'Afficher les outils IA pour le texte (bêta)';
$string['aitextutilshide'] = 'Masquer les outils IA pour le texte (bêta)';
$string['textgenerator_instructions'] = 'Saisissez une courte description de sujet non fictionnel, puis cliquez sur le bouton pour générer un texte. Le résultat ne sera pas toujours factuellement exact. Soyez prudent avant de l’utiliser avec des étudiants.';
$string['textsimplifier_instructions'] = 'Choisissez le niveau de simplification, puis cliquez sur le bouton pour simplifier le texte. Le texte sera simplifié au niveau approximatif choisi.';
$string['article-topic-here'] = 'ex. : avantages et inconvénients des réseaux sociaux';
$string['generate-text'] = 'Générer un texte';
$string['simplify-text'] = 'Simplifier le texte';
$string['entersomething'] = 'Veuillez saisir un sujet afin de générer un texte';
$string['text-too-long-100'] = 'Votre sujet ne doit pas dépasser 100 caractères. Décrivez simplement le sujet, sans écrire une phrase complète ni ajouter d’instructions supplémentaires.';
$string['textoverwriteconfirm'] = 'Confirmation de remplacement';
$string['reallyoverwritepassage'] = 'Remplacer le texte actuel ?';
$string['overwrite'] = 'Remplacer';
$string['cancel'] = 'Annuler';
$string['datatables_info'] = 'Affichage de _START_ à _END_ sur _TOTAL_ entrées';
$string['datatables_infoempty'] = 'Affichage de 0 à 0 sur 0 entrée';
$string['datatables_lengthmenu'] = 'Afficher _MENU_ entrées';
$string['datatables_search'] = 'Rechercher :';
$string['datatables_zerorecords'] = 'Aucun enregistrement correspondant trouvé';
$string['datatables_paginate_first'] = 'Premier';
$string['datatables_paginate_last'] = 'Dernier';
$string['datatables_paginate_next'] = 'Suivant';
$string['datatables_paginate_previous'] = 'Précédent';
$string['datatables_emptytable'] = 'Aucune donnée disponible dans le tableau';
$string['datatables_aria_sortascending'] = 'activer pour trier la colonne par ordre croissant';
$string['datatables_aria_sortdescending'] = 'activer pour trier la colonne par ordre décroissant';
$string['one_simplest'] = 'un (le plus simple)';
$string['two'] = 'deux';
$string['three'] = 'trois';
$string['four'] = 'quatre';
$string['five'] = 'cinq';
$string['passagepicture'] = 'Image du texte';
$string['passagepicture_descr'] = 'Ajouter une image dans l’en-tête de l’activité.';
$string['stdashboardid'] = 'ID du tableau de bord étudiant';
$string['eventreadaloudattemptsubmitted'] = 'Tentative ReadAloud soumise';
$string['bulkdelete'] = 'Supprimer la sélection';
$string['bulkdeletequestion'] = 'Voulez-vous vraiment supprimer la question sélectionnée ?';
$string['addquestion'] = 'Ajouter une question';
$string['multichoice'] = 'Choix multiple';
$string['multiaudio'] = 'QCM audio';
$string['dictation'] = 'Dictée';
$string['dictationchat'] = 'Chat de dictée';
$string['speechcards'] = 'Cartes orales';
$string['listenrepeat'] = 'Écouter et parler';
$string['page'] = 'Page de contenu';
$string['smartframe'] = 'SmartFrame';
$string['shortanswer'] = 'Réponse courte';
$string['lgapfill'] = 'Texte à trous audio';
$string['sgapfill'] = 'Texte à trous oral';
$string['tgapfill'] = 'Texte à trous écrit';
$string['spacegame'] = 'Jeu spatial';
$string['freewriting'] = 'Expression écrite libre';
$string['freespeaking'] = 'Expression orale libre';
$string['fluency'] = 'Fluidité';
$string['passagereading'] = 'Lecture de texte';
$string['conversation'] = 'Conversation';
$string['pagelayout'] = 'Mise en page';
$string['newitem'] = 'Élément : {$a}';

$string['completiondetail:mingrade'] = 'Note minimale';
$string['completiondetail:allsteps'] = 'Toutes les étapes';
$string['completionallsteps'] = 'Toutes les étapes';
$string['allsteps'] = 'Toutes les étapes';
$string['completionallsteps_help'] = 'Toutes les étapes doivent être terminées pour que l’activité soit considérée comme achevée.';
$string['mingrade_help'] = 'Note minimale ReadAloud (%) requise pour « terminer » cette activité.';
$string['allsteps_help'] = 'Toutes les étapes doivent être terminées pour que l’activité soit considérée comme achevée.';

$string['d_question'] = 'Élément';
$string['freespeaking_instructions1'] = 'Utilisez le microphone pour enregistrer votre réponse à la question.';
$string['freewriting_instructions1'] = 'Saisissez votre réponse à la question dans la zone de texte ci-dessous.';
$string['lg_instructions1'] = 'Instructions du texte à trous audio';
$string['sg_instructions1'] = 'Instructions du texte à trous oral';
$string['tg_instructions1'] = 'Instructions du texte à trous écrit';
$string['multiaudio_instructions1'] = 'Choisissez la bonne réponse. Utilisez le micro pour la lire à voix haute.';
$string['multichoice_instructions1'] = 'Choisissez la bonne réponse.';
$string['shortanswer_instructions1'] = 'Répondez à la question avec le micro.';
$string['iteminstructions'] = 'Instructions de l’élément';
$string['chooselayout'] = 'Choisir la mise en page';
$string['layoutauto'] = 'Automatique';
$string['layoutvertical'] = 'Verticale';
$string['layouthorizontal'] = 'Horizontale';
$string['layoutmagazine'] = 'Magazine';
$string['mediaprompts'] = 'Prompts médias';
// Media toggles.
$string['addmedia'] = 'Image / audio ou vidéo';
$string['addttsaudio'] = 'Audio TTS';
$string['addtextarea'] = 'Bloc de texte';
$string['reallydeletemediaprompt'] = 'Supprimer vraiment ce média : ';
$string['deletemediaprompt'] = 'Supprimer le média ?';
$string['choosemediaprompt'] = 'Choisissez le type de média...';
$string['deletefilesfirst'] = 'Supprimez les fichiers que vous avez ajoutés manuellement. Ils ne seront pas supprimés automatiquement.';
$string['cleartextfirst'] = 'Effacez le contenu que vous avez ajouté manuellement. Il ne sera pas supprimé automatiquement.';

$string['itemmedia'] = 'Image, audio ou vidéo à afficher';
$string['itemttsquestion'] = 'Texte du prompt TTS';
$string['itemttsquestionvoice'] = 'Voix du prompt TTS';
$string['itemtextarea'] = 'Bloc de texte';

// TTS options.
$string['choosevoiceoption'] = 'Options du prompt TTS';
$string['autoplay'] = 'Lecture automatique';
$string['itemsettingsheadings'] = 'Paramètres de l’élément';

$string['enterresponses'] = 'Saisissez la liste des réponses correctes dans la zone de texte ci-dessous. Placez chaque réponse sur une nouvelle ligne.';
$string['correctresponses'] = 'Réponses correctes';
$string['choosevoice'] = 'Choisir la voix du locuteur du prompt';
$string['choosemultiaudiovoice'] = 'Choisir la voix du lecteur de la réponse';
$string['showoptionsastext'] = 'Afficher les réponses sous forme de texte';
$string['showtextprompt'] = 'Afficher le prompt textuel';
$string['textprompt_words'] = 'Afficher le texte complet';
$string['textprompt_dots'] = 'Afficher des points à la place des lettres';
$string['listenorread'] = 'Afficher les options sous forme de';
$string['listenorread_read'] = 'texte simple';
$string['listenorread_listen'] = 'lecteurs audio + points';
$string['listenorread_listenandread'] = 'lecteurs audio + texte simple';
$string['listenorread_image'] = 'images + texte simple';
$string['confirmchoice_formlabel'] = 'Tentative obligatoire (pas de passage possible)';
$string['continue'] = 'Continuer <i class=\'fa fa-arrow-right\'></i>';
$string['confirmchoice'] = 'Vérifier';
$string['listeninggapfill'] = 'Texte à trous audio';
$string['speakinggapfill'] = 'Texte à trous oral';
$string['typinggapfill'] = 'Texte à trous écrit';
$string['gapfillitemsdesc'] = 'Saisissez la liste des éléments dans la zone de texte ci-dessous. Chaque élément doit être sur une nouvelle ligne. Les trous dans les lettres doivent être placés entre crochets : [ ]. Le format est :<br>Prompt textuel | indice<br>Ex. : This is my d[og] | a common pet';
$string['listeninggapfillitemsdesc'] = 'Saisissez la liste des éléments dans la zone de texte ci-dessous. Chaque élément doit être sur une nouvelle ligne. Les trous dans les lettres doivent être placés entre crochets : [ ]. Le format est :<br>Prompt textuel<br>Ex. : This is my d[og]';
$string['readsentences'] = 'Lire les phrases (TTS)';
$string['readsentences_desc'] = 'Si cette option est activée, chaque phrase sera lue à voix haute. Cela prendra la forme d’une dictée.';
$string['allowretry'] = 'Autoriser une nouvelle tentative';
$string['allowretry_desc'] = 'Si cette option est activée, les étudiants peuvent soumettre de nouvelles tentatives si leur réponse précédente était incorrecte.';
$string['hidestartpage'] = 'Masquer la page de démarrage';
$string['hidestartpage_desc'] = 'Si cette option est activée, l’élément de l’activité commence dès son chargement.';
$string['sentenceprompts'] = 'Phrases (prompts)';
$string['relevancetype'] = 'Type de pertinence';
$string['relevancetype_none'] = 'Pertinence non prise en compte';
$string['relevancetype_question'] = 'Pertinence par rapport à la question (texte de l’élément)';
$string['relevancetype_modelanswer'] = 'Pertinence par rapport à une réponse modèle';
$string['freewritingdesc'] = 'Définissez le nombre cible de mots ainsi que les consignes de notation et de feedback pour l’évaluation par IA. Les étudiants doivent saisir leur réponse au sujet et recevront une note et un feedback générés par IA.';
$string['freespeakingdesc'] = '<b>Free Speaking est un type d’élément en BÊTA.</b> Les navigateurs et appareils mobiles peuvent se comporter différemment.<br/><br/>Définissez le nombre cible de mots ainsi que les consignes de notation et de feedback pour l’évaluation par IA. Les étudiants doivent s’enregistrer en parlant du sujet et recevront une note et un feedback générés par IA.';
$string['freespeaking_default_aigrade'] = 'Retirer 1 point pour chaque erreur de grammaire, mais ne pas pénaliser les fautes d’orthographe ni de ponctuation.';
$string['freespeaking_default_aigradefeedback'] = 'Expliquer simplement chaque erreur de grammaire.';
$string['freewriting_default_aigrade'] = 'Retirer 1 point pour chaque erreur de grammaire, d’orthographe ou de ponctuation.';
$string['freewriting_default_aigradefeedback'] = 'Expliquer simplement chaque erreur.';
$string['writehere'] = 'Écrire ici...';
$string['submit'] = 'Envoyer';
$string['fs_totalmarks_instructions'] = 'Nombre total de points que cet élément d’expression orale libre ajoute au score du quiz.';
$string['fw_totalmarks_instructions'] = 'Nombre total de points que cet élément d’expression écrite libre ajoute au score du quiz.';
$string['targetwordcount_title'] = 'Nombre cible de mots';
$string['totalmarks'] = 'Nombre total de points';
$string['aigrade_instructions'] = 'Consignes de notation pour l’IA';
$string['aigrade_feedback'] = 'Consignes de feedback pour l’IA';
$string['aigrade_feedback_language'] = 'Langue du feedback IA';
$string['aigrade_feedback_title'] = 'Feedback';

$string['action'] = 'Action';
$string['order'] = 'Ordre';
$string['deletebuttonlabel'] = 'SUPPRIMER';
$string['totalscore'] = 'Score';
$string['reattempttitle'] = 'Refaire le quiz';
$string['reattemptbody'] = 'Voulez-vous refaire ce quiz ?';
$string['questiontext'] = 'Question';
$string['check'] = 'Vérifier';
$string['skip'] = 'Passer';
$string['start'] = 'Commencer';
$string['score'] = 'Score';
$string['currentwordcount'] = 'Nombre de mots';
$string['showcorrections'] = 'Afficher les corrections intégrées';
$string['hidecorrections'] = 'Masquer les corrections intégrées';
$string['reallyreattempt'] = 'Votre tentative précédente sera remplacée. Voulez-vous vraiment réessayer ?';
$string['answerdetails'] = 'Détails de la réponse';

$string['allowmicaccess'] = 'Veuillez autoriser l’accès à votre microphone.';
$string['nomicdetected'] = 'Aucun microphone détecté.';
$string['speechnotrecognized'] = 'Nous n’avons pas pu reconnaître votre parole.';
$string['gapfill_results'] = 'Résultats';
$string['loading'] = 'Chargement...';
$string['dc_results'] = 'Résultats';

$string['quizsettingsheader'] = 'Paramètres du quiz';
$string['quizscore'] = 'Score du quiz';
$string['showqtitles'] = 'Afficher les titres des questions';
$string['showqtitles_help'] = 'Afficher les titres des questions';
$string['showqreview'] = 'Afficher la révision du quiz';
$string['showqreview_help'] = 'Afficher la révision du quiz';
$string['qfinishscreen'] = 'Écran de fin du quiz';
$string['qfinishscreen_details'] = 'À la fin du quiz, vous pouvez afficher un écran simple, un écran complet ou un écran personnalisé. L’écran personnalisé est une page que vous pouvez concevoir vous-même.';
$string['qfinishscreen_help'] = 'À la fin du quiz, vous pouvez afficher un écran simple, un écran complet ou un écran personnalisé. L’écran personnalisé est une page que vous pouvez concevoir vous-même.';
$string['qfinishscreen_simple'] = 'Simple - score uniquement';
$string['qfinishscreen_full'] = 'Complet - score et détails des questions';
$string['qfinishscreen_custom'] = 'Personnalisé';
$string['qfinishscreencustom'] = 'Écran de fin personnalisé';
$string['qfinishscreencustom_help'] = 'L’écran personnalisé est une fonctionnalité avancée qui permet de créer un écran de fin personnalisé avec la notation Mustache et des variables. Parmi les variables disponibles : {total}, {courseurl}, {coursename}, {yellowstars}, {graystars}, {reattempturl} et un tableau {results}, contenant pour chaque élément les variables {title}, {grade}, {yellowstars} et {graystars}.';

// Modes.
$string['home'] = 'Accueil';
$string['mode_listen'] = 'Écouter';
$string['mode_practice'] = 'S’entraîner';
$string['mode_quiz'] = 'Quiz';
$string['mode_read'] = 'Lire';
$string['mode_shadow'] = 'Shadowing';
$string['mode_report'] = 'Rapport';

$string['next'] = 'Suivant';
$string['prev'] = 'Précédent';
$string['taptospeak'] = 'Touchez pour parler';

$string['enablenativelanguage'] = 'Activer la langue maternelle';
$string['enablenativelanguage_details'] = 'Si cette option est activée, l’étudiant peut choisir sa langue maternelle. Cela remplacera la langue de feedback par défaut utilisée par l’IA pour les résultats des exercices d’expression écrite libre et d’expression orale libre du quiz. La langue doit actuellement être <a href="https://support.poodll.com/en/support/solutions/articles/19000163890-definitions-in-user-s-native-language">configurée dans Poodll WordCards</a>.';
$string['letsadditems'] = 'Ajoutons quelques questions !';
$string['additems'] = 'Ajouter des questions au quiz';
$string['numberonly'] = 'Chiffres uniquement';
$string['aigrade_modelanswer'] = 'Réponse modèle';
$string['enableread'] = 'Activer la lecture';
$string['enablequiz'] = 'Activer le quiz';
$string['activitysteps'] = 'Étapes de l’activité';
$string['activitystepsdetails'] = 'Définissez les étapes d’apprentissage de cette activité ReadAloud.';
$string['error_nosteps'] = 'Au moins une étape doit être activée.';
$string['alternatestreaming'] = 'Activer le streaming alternatif';
$string['alternatestreaming_details'] = 'Diffuse l’audio enregistré pour une transcription ouverte. Légèrement plus lent que la transcription par défaut du navigateur et fonctionne uniquement en anglais. Activé par défaut dans l’application mobile.';
$string['cloudpoodllserver'] = 'Serveur Cloud Poodll';
$string['cloudpoodllserver_details'] = 'Serveur à utiliser pour Cloud Poodll. Ne le modifiez que si Poodll vous en a fourni un autre.';

$string['almost'] = 'Presque...';
$string['almost_desc'] = 'Vous avez mal prononcé certains mots. Voulez-vous réessayer ou continuer ?';
$string['continue'] = 'Continuer';
$string['dontshowtilltheend'] = 'Ne pas afficher avant la fin';
$string['imready'] = 'Je suis prêt';
$string['incorrect'] = 'Incorrect';
$string['incorrect_desc'] = 'Vous ne l’avez pas dit correctement. Voulez-vous réessayer ou continuer ?';
$string['keeplistening'] = 'Continuer à écouter';
$string['keeppracticing'] = 'Continuer à s’entraîner';
$string['listen'] = 'Écouter';
$string['listenorpractice'] = 'Vous pouvez continuer à écouter ou commencer à vous entraîner.';
$string['nextsentence'] = 'Phrase suivante';
$string['noquestions'] = 'Aucune question à afficher.';
$string['practice'] = 'S’entraîner';
$string['practicecomplete'] = 'Super, vous avez terminé la session d’entraînement !';
$string['practicecomplete_desc'] = 'On dirait que vous êtes prêt à lire le texte complet.';
$string['question'] = 'Question ?';
$string['questions'] = 'Questions';
$string['quizresults'] = 'Résultats du quiz';
$string['quiztime'] = 'Moment du quiz';
$string['quiztimehelp'] = 'Faites le quiz pour tester davantage votre compréhension du texte.';
$string['readaloudresults'] = 'Résultats de lecture à voix haute';
$string['readingpassage'] = 'Texte à lire';
$string['readreporthelp'] = 'Consultez vos résultats. Avez-vous bien compris le texte ?';
$string['readreportdummyhelp'] = 'Vos résultats arrivent... veuillez patienter...';
$string['nowevaluatingreading'] = 'Nous évaluons votre lecture... veuillez patienter un instant...';

$string['takethequiz'] = 'Faire le quiz';
$string['timetopractice'] = 'Vous avez terminé l’écoute ?';
$string['tryagain'] = 'Réessayer';
$string['viewfinalreport'] = 'Voir le rapport final';
$string['viewfinalreportintro'] = 'Vos résultats complets et le résumé de votre progression.';
$string['finalreporthelp'] = 'Vos résultats complets et le résumé de votre progression.';
$string['welldone'] = 'Bravo !';
$string['welldone_desc'] = 'Vous avez prononcé tous les mots correctement !';
$string['quitlistening'] = 'Terminer l’écoute';
$string['improveyourscore'] = 'Vous voulez essayer d’améliorer votre score ?';
$string['reallyreattemptquiz'] = 'Refaire le quiz remplacera votre tentative précédente. Voulez-vous vraiment réessayer ?';
$string['quizreattempt'] = 'Peut refaire le quiz';
$string['quizreattempt_help'] = 'Autoriser l’étudiant à refaire le quiz pendant la tentative actuelle.';
$string['readreattempt'] = 'Peut refaire la lecture';
$string['readreattempt_help'] = 'Autoriser l’étudiant à refaire la lecture pendant la tentative actuelle.';

$string['azureapikey_details'] = 'Il s’agit de la clé API utilisée pour les services Azure Speech avec ReadAloud. Elle est facultative. Elle est principalement destinée à nos utilisateurs en Chine continentale. Voir <a href="https://learn.microsoft.com/en-us/azure/cognitive-services/speech-service/overview">ici</a> pour plus d’informations.';
$string['azureapiregion_details'] = 'Il s’agit de la région associée à votre clé API Azure Speech. Si vous n’en avez pas, vous pouvez en créer une depuis le portail Azure.';
$string['machinegrademethod_details'] = 'Utiliser les évaluations automatiques ou humaines comme notes dans le carnet de notes.';
$string['sessionscoremethod_details'] = 'Méthode de calcul de la valeur (%) dans le carnet de notes.';
$string['ttslanguage_details'] = 'Cette valeur est utilisée pour la reconnaissance vocale et la synthèse vocale.';
$string['itemsperpage_details'] = 'Définit le nombre de lignes affichées dans les rapports ou les listes de tentatives.';
$string['stdashboardid_details'] = 'Si le bloc du tableau de bord étudiant est installé, indiquez ici l’ID du bloc.';

// Duplicate strings.
$string['readaloud:view'] = 'Prévisualiser ReadAloud';
$string['readaloud:view'] = 'Voir ReadAloud';
$string['readaloud:itemedit'] = 'Modifier les questions';
$string['readaloud:itemedit'] = 'Modifier les éléments';
$string['readaloud:itemview'] = 'Voir les questions';
$string['readaloud:itemview'] = 'Voir les éléments';
$string['timecreated'] = 'Date de création';
$string['timecreated'] = 'Date de création';
$string['welcomelabel'] = 'Instructions par défaut';
$string['welcomelabel'] = 'Instructions avant la tentative';
$string['feedbacklabel'] = 'Instructions après la tentative';
$string['feedbacklabel'] = 'Feedback par défaut';
$string['nodataavailable'] = 'Aucune donnée disponible';
$string['nodataavailable'] = 'Aucune donnée disponible pour le moment';
$string['transcriber'] = 'Transcripteur ligne par ligne';
$string['transcriber'] = 'Transcripteur';
$string['transcriber_details'] = 'Moteur de transcription à utiliser';
$string['transcriber_details'] = 'Moteur de transcription à utiliser pour la lecture ligne par ligne.';
$string['correct'] = 'Correct';
$string['correct'] = 'Correct';
$string['itemtype'] = 'Type d’élément';
$string['itemtype'] = 'Type d’élément';
$string['deleteitem'] = 'Supprimer l’élément';
$string['deleteitem'] = 'Supprimer l’élément';
$string['guidedtrans_corpus'] = 'Utiliser les textes du corpus';
$string['guidedtrans_corpus'] = 'Utiliser le corpus (tous les textes ReadAloud)';
$string['reattemptquiz'] = 'Refaire le quiz';
$string['reattemptquiz'] = 'Refaire le quiz ?';
$string['addtextarea_instructions'] = 'Saisissez le texte que vous voulez afficher dans l’élément de leçon.';
$string['addttsaudio_instructions'] = 'Saisissez le texte qui doit être lu par le moteur TTS.';
$string['addmedia_instructions'] = 'Choisissez le type de média que vous voulez afficher dans l’élément de leçon.';

// Account dashboard.
$string['accountdashboard'] = 'Tableau de bord du compte';
$string['audio'] = 'Audio';
$string['end'] = 'Expiration';
$string['failedfetchsubreport'] = 'Échec de la récupération du rapport d’abonnement';
$string['maxmonth'] = 'Mois le plus élevé';
$string['ninety_days'] = '90 jours';
$string['no_subscriptions'] = 'Aucun abonnement.';
$string['oneeighty_days'] = '180 jours';
$string['per_plugin'] = 'Par plugin (année précédente)';
$string['per_recording_type'] = 'Par type d’enregistrement';
$string['poodll_users'] = 'Utilisateurs Poodll';
$string['recording_min'] = 'Minutes d’enregistrement';
$string['recordings'] = 'Enregistrements';
$string['start'] = 'Début';
$string['subscription'] = 'Abonnement';
$string['thirty_days'] = '30 jours';
$string['threehundredsixtyfive_days'] = '365 jours';
$string['video'] = 'Vidéo';