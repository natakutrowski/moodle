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
 * English strings for solo
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_solo
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Poodll Solo';
$string['modulenameplural'] = 'Poodll Solo';
$string['modulename_help'] = 'Poodll Solo est une activité conçue pour offrir aux élèves une pratique de l\'expression orale. Les élèves s\'enregistrent en parlant d\'un sujet, transcrivent leur propre discours et reçoivent des retours sur leur travail.';
// $string['solofieldset'] = 'Exemple de champ personnalisé';
$string['soloname'] = 'Poodll Solo';
$string['soloname_help'] = 'Ceci est le contenu de l\'info-bulle d\'aide associée au champ nom de Poodll Solo. La syntaxe Markdown est prise en charge.';
$string['solo'] = 'solo';
$string['activitylink'] = 'Lien vers l\'activité suivante';
$string['activitylink_help'] = 'Pour fournir un lien après la tentative vers une autre activité du cours, sélectionnez l\'activité dans la liste déroulante.';
$string['activitylinkname'] = 'Continuer vers l\'activité suivante : {$a}';
$string['pluginadministration'] = 'Administration de Poodll Solo';
$string['pluginname'] = 'Poodll Solo';
// $string['someadminsetting'] = 'Paramètre administrateur';
// $string['someadminsetting_details'] = 'Plus d\'informations sur le paramètre administrateur';
// $string['someinstancesetting'] = 'Paramètre d\'instance';
// $string['someinstancesetting_details'] = 'Plus d\'informations sur le paramètre d\'instance';
$string['solosettings'] = 'Paramètres Solo';
$string['solo:addinstance'] = 'Ajouter un nouveau Poodll Solo';
$string['solo:view'] = 'Voir Poodll Solo';
$string['solo:viewreports'] = 'Voir les rapports de Poodll Solo';
$string['solo:selecttopics'] = 'Sélectionner des sujets à utiliser dans l\'activité.';
$string['solo:managetopics'] = 'Gérer les sujets (ajouter/éditer/supprimer)';
$string['solo:attemptview'] = 'Voir les tentatives';
$string['solo:attemptedit'] = 'Éditer les tentatives';
$string['solo:manageattempts'] = 'Peut gérer les tentatives de Poodll Solo';
$string['solo:manage'] = 'Peut gérer les instances de Poodll Solo';
$string['solo:submit'] = 'Peut soumettre des tentatives de Poodll Solo';
$string['solo:grades'] = 'Voir les notes de Solo';
$string['privacy:metadata'] = 'Le plugin Poodll Solo enregistre des données personnelles.';
$string['privacy:metadata:solo'] = 'Le plugin Poodll Solo enregistre des données personnelles.';
$string['privacy:metadata:attemptstable'] = 'La table des tentatives de Poodll Solo.';
$string['privacy:metadata:attemptstatstable'] = 'Stocke les statistiques et les données concernant la soumission des élèves.';
$string['privacy:metadata:transcript'] = 'La transcription de la soumission de l\'élève';
$string['privacy:metadata:grade'] = 'La note finale pour la tentative';
$string['privacy:metadata:aigrade'] = 'La note estimée par l\'IA pour la tentative';
$string['privacy:metadata:words'] = 'Nombre total de mots dans la soumission';
$string['privacy:metadata:uniquewords'] = 'Nombre total de mots uniques dans la soumission';
$string['privacy:metadata:longwords'] = 'Nombre total de mots longs dans la soumission';
$string['privacy:metadata:turns'] = 'Nombre total de phrases dans la soumission';
$string['privacy:metadata:avturn'] = 'Longueur moyenne des phrases dans la soumission';
$string['privacy:metadata:longestturn'] = 'Longueur de la phrase la plus longue dans la soumission';
$string['privacy:metadata:targetwords'] = 'Nombre total de mots cibles dans la soumission';
$string['privacy:metadata:totaltargetwords'] = 'Nombre total de mots cibles dans l\'activité';
$string['privacy:metadata:aiaccuracy'] = 'Similarité entre la transcription manuelle et celle générée par l\'IA';
$string['privacy:metadata:cefrlevel'] = 'Niveau CECR estimé';
$string['privacy:metadata:wpm'] = 'Vitesse de parole en mots par minute';
$string['privacy:metadata:speakingtime'] = 'Temps total de parole (en secondes)';
$string['privacy:metadata:relevance'] = 'Pertinence estimée du contenu de la soumission par rapport au sujet';
$string['id'] = 'ID';
$string['name'] = 'Nom';
$string['timecreated'] = 'Date de création';
$string['basicheading'] = 'Rapport de base';
$string['totalattempts'] = 'Tentatives';
$string['overview'] = 'Aperçu';
$string['overview_help'] = 'Aide Aperçu';
$string['view'] = 'Voir';
$string['preview'] = 'Aperçu';
$string['viewreports'] = 'Voir les rapports';
$string['reports'] = 'Mes rapports';
$string['reports'] = 'Rapports';
// $string['viewgrading']='Voir l'évaluation';
$string['showingattempt'] = 'Affichage de la tentative pour : {$a}';
$string['basicreport'] = 'Rapport de base';
$string['returntoreports'] = 'Retour aux rapports';
$string['returntotop'] = 'Retour en haut';
$string['exportexcel'] = 'Exporter en CSV';
$string['deletealluserdata'] = 'Supprimer toutes les données utilisateur';
// $string['maxattempts'] ='Nombre maximum de tentatives';
// $string['unlimited'] ='Illimité';
// $string['defaultsettings'] ='Paramètres par défaut';
// $string['exceededattempts'] ='Vous avez atteint le nombre maximum de {$a} tentatives.';
// $string['solotask'] ='Tâche Poodll Solo';
$string['gotnosound'] = 'Nous ne vous avons pas entendu. Veuillez vérifier les autorisations et les paramètres de votre microphone, puis réessayez.';
$string['done'] = 'Terminé';
$string['submit'] = 'Soumettre';
$string['processing'] = 'Traitement en cours';
$string['feedbackheader'] = 'Terminé';
// $string['beginreading'] = 'Commencer la lecture';
$string['errorheader'] = 'Erreur';
// $string['uploadconverterror'] = 'Une erreur s\'est produite lors de l\'envoi de votre fichier au serveur. Votre soumission n\'a PAS été reçue. Veuillez actualiser la page et réessayer.';
$string['attemptsreport'] = 'Rapport des tentatives';
$string['submitted'] = 'Soumis';
$string['id'] = 'ID';
$string['username'] = 'Utilisateur';
$string['audiofile'] = 'Fichier audio';
$string['timecreated'] = 'Date de création';
$string['nodataavailable'] = 'Aucune donnée disponible pour le moment';
$string['saveandnext'] = 'Enregistrer ... et suivant';
$string['next'] = 'Suivant';
$string['start'] = 'Commencer';
$string['startrecording'] = "Cliquez pour commencer l'enregistrement";
$string['stoprecording'] = "Cliquez à nouveau pour arrêter l'enregistrement";
$string['finish'] = 'Terminer';
$string['done'] = 'Terminé';
$string['reattempt'] = 'Réessayer';
$string['notgradedyet'] = 'Votre soumission a été reçue, mais n\'a pas encore été évaluée';
$string['enabletts'] = 'Activer TTS (expérimental)';
$string['enabletts_details'] = 'TTS n\'est actuellement pas implémenté';
// we hijacked this setting for both TTS STT .... bad ... but they are always the same aren't they?
$string['ttslanguage'] = 'Langue cible';
$string['deleteattemptconfirm'] = 'Êtes-vous sûr de vouloir supprimer cette tentative ?';
$string['deletenow'] = 'Supprimer maintenant';
$string['attemptsperpage'] = 'Tentatives par page';
$string['attemptsperpage_details'] = 'Cela définit le nombre de lignes affichées dans les rapports ou les listes de tentatives.';
$string['gradingsperpage'] = 'Évaluations par page';
$string['gradingsperpage_details'] = 'Cela définit le nombre de tentatives à évaluer manuellement qui seront affichées sur la page d\'évaluation en même temps.';

$string['apiuser'] = 'Utilisateur API Poodll';
$string['apiuser_details'] = 'Le nom d\'utilisateur du compte Poodll qui autorise Poodll sur ce site.';
$string['apisecret'] = 'Clé secrète API Poodll';
$string['apisecret_details'] = 'La clé secrète API Poodll. Voir <a href="https://support.poodll.com/support/solutions/articles/19000083076-cloud-poodll-api-secret">ici</a> pour plus de détails';
$string['enableai'] = 'Activer l\'IA';
$string['enableai_details'] = 'Poodll Solo peut évaluer les résultats d\'une tentative d\'élève en utilisant l\'IA. Cochez pour activer.';

$string['useast1'] = 'US Est';
$string['tokyo'] = 'Tokyo, Japon';
$string['sydney'] = 'Sydney, Australie';
$string['dublin'] = 'Dublin, Irlande';
$string['ottawa'] = 'Ottawa, Canada';
$string['frankfurt'] = 'Francfort, Allemagne';
$string['london'] = 'Londres, Royaume-Uni';
$string['saopaulo'] = 'Sao Paulo, Brésil';
$string['singapore'] = 'Singapour';
$string['mumbai'] = 'Mumbai, Inde';
$string['capetown'] = 'Le Cap, Afrique du Sud';
$string['bahrain'] = 'Bahreïn';

$string['forever'] = 'Ne jamais expirer';

$string['en-us'] = 'Anglais (États-Unis)';
$string['en-nz'] = 'Anglais (Nouvelle-Zélande)';
$string['en-za'] = 'Anglais (Afrique du Sud)';
$string['es-us'] = 'Espagnol (États-Unis)';
$string['en-au'] = 'Anglais (Australie)';
$string['en-gb'] = 'Anglais (Royaume-Uni)';
$string['fr-ca'] = 'Français (Canada)';
$string['fr-fr'] = 'Français (France)';
$string['fil-ph'] = 'Filipino';
$string['it-it'] = 'Italien (Italie)';
$string['pt-br'] = 'Portugais (Brésil)';
$string['en-in'] = 'Anglais (Inde)';
$string['es-es'] = 'Espagnol (Espagne)';
$string['de-de'] = 'Allemand (Allemagne)';
$string['de-at'] = 'Allemand (Autriche)';
$string['da-dk'] = 'Danois (Danemark)';
$string['hi-in'] = 'Hindi';
$string['ko-kr'] = 'Coréen';
$string['ar-ae'] = 'Arabe (Golfe)';
$string['ar-sa'] = 'Arabe (Standard Moderne)';
$string['zh-cn'] = 'Chinois (Mandarin-Mainland)';
$string['nl-nl'] = 'Néerlandais';
$string['nl-be'] = 'Néerlandais (Belgique)';
$string['en-ie'] = 'Anglais (Irlande)';
$string['en-wl'] = 'Anglais (Pays de Galles)';
$string['en-ab'] = 'Anglais (Écosse)';
$string['fa-ir'] = 'Farsi';
$string['de-ch'] = 'Allemand (Suisse)';
$string['he-il'] = 'Hébreu';
$string['id-id'] = 'Indonésien';
$string['ja-jp'] = 'Japonais';
$string['ms-my'] = 'Malaisien';
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
$string['nb-no'] = 'Norvégien (non utilisé)';
$string['pl-pl'] = 'Polonais';
$string['ro-ro'] = 'Roumain';
$string['mi-nz'] = 'Maori';

$string['bg-bg'] = 'Bulgare';
$string['cs-cz'] = 'Tchèque';
$string['el-gr'] = 'Grec';
$string['hr-hr'] = 'Croate';
$string['hu-hu'] = 'Hongrois';
$string['lt-lt'] = 'Lituanien';
$string['lv-lv'] = 'Letton';
$string['sk-sk'] = 'Slovaque';
$string['sl-si'] = 'Slovène';
$string['is-is'] = 'Islandais';
$string['mk-mk'] = 'Macédonien';
$string['no-no'] = 'Norvégien';
$string['sr-rs'] = 'Serbe';
$string['vi-vn'] = 'Vietnamien';

$string['as-in'] = 'Assamais';
$string['aw-aw'] = 'Awadhi';
$string['bn-in'] = 'Bengali';
$string['bh-in'] = 'Bhojpuri';
$string['gu-in'] = 'Gujarati';
$string['kn-in'] = 'Kannada';
$string['ml-in'] = 'Malayalam';
$string['mr-in'] = 'Marathi';
$string['mw-in'] = 'Marwadi';
$string['or-in'] = 'Odia (Oriya)';
$string['pa-ing'] = 'Punjabi (Gurmukhi)';
$string['pa-in'] = 'Punjabi (Shahmukhi)';
$string['sa-in'] = 'Sanskrit';
$string['ur-in'] = 'Ourdou';

$string['awsregion'] = 'Région AWS';
$string['region'] = 'Région AWS';
$string['expiredays'] = 'Jours de conservation du fichier';
// $string['aigradenow']='Évaluation par IA';

$string['attemptsperpage'] = "Tentatives à afficher par page : ";
$string['backtotop'] = "Retour au cours";
$string['transcript'] = "Transcription";
// $string['quickgrade']="Évaluation rapide";
$string['ok'] = "OK";

$string['notimelimit'] = 'Illimité';
$string['xsecs'] = '{$a} secondes';
$string['onemin'] = '1 minute';
$string['xmins'] = '{$a} minutes';
$string['oneminxsecs'] = '1 minute {$a} secondes';
$string['xminsecs'] = '{$a->minutes} minutes {$a->seconds} secondes';

$string['postattemptheader'] = 'Options après tentative';
$string['recordingaiheader'] = 'Options d\'enregistrement et d\'IA';

// $string['grader']='Évalué par';
// $string['grader_ai']='IA';
// $string['grader_human']='Humain';
// $string['grader_ungraded']='Non évalué';

$string['displaysubs'] = '{$a->subscriptionname} : expire le {$a->expiredate}';
$string['noapiuser'] = "Aucun utilisateur API n'a été saisi. Poodll Solo ne fonctionnera pas correctement.";
$string['noapisecret'] = "Aucun secret API n'a été saisi. Poodll Solo ne fonctionnera pas correctement.";
$string['credentialsinvalid'] = "L'utilisateur API et le secret saisis n'ont pas permis d'obtenir un accès. Veuillez les vérifier.";
$string['appauthorised'] = "Poodll Solo est autorisé pour ce site.";
$string['appnotauthorised'] = "Poodll Solo n'est PAS autorisé pour ce site.";
$string['refreshtoken'] = "Actualiser les informations de licence";
$string['notokenincache'] = "Actualisez pour voir les informations de licence. Contactez le support Poodll s'il y a un problème.";
// these errors are displayed on activity page
// $string['nocredentials'] = 'Utilisateur API et secret non saisis. Veuillez les saisir sur <a href="{$a}">la page de paramètres.</a> Vous pouvez les obtenir sur <a href="https://poodll.com/member">Poodll.com.</a>';
$string['novalidcredentials'] = 'L\'utilisateur API et le secret ont été rejetés et n\'ont pas permis d\'obtenir un accès. Veuillez les vérifier sur <a href="{$a}">la page de paramètres.</a> Vous pouvez les obtenir sur <a href="https://poodll.com/member">Poodll.com.</a>';
$string['nosubscriptions'] = "Aucune souscription actuelle pour ce site/plugin.";

$string['privacy:metadata:attemptid'] = 'L\'identifiant unique d\'une tentative Poodll Solo de l\'utilisateur.';
$string['privacy:metadata:attempttable'] = 'Stocke les scores et autres données utilisateur associés à une tentative Poodll Solo.';
$string['privacy:metadata:cloudpoodllcom'] = 'Le plugin Poodll Solo stocke les enregistrements dans des buckets AWS S3 via cloud.poodll.com.';
$string['privacy:metadata:cloudpoodllcom:userid'] = 'Le plugin Poodll Solo inclut l\'ID utilisateur Moodle dans les URLs des enregistrements et des transcriptions.';
$string['privacy:metadata:filename'] = 'URLs des fichiers des enregistrements soumis.';
$string['privacy:metadata:jsontranscriptpurpose'] = 'Les transcriptions complètes des enregistrements.';
$string['privacy:metadata:soloid'] = 'L\'identifiant unique d\'une instance d\'activité Poodll Solo.';
$string['privacy:metadata:timemodified'] = 'Dernière modification de la tentative.';
$string['privacy:metadata:transcriptpurpose'] = 'Les transcriptions courtes des enregistrements.';
$string['privacy:metadata:userid'] = 'L\'ID utilisateur pour la tentative Poodll Solo.';

// Tentatives
// $string['durationgradesettings'] = 'Paramètres de notation';
// $string['durationboundary']='{$a}: Temps d\'achèvement inférieur à (secondes)';
// $string['boundarygrade']='{$a}: points';
// $string['numeric']='Doit être numérique';
// $string['attemptinuse']= 'Cette tentative fait partie de l\'historique des tentatives de l\'utilisateur. Elle ne peut pas être supprimée.';
// $string['moveattemptup']='Haut';
// $string['moveattemptdown']='Bas';

$string['attempts'] = 'Tentatives';
$string['manageattempts'] = 'Gérer les tentatives';
// $string['correctanswer'] ='Réponse correcte';
// $string['addnewattempt'] = 'Ajouter une nouvelle tentative';
// $string['addingattempt'] = 'Ajout d\'une nouvelle tentative';
$string['editingattempt'] = 'Modification d\'une tentative';
// $string['createaattempt'] = 'Créer une tentative';
$string['attempt'] = 'Tentative';
$string['attempttitle'] = 'Titre de la tentative';
$string['attemptcontents'] = 'Description de la tentative';
// $string['answer'] = 'Réponse';
// $string['saveattempt'] = 'Enregistrer la tentative';
// $string['audioattemptfile'] = 'Audio de la tentative (MP3)';
// $string['attemptname'] = 'Nom de la tentative';
// $string['attemptorder'] = 'Ordre de la tentative';
// $string['correct'] = 'Correct';
// $string['attempttype'] = 'Type de tentative';
$string['actions'] = 'Actions';
// $string['editattempt'] = 'Modifier la tentative';
// $string['previewattempt'] = 'Aperçu de la tentative';
$string['deleteattempt'] = 'Supprimer la tentative';
$string['confirmattemptdelete'] = 'Êtes-vous sûr de vouloir <i>SUPPRIMER</i> cette tentative ?';
$string['confirmattemptdeletetitle'] = 'Voulez-vous vraiment supprimer cette tentative ?';
$string['confirmattemptdelete'] = 'Êtes-vous sûr de vouloir <i>SUPPRIMER</i> cette tentative ?';
$string['confirmattemptdeletealltitle'] = 'Voulez-vous vraiment supprimer TOUTES les tentatives ?';
$string['confirmattemptdeleteall'] = 'Êtes-vous sûr de vouloir <i>SUPPRIMER TOUTES</i> les tentatives ?';
$string['noattempts'] = 'Cette activité ne contient aucune tentative';
$string['attemptdetails'] = 'Détails de la tentative : {$a}';
$string['attemptsummary'] = 'Résumé de la tentative : {$a}';
$string['viewreport'] = 'Voir le rapport';

// $string['addrecordconversation'] = 'Enregistrer votre discours';
// $string['adduserselections'] = 'Sélections de l\'utilisateur';
// $string['addselftranscribe'] = 'Transcription manuelle';

// $string['readtext'] = 'Texte à lire';
// $string['language_voice'] = 'Langue et voix';
// $string['listen'] = 'Écouter';
// $string['download'] = 'Télécharger';
// $string['tagarea_solo_attempts'] = 'Tentatives Poodll Solo';
$string['timemodified'] = 'Dernière modification';

// $string['picturechoice'] = 'Choix d\'image';
// $string['translate'] = 'Traduire';
// $string['pictureitemfile'] = 'Fichier d\'élément d\'image';
// $string['iscorrectlabel'] = 'Correct/Incorrect';
// $string['textchoice'] = 'Choix de zone de texte';
// $string['textboxchoice'] = 'Choix de boîte de texte';
// $string['audioresponse'] = 'Réponse audio';
// $string['correcttranslationtitle'] = 'Traduction correcte';
// $string['audiochoice'] = 'Choix audio';
// $string['audioprompt'] = 'Invite audio';
$string['edit'] = 'Modifier';
// $string['gotoactivity'] = 'Commencer l\'activité';
// $string['tryactivityagain'] = 'Réessayer';
// $string['shuffleanswers'] = 'Mélanger les réponses';
// $string['shufflequestions'] = 'Mélanger les questions';
$string['solo:attemptview'] = 'Voir les tentatives';
$string['solo:attemptedit'] = 'Modifier les tentatives';
$string['attemptname'] = 'Tentative';
$string['nodataavailable'] = 'Aucune donnée disponible';
$string['transcriber'] = 'Transcripteur';
$string['transcriber_details'] = 'Le moteur de transcription à utiliser.';
$string['transcriber_open'] = 'Transcription ouverte';
// $string['transcriber_amazontranscribe'] = 'Transcription régulière (AWS)';
// $string['transcriber_amazonstreaming'] = 'Transcription instantanée (AWS)';
// $string['transcriber_googlechrome'] = 'Transcription instantanée (Chrome uniquement)';
// $string['transcriber_googlecloud'] = 'Transcription rapide (Google) (longueur audio < 60s seulement)';
$string['transcriber_none'] = 'Pas de transcription';
$string['transcriptnotready'] = '<i>Transcription non prête</i>';
$string['transcripttitle'] = 'Transcription';

// $string['createattempt'] = 'Créer une tentative';
// $string['addtopic'] = 'Ajouter un sujet';
// $string['deletetopic'] = 'Supprimer le sujet';
// $string['edittopic'] = 'Modifier le sujet';
// $string['editingtopic'] = 'Modification du sujet';
// $string['savetopic'] = 'Enregistrer le sujet';
// $string['createtopic'] = 'Créer un sujet';
$string['topicformtitle'] = 'Ajouter/Modifier un sujet';
$string['topiclevelcustom'] = 'Personnalisé';
$string['topiclevelcourse'] = 'Cours';
$string['grades'] = 'Notes';
$string['managegrades'] = 'Gérer les notes';
$string['topics'] = 'Sujets';
$string['managetopics'] = 'Gérer les sujets';
// $string['topicselected'] = 'Sélectionné';
$string['topicname'] = 'Sujet';
$string['topiclevel'] = 'Niveau';
$string['topicicon'] = 'Icône';
$string['topictargetwords'] = 'Mots cibles';
$string['targetwords'] = 'Mots cibles';
$string['tips'] = 'Conseils de parole';
// $string['targettime'] = 'Temps cible';
$string['targetspeakingtime'] = 'Temps de parole cible';
// $string['type'] = 'Type';
// $string['confirmtopicdelete'] = 'Voulez-vous vraiment supprimer le sujet : {$a} ?';
// $string['choosetopic'] = 'Choisir un sujet';
// $string['topicinstructions']='Ajouter ou modifier des sujets. Les sujets personnalisés seront uniquement disponibles ici. Les sujets au niveau du cours seront disponibles dans tout le cours. Les sujets sélectionnés seront disponibles pour les étudiants dans cette activité.';

$string['userselections'] = 'Sélection de l\'utilisateur';
$string['selftranscribe'] = 'Transcrire votre discours';
// $string['transcriptscompare'] = 'Comparer les transcriptions';
// $string['comparetranscripts'] = 'Comparer les transcriptions';
// $string['saveitem'] = 'Enregistrer';
$string['xminutes'] = '{$a}:00 minutes';
$string['convlength'] = 'Durée cible';
// $string['mywords'] = 'Mes mots cibles';
$string['words'] = 'Mots';
$string['speakingtopic'] = 'Sujet de parole';
$string['speakingtips'] = 'Conseils de parole';
$string['speakingtips_details'] = '';
$string['speakingtips_default'] = 'Parlez simplement, lentement et clairement.';
// $string['chooseusers'] = 'Choisir un partenaire';
$string['users'] = 'Partenaires';
$string['topic'] = 'Sujet';

$string['attempt_prepare'] = 'Préparer';
// $string['attempt_prepare_title'] = 'Préparer à parler';
$string['attempt_record'] = 'Enregistrer';
$string['attempt_transcribe'] = 'Transcrire';
$string['attempt_model'] = 'Modèle de réponse';
// $string['attempt_review'] = 'Revoir';

// $string['step_preparetitle']='Préparer';
$string['step_prepareinstructions'] = 'Vérifiez le sujet de parole et les mots cibles dans les options ci-dessous. Lorsque vous êtes prêt, passez à la page suivante et commencez à parler.';
$string['step_prepareinstructions_norecording'] = 'Vérifiez le sujet et les mots cibles ci-dessous. Lorsque vous êtes prêt, passez à la page suivante et commencez.';
  
// $string['step_mediarecordtitle']='Enregistrer';
$string['step_mediarecordinstructions'] = 'Utilisez l\'enregistreur ci-dessous pour vous enregistrer. Bonne chance.';
$string['step_posttranscriberecordinstructions'] = 'Utilisez l\'enregistreur ci-dessous pour vous enregistrer en train de parler.';

// $string['step_typetitle']='Entrer le texte';
// $string['step_typeinstructions']='Utilisez l\'enregistreur ci-dessous pour vous enregistrer. Essayez d\'utiliser les mots cibles. Bonne chance.';
// $string['step_selftranscribetitle']='Transcrire';
$string['step_selftranscribeinstructions'] = 'Écoutez votre enregistrement et entrez/vérifiez ce que vous avez dit dans l\'éditeur ci-dessous. Ne changez pas ce que vous avez dit. Vous devez corriger les fautes d\'orthographe ou de ponctuation.';
$string['step_prerecord_transcribeinstructions'] = 'Vérifiez les instructions et les mots cibles, puis tapez votre réponse dans l\'éditeur de texte ci-dessous.';
$string['step_writtensubmissioninstructions'] = 'Vérifiez les instructions et les objectifs, puis tapez votre réponse dans l\'éditeur de texte ci-dessous.';

// $string['step_modeltitle']='Réponse Modèle';
$string['step_modelinstructions'] = 'Vérifiez la réponse modèle ci-dessous.';

// $string['savesubtitles'] = 'Enregistrer le discours';
// $string['removesubtitles'] = 'Supprimer le discours';
// $string['addnew'] = 'Ajouter nouveau';
// $string['stepback'] = 'Revenir';
// $string['stepahead'] = 'Avancer';
// $string['playpause'] = 'Lecture/pause';
// $string['now'] = 'Maintenant';
// $string['cancel'] = 'Annuler';
$string['audioreplay'] = 'Écoutez votre audio pour cette tentative';
$string['videoreplay'] = 'Regardez votre vidéo pour cette tentative';
$string['selftranscript'] = 'Transcription personnelle';
// $string['autotranscript'] = 'Transcription automatique';
$string['stats'] = 'Statistiques';
$string['stats_words_title'] = 'Mots';
$string['stats_words'] = 'Total des mots';
$string['stats_turns_title'] = 'Phrases';
$string['stats_turns'] = 'Nombre total de phrases';
$string['stats_avturn'] = 'Longueur moyenne des phrases';
$string['stats_longestturn'] = 'Longueur de la phrase la plus longue';
$string['stats_targetwords'] = 'Mots cibles';
$string['stats_aiaccuracy'] = 'Clarté de la parole';
$string['stats_uniquewords'] = 'Mots uniques';
$string['stats_longwords'] = 'Mots longs';
$string['stats_ideacount'] = 'Concepts';
$string['stats_cefrlevel'] = 'Niveau CECR (estimé)';
$string['stats_relevance'] = 'Pertinence (estimée)'; // AKA similarité
$string['stats_wpm'] = 'Mots par minute';
$string['more_stats'] = 'Plus de statistiques';

// $string['transcripteditor']= 'Éditeur de transcription';
$string['multiattempts'] = 'Autoriser plusieurs tentatives';
$string['multiattempts_details'] = 'Si coché, un étudiant peut choisir de remplacer une tentative existante par une nouvelle.';
$string['attemptsheading'] = 'Tentatives';
$string['incompleteattemptsheading'] = 'Tentatives incomplètes';
$string['incompleteattemptsreport'] = 'Rapport des tentatives incomplètes';
$string['partners'] = 'Partenaires';
$string['turns'] = 'Phrases';
$string['ATL'] = 'Longueur moyenne des phrases';
$string['LTL'] = 'Longueur de la phrase la plus longue';
$string['TW'] = 'Total des mots';
$string['CEFR'] = 'CECR';
$string['idnumber'] = 'Numéro d\'identification';

$string['audiorecording'] = 'Enregistrement audio';
$string['videorecording'] = 'Enregistrement vidéo';
$string['voicerecording'] = 'Enregistrement vocal';
$string['attemptnumberheader'] = 'Numéro de tentative';
$string['attemptnumber'] = '{$a}';
// $string['summaryuserattemptheadertitle']= '(Tentative: {$a}) Détails du discours';
$string['summaryuserattemptheadertitle'] = 'Détails du discours';
$string['summaryuserattemptheaderintro'] = '';
$string['summaryheadertitle'] = 'Détails de votre discours';
$string['summaryheadertitle_norecording'] = 'Résultats de la tentative';
$string['summaryheaderintro'] = 'Vérifiez les détails et les résultats de votre discours ci-dessous. Que pensez-vous ? Vous devriez vous améliorer à chaque fois.';
// $string['leaveedittopic']= 'Modifier (activité différente)';
$string['fonticonexplanation'] = 'Ajoutez une icône graphique pour représenter le sujet. Utilisez FontAwesome pour cela. Le modèle est fa-xxx où xxx est le nom de l\'icône. Recherchez des icônes sur : <a href="https://fontawesome.com/v4.7.0/icons">https://fontawesome.com/v4.7.0/icons</a>';

$string['targetwordsexplanation'] = 'Ajoutez les mots cibles chacun sur une nouvelle ligne.';

// $string['confirmtopicdeletetitle']= 'Confirmer la suppression du sujet :';

$string['maxconvlength_details'] = 'Limite de temps de l\'enregistreur audio';
$string['maxconvlength'] = 'Limite de temps';
$string['heard'] = 'Entendu';
$string['SPL'] = 'Orthographe';
$string['ACC'] = 'Précision';

// $string['notopicsavailable'] = "Aucun sujet n'a été ajouté par votre enseignant.";

$string['postattemptedit'] = 'Peut être modifié après la fin';
$string['postattemptedit_details'] = 'Permettre à l\'étudiant de modifier sa soumission après l\'avoir envoyée.';
$string['dopostattemptedit'] = 'Modifier la dernière tentative';
// $string['audiofilename'] = 'Audio';

// ID, Nom, score AI, phrases, longueur moyenne de phrase
$string['gradesid'] = 'ID';
$string['gradesfirst'] = 'Prénom';
$string['gradeslast'] = 'Nom';
$string['gradesaiscore'] = 'Score AI';
$string['gradesclarity'] = 'Clarté';
$string['gradeswords'] = 'Nombre total de mots';
$string['gradestargetwords'] = 'Mots cibles';
$string['gradesmethod'] = 'Noté par';
$string['gradesturns'] = 'Phrases';
$string['gradesavturnlength'] = 'Longueur moyenne des phrases';
$string['gradesactions'] = 'Actions';
$string['gradesgraded'] = 'Noté';
$string['gradesgradedno'] = 'Non noté';
$string['gradesgradedyes'] = 'Noté';
$string['gradesgrade'] = 'Note';
$string['gradeschoose'] = 'Choisissez une option :';
$string['gradesstudent'] = 'Étudiant';
$string['gradesdatapoint'] = 'Point de données';
$string['gradesrubric'] = 'Rubrique';
$string['gradestranscript'] = 'Transcription';
$string['gradesfeedback'] = 'Retour :';
$string['gradessubmit'] = 'Soumettre';
$string['gradesubmissions'] = 'Soumettre des notes';
$string['gradesgrader'] = 'Noté par';
$string['humangraded'] = 'Enseignant';
$string['autograded'] = 'Automatique';
$string['gradeheader'] = 'Note';
$string['gradelabel'] = '{$a}%';

$string['gradeitem:solo'] = 'Poodll Solo';
$string['developer'] = 'Développeur';
$string['dopopupgrade'] = 'Utilisateur à noter : ';

$string['detailedattemptsreport'] = 'Rapport de recherche';
$string['detailedattemptsheading'] = 'Rapport de recherche';
$string['detailedattempts'] = 'Rapport de recherche';

$string['classprogressreport'] = 'Progrès de la classe';
$string['classprogressheading'] = 'Progrès de la classe';
$string['classprogress'] = 'Progrès de la classe';

$string['myprogressreport'] = 'Mes progrès';
$string['myprogressheading'] = 'Mes progrès : {$a}';
$string['myprogress'] = 'Mes progrès';

$string['userattempts'] = 'Tentatives de l\'utilisateur';
$string['userattemptsheading'] = 'Tentatives de {$a}';
$string['userattempts'] = 'Tentatives de l\'utilisateur';

$string['myattempts'] = 'Mes tentatives';
$string['myattemptsheading'] = 'Mes tentatives : {$a}';
$string['myattempts'] = 'Mes tentatives';

$string['downloadaudio'] = 'Télécharger l\'audio';
$string['downloadaudioheading'] = 'Télécharger l\'audio';
$string['downloadaudioreport'] = 'Télécharger l\'audio';
$string['file'] = 'Fichier';
$string['teachereval'] = 'Évaluation par l\'enseignant';
$string['autoeval'] = 'Évaluation automatique';
$string['spellingeval'] = 'Évaluation de l\'orthographe';
$string['grammareval'] = 'Évaluation de la grammaire';
$string['nogrammarerrors'] = 'Aucune erreur de grammaire.';
$string['possiblegrammarerrors'] = 'Erreurs de grammaire possibles :';
$string['possiblespellingerrors'] = 'Erreurs d\'orthographe possibles :';
$string['nospellingerrors'] = 'Aucune erreur d\'orthographe.';
$string['completedsteps'] = 'Étapes complétées';
$string['completionallsteps'] = 'Terminer lorsque toutes les étapes sont complétées';
$string['completiondetail:allsteps'] = 'Terminez toutes les étapes de l’activité';
$string['completionallsteps_help'] = 'Terminer lorsque toutes les étapes sont complétées';
$string['yes'] = 'Oui';
$string['no'] = 'Non';

$string['speakingtopic_help'] = 'Instructions courtes aux étudiants sur ce dont ils doivent parler.';
$string['targetwords_help'] = 'Mots ou phrases cibles que l\'étudiant doit essayer d\'utiliser en parlant. Chacun sur une nouvelle ligne.';

$string['avturns'] = 'Moyenne de phrases';
$string['avatl'] = 'Longueur moyenne de phrase';
$string['avltl'] = 'Longueur maximale de phrase';
$string['avw'] = 'Moyenne de mots';
$string['GRM'] = 'Grammaire';
$string['avtw'] = 'Moyenne de mots cibles';
$string['avspell'] = 'Moyenne de l\'orthographe';
$string['avacc'] = 'Moyenne de la précision';
$string['tabular'] = 'Vue en tableau';

$string['grade'] = 'Note';

$string['reportmenuinstructions'] = "Afficher les rapports en sélectionnant le rapport parmi les boutons ci-dessous.";
$string['totalgradeables'] = 'Évaluation de {$a} étudiants';

$string['myreports'] = 'Mes Rapports';
$string['stats_autogrammarscore'] = 'Grammaire';
$string['stats_autospellscore'] = 'Orthographe';
$string['stats_clarity'] = 'Clarté';

$string['tnav_grammar'] = 'Grammaire {$a}';
$string['tnav_spelling'] = 'Orthographe {$a}';
$string['tnav_clarity'] = 'Clarté {$a}';

$string['bigword'] = 'Grand mot';
$string['spellingmistake'] = 'Erreur d\'orthographe';
$string['grammarmistake'] = 'Erreur de grammaire';
$string['targetwordspoken'] = 'Mot cible prononcé';
$string['sentence'] = 'Phrase';
$string['aggroup'] = 'Évaluation automatique';
$string['aggroup_help'] = 'Définir la formule utilisée pour évaluer automatiquement la prise de parole des étudiants';

$string['recorderaudio'] = 'Enregistreur audio';
$string['recordervideo'] = 'Enregistreur vidéo';
$string['recorderskin'] = 'Style de l\'enregistreur';
$string['recordertype'] = 'Type d\'enregistrement';

$string['skinplain'] = 'Simple';
$string['skinbmr'] = 'Rose brûlée';
$string['skinfresh'] = 'Frais (audio uniquement)';
$string['skin123'] = 'Un Deux Trois';
$string['skinonce'] = 'Une fois';
$string['skinsolo'] = 'Solo';
$string['skinupload'] = 'Télécharger';

$string['totalunique'] = 'Nombre total de mots uniques';
$string['totalwords'] = 'Nombre total de mots';
$string['gradewordgoal'] = 'Objectif de mots totaux';
$string['gradewordgoal_help'] = 'Définit le nombre de mots que l\'étudiant doit prononcer pour obtenir le maximum de points lors de l\'évaluation automatique. Voir la section d\'évaluation de ce formulaire pour plus de détails.';
$string['displaygradewordgoal'] = '{$a} mots';

$string['ag_overgradewordgoal'] = ' / Objectif de mots ) x ';
$string['ag_pointsper'] = ' points par ';
$string['enabletranscription'] = 'Transcription manuelle';
$string['enabletranscription_details'] = 'Demander aux étudiants de transcrire manuellement leur propre discours';
$string['enableautograde'] = 'Activer l\'évaluation automatique';
$string['enableautograde_details'] = 'L\'évaluation automatique calculera une note préliminaire pour vos étudiants que vous pourrez modifier ou utiliser telle quelle.';
$string['rating_poor'] = 'Merci';
$string['rating_fair'] = 'Merci';
$string['rating_good'] = 'Bon travail';
$string['rating_verygood'] = 'Très bien';
$string['rating_excellent'] = 'Excellent !';
$string['toggleplayinstructions'] = '(Appuyez sur la touche ESC pour démarrer et arrêter le lecteur audio.)';
$string['prerecordtranscriptinstructions'] = 'Entrez votre réponse dans la zone de texte ci-dessous. À l\'étape suivante, vous la lirez à voix haute.';

// nouvelle tentative
$string['reattempt'] = 'Réessayer';
$string['reattempttitle'] = 'Vraiment réessayer ?';
$string['reattemptbody'] = 'Si vous continuez, votre tentative précédente sera remplacée par celle-ci. OK ?';

$string['secs_till_check'] = 'Vérification des résultats dans ... ';
$string['checking'] = ' ... vérification ... ';
$string['notgradedyet'] = 'Votre soumission a été reçue, mais n\'a pas encore été évaluée. Cela peut prendre quelques minutes.';
$string['evaluatedmessage'] = 'Votre dernière tentative a été reçue et l\'évaluation est affichée ci-dessous.';
$string['moreattemptdetails'] = "Plus de détails sur la tentative";
$string['transcriptevaluation'] = "Évaluation de la transcription";
$string['transcriptevaluationdetails'] = "Les mots soulignés montrent les différences entre votre transcription et la transcription automatique.";
$string['uploading'] = ' ... téléchargement en cours ... ';

// options multimédia
$string['mediaoptions'] = 'Options multimédia';
$string['addmedia'] = 'Ajouter un média';
$string['addtext'] = 'Ajouter un texte';
$string['addiframe'] = 'Ajouter un iFrame';
$string['addttsaudio'] = 'Ajouter un audio TTS';
$string['addytclip'] = 'Ajouter un clip YouTube/Vimeo';

$string['speakingtargetsheader'] = 'Objectifs de prise de parole';
$string['languageandrecordingheader'] = 'Langue et Enregistrement';
$string['autogradingheader'] = 'Évaluation automatique';
$string['enablesetuptab'] = 'Activer l\'onglet de configuration';
$string['enablesetuptab_details'] = "Afficher un onglet contenant les paramètres de l'activité pour les administrateurs. Pas très utile dans la plupart des cas.";
$string['setup'] = "Configuration";

$string['nosetup'] = "L'activité n'est pas prête";
$string['addsetup'] = "Configurer l'activité";
$string['waitforsetup'] = "Aucun sujet n'a encore été défini pour cette activité. Vous ne pourrez pas effectuer l'activité tant que votre enseignant n'en aura pas ajouté un.";
$string['letsaddsetup'] = "Aucun sujet n'a encore été défini pour cette activité. Ajoutons-en un.";
$string['noattemptfound'] = "Aucune tentative trouvée";
$string['viewattempt'] = "Voir";
$string['attemptfor'] = 'Tentative : {$a}';
$string['audioandstats'] = "Audio et Statistiques";

$string['content_iframe_help'] = 'Collez le code iframe (html uniquement) pour tout média qui doit être affiché aux étudiants.';
$string['content_media_help'] = 'Téléchargez un fichier audio/vidéo ou une image qui sera affiché aux étudiants.';
$string['content_tts_help'] = 'Contenu de synthèse vocale (TTS).';
$string['content_media'] = 'Contenu image, audio ou vidéo';
$string['content_iframe'] = 'Code iframe intégré';
$string['content_text'] = 'Contenu texte';
$string['content_text_help'] = 'Ajoutez du texte pour accompagner votre sujet';
$string['content_tts'] = 'Texte TTS';
$string['content_ttsvoice'] = 'Voix du locuteur';
$string['content_ttsspeed'] = 'Vitesse du locuteur';
$string['content_ytid'] = "ID Vidéo YouTube";
$string['content_ytstart'] = "Début (en secondes)";
$string['content_ytend'] = "Fin (en secondes)";
$string['ytclipdetails'] = "Clip YouTube/Vimeo";
$string['freetrial'] = "Obtenez les identifiants API Cloud Poodll et un essai gratuit";
$string['freetrial_desc'] = "Une boîte de dialogue devrait apparaître vous permettant de vous inscrire pour un essai gratuit avec Poodll. Après l'inscription, vous devez vous connecter au tableau de bord des membres pour obtenir votre nom d'utilisateur API et votre clé secrète. Et pour enregistrer l'URL de votre site.";
$string['fillcredentials'] = "Définir l'utilisateur API et la clé secrète avec les identifiants existants";
$string['viewstart'] = "Ouverture de l'activité";
$string['viewend'] = "Fermeture de l'activité";
$string['viewstart_help'] = "Si défini, empêche un étudiant d'entrer dans l'activité avant la date/heure de début.";
$string['viewend_help'] = "Si défini, empêche un étudiant d'entrer dans l'activité après la date/heure de clôture.";
$string['activitydate:submissionsdue'] = 'Date limite :';
$string['activitydate:submissionsopen'] = 'Ouverture :';
$string['activitydate:submissionsopened'] = 'Ouvert :';
$string['activityisnotopenyet'] = "Cette activité n'est pas encore ouverte.";
$string['activityisclosed'] = "Cette activité est fermée.";
$string['open'] = "Ouvert : ";
$string['until'] = "Jusqu'à : ";
$string['activityopenscloses'] = "Dates d'ouverture/fermeture de l'activité";
$string['solo:preview'] = 'Peut prévisualiser les activités Solo';
$string['modelanswer'] = "Réponse modèle";
$string['modelanswerheader'] = "Réponse modèle";
$string['modelanswerinstructions'] = "La réponse modèle est utilisée comme une 'bonne réponse', contre laquelle les scores de similarité peuvent être calculés pour l'évaluation automatique. Elle n'est pas montrée aux étudiants. Utilisez les options multimédia ci-dessous pour afficher une vidéo ou un lecteur de synthèse vocale aux étudiants lors de l'étape de la réponse modèle.";
$string['audiorec_heading'] = "Enregistreur audio";
$string['videorec_heading'] = "Enregistreur vidéo";
$string['grammarcorrection'] = "Corrections suggérées :";
$string['step_none'] = 'Aucun';
$string['step_record'] = 'Enregistrer';
$string['step_transcribe'] = 'Transcription manuelle';
$string['step_model'] = 'Modèle';
$string['seq_PRTM'] = 'Préparer -> Enregistrer -> Transcrire -> Modèle (si défini)';
$string['seq_PRMT'] = 'Préparer -> Enregistrer -> Modèle -> Transcription';
$string['seq_PRM'] = 'Préparer -> Enregistrer -> Modèle (si défini)';
$string['seq_PTRM'] = 'Préparer -> Taper -> Enregistrer -> Modèle (si défini)';
$string['seq_PTM'] = 'Préparer -> Taper -> Modèle (si défini)';
$string['seq_RM'] = 'Enregistrer -> Modèle (si défini)';
$string['activitysteps'] = "Étapes de l'activité";
$string['preloadtranscript'] = 'Précharger la transcription';
$string['preloadtranscript_details'] = 'Préchargez la transcription dans l\'éditeur de transcription, afin que l\'étudiant ait juste à l\'éditer. NB : la transcription peut prendre plusieurs minutes pour être disponible.';

$string['enabletts'] = 'Activer TTS';
$string['enabletts_help'] = 'Permettre aux étudiants d\'écouter leur transcription lue à haute voix par une voix TTS';
$string['enabletts_details'] = 'Permettre aux étudiants d\'écouter leur transcription lue à haute voix par une voix TTS';
$string['default_enabletts'] = 'Activer TTS (par défaut)';

$string['nopasting'] = 'Désactiver le copier-coller';
$string['nopasting_help'] = 'Empêcher les utilisateurs de coller du texte dans la zone de transcription/texte.';
$string['nopasting_details'] = 'Empêcher les utilisateurs de coller du texte dans la zone de transcription/texte.';

$string['preloadtranscript'] = 'Précharger la transcription automatique';
$string['preloadtranscript_help'] = 'Cela chargera la transcription automatique de l\'utilisateur dans la zone de transcription manuelle. L\'utilisateur pourra ensuite ajuster les erreurs de transcription.';
$string['preloadtranscript_details'] = 'Cela chargera la transcription automatique de l\'utilisateur dans la zone de transcription manuelle. L\'utilisateur pourra ensuite ajuster les erreurs de transcription.';

$string['enablesuggestions'] = 'Activer les suggestions IA';
$string['enablesuggestions_help'] = 'Permettre à l\'IA de suggérer une version plus correcte de la transcription de l\'étudiant. Les résultats peuvent être imprévisibles. Ceci n\'est actuellement pas lié à l\'évaluation.';
$string['enablesuggestions_details'] = 'Permettre à l\'IA de suggérer une version plus correcte de la transcription de l\'étudiant. Les résultats peuvent être imprévisibles. Ceci n\'est actuellement pas lié à l\'évaluation.';
$string['default_enablesuggestions'] = 'Activer les suggestions IA (par défaut)';

$string['enablegallery'] = 'Activer la galerie';
$string['enablegallery_help'] = 'Permettre aux étudiants d\'écouter les soumissions d\'autres étudiants sur le même sujet';
$string['enablegallery_details'] = 'Permettre aux étudiants d\'écouter les soumissions d\'autres étudiants sur le même sujet';
$string['nosuggestions'] = "Pas de suggestions.";
$string['checkgrammarandspelling'] = 'Vérifier la grammaire et l\'orthographe';
$string['grammarandspellingsuggestions'] = 'Obtenir des suggestions de grammaire et d\'orthographe';
$string['important'] = 'Important';
$string['noemptyselftranscript'] = 'Veuillez entrer quelque chose dans la zone de texte avant de quitter cette page.';
$string['noemptyrecording'] = 'Veuillez enregistrer et télécharger avant de quitter cette page.';
$string['donotwaitfortranscript'] = 'Je ne veux pas attendre la transcription.';

$string['enablelocalpost'] = "Activer l'envoi local";
$string['enablelocalpost_details'] = "C'est un paramètre expérimental pour les utilisateurs en Chine continentale. L'envoi local enverra les enregistrements audio au serveur Moodle, qui les transmettra ensuite à nos serveurs cloud. Cela *pourrait* améliorer la fiabilité pour les utilisateurs ayant des connexions lentes.";

$string['gradeequals'] = 'Note = ';
$string['bonusgrade'] = 'Notes supplémentaires';
$string['relevancegrade'] = 'Évaluation automatique - Similarité/Pertinence';
$string['relevancegrade_help'] = 'La similarité est une mesure générée par l\'IA de la similitude sémantique entre la réponse de l\'étudiant et la réponse modèle. Lorsque le score de similarité de l\'étudiant est inférieur au seuil (x %), sa note globale est réduite proportionnellement. La similarité n\'est calculée que pour les réponses en anglais.';
$string['relevancegrade_details'] = 'Réduire la note des soumissions en anglais dans la mesure où leur similarité sémantique avec la réponse modèle est inférieure au seuil (x %). Si aucune réponse modèle n\'est spécifiée, cela est ignoré.';
$string['relevance_none'] = 'Similarité non prise en compte';
$string['relevance_broad'] = 'Largement similaire (50%)';
$string['relevance_quite'] = 'Assez similaire (70%)';
$string['relevance_very'] = 'Très similaire (80%)';
$string['relevance_extreme'] = 'Extrêmement similaire (90%)';
$string['suggestionsgrade'] = 'Évaluation automatique - Suggestions';
$string['suggestionsgrade_none'] = 'Les suggestions n\'affectent pas la note';
$string['suggestionsgrade_use'] = 'Les corrections suggérées réduisent la note';
$string['suggestionsgrade_details'] = 'Si les corrections suggérées affectent la note, alors la différence en pourcentage entre la transcription et le texte des suggestions réduit la note proportionnellement.';
$string['suggestionsgrade_help'] = 'Si les corrections suggérées affectent la note, alors la différence en pourcentage entre la transcription et le texte des suggestions réduit la note proportionnellement.';
$string['fetching_auto_transcript'] = 'Récupération de la transcription. Veuillez patienter ...';
$string['no_grammar_corrections'] = 'Aucune correction grammaticale. Bravo !';
$string['showcorrections'] = 'Afficher les corrections en ligne';
$string['hidecorrections'] = 'Masquer les corrections en ligne';

$string['pushpage'] = 'Transmettre la page';
$string['pushinstructions'] = 'Sur cette page, vous pouvez sélectionner un paramètre d\'activité, et la valeur de ce paramètre sera transmise à toutes les autres activités de ce cours. Vous pouvez élargir la portée à l\'ensemble du site si vous êtes administrateur. Vous pouvez aussi la restreindre aux activités portant le même nom. <br> <b>Faites très très attention</b> il n\'y a pas de confirmation. La transmission se fera immédiatement lorsque vous appuierez sur enregistrer. Soyez sûr de vouloir le faire.';
$string['pushformheading'] = 'Transmettre les paramètres aux autres activités';
$string['pushaction'] = 'Paramètre à transmettre';
$string['pushsitelevel'] = 'Appliquer à l\'échelle du site (sinon à l\'échelle du cours)';
$string['pushsamename'] = 'Appliquer uniquement aux activités portant le même nom';
$string['pushdone'] = 'Transmission terminée. {$a} enregistrements mis à jour.';
$string['eventsolostepsubmitted'] = 'Étape Solo soumise';
$string['eventsoloattemptsubmitted'] = 'Tentative Solo soumise';
$string['eventsoloattemptautograded'] = 'Tentative Solo évaluée automatiquement';
$string['layout'] = 'Disposition';
$string['layout_standard'] = 'Standard';
$string['layout_narrow'] = 'Étroit';
$string['showgrammar'] = "Afficher l'évaluation grammaticale";
$string['showspelling'] = "Afficher l'évaluation orthographique";
$string['showopts_no'] = "Ne pas afficher";
$string['showopts_yes'] = "Afficher";

$string['ttsspeed'] = 'Vitesse TTS';
$string['mediumspeed'] = 'Moyenne';
$string['slowspeed'] = 'Lente';
$string['extraslowspeed'] = 'Très lente';
$string['modelanswer_help'] = 'Entrez une réponse modèle complète et correcte pour le sujet. Elle sera utilisée dans le processus d\'évaluation.';
$string['backtotranscriptedit'] = "Retour à l'édition";
$string['waitingforteacher'] = "Votre enseignant vérifiera votre tentative. Merci";
$string['gradesdate'] = 'Date';

$string['prompttester'] = 'Testeur de notation IA';
$string['prompttester_help'] = 'Utilisez ceci pour tester l\'évaluation par IA. Entrez une réponse exemple et voyez comment elle est évaluée.';
$string['sampleanswerempty'] = 'Pour tester l\'évaluation IA, vous devez entrer une réponse exemple.';
$string['sampleanswerevaluate'] = 'Évaluer';
$string['sampleanswer'] = 'Réponse Exemple';
$string['sampleanswerinstructions'] = 'La réponse exemple est utilisée pour vous aider à tester l\'évaluation IA et les instructions de feedback dans la section d\'évaluation automatique ci-dessus. Entrez une réponse qui ressemble à une réponse réelle d\'étudiant, et appuyez sur \'Évaluer\' pour voir comment l\'IA réagit.';
$string['sampleanswer_help'] = 'Entrez une réponse qui ressemble à une réponse réelle d\'étudiant, et voyez comment l\'IA la traite.';
$string['markscheme'] = 'Instructions de notation IA';
$string['markscheme_help'] = 'Instructions à l\'IA sur la manière d\'évaluer la réponse de l\'étudiant.';
$string['feedbackscheme'] = 'Instructions de feedback IA';
$string['feedbackscheme_help'] = 'Instructions à l\'IA sur la manière de fournir un feedback sur la réponse de l\'étudiant.';
$string['feedbacklanguage'] = 'Langue du feedback IA';
$string['stats_aigrade'] = 'Note IA';
$string['relevance_model'] = 'Pertinence - Similarité avec la réponse modèle';
$string['relevance_question'] = 'Pertinence - Par rapport au sujet de la question';

$string['aifeedback'] = 'Feedback IA :';
$string['autogradelog'] = 'Journal de l\'évaluation automatique';
$string['yourtranscript'] = 'Votre transcription :';
$string['estimated'] = 'estimé';
$string['ideacount'] = 'Idée/concept';
$string['aigradepreviewheader'] = 'Aperçu de la note IA';
$string['showttspassage'] = 'Lire le passage à haute voix';
$string['resultsdisplay'] = 'Affichage des résultats';
$string['starrating_use'] = 'Évaluation par étoiles (5 étoiles)';
$string['starrating_none'] = 'Pourcentage + diagramme en beignet';
$string['starrating'] = 'Évaluation par étoiles';
$string['starrating_help'] = 'Utiliser un système de notation par étoiles (5 étoiles) pour l\'évaluation.';
$string['leveltypes'] = 'Niveaux estimés';
$string['leveltypes_help'] = 'Ce paramètre affecte les niveaux de certification affichés à l\'écran. Les niveaux IELTS et TOEFL ne s\'affichent que si l\'anglais est défini comme langue cible.';
$string['showcefrlevel'] = 'Niveau CECR';
$string['showieltslevel'] = 'Niveau IELTS';
$string['showtoefllevel'] = 'Niveau TOEFL';
$string['showgenericlevel'] = 'Niveau générique';
$string['beginner'] = 'Débutant';
$string['intermediate'] = 'Intermédiaire';
$string['highintermediate'] = 'Intermédiaire supérieur';
$string['lowadvanced'] = 'Avancé faible';
$string['advanced'] = 'Avancé';
$string['upperadvanced'] = 'Avancé supérieur';
$string['stats_ieltslevel'] = 'Niveau IELTS';
$string['stats_toefllevel'] = 'Niveau TOEFL';
$string['stats_genericlevel'] = 'Niveau de langue';
$string['enablenativelanguage'] = "Activer la langue maternelle";
$string['enablenativelanguage_details'] = 'Si activé, l\'étudiant peut choisir sa langue maternelle, ce qui remplacera la langue par défaut du feedback fourni par l\'IA. La langue doit actuellement être <a href="https://support.poodll.com/en/support/solutions/articles/19000163890-definitions-in-user-s-native-language">définie dans Poodll WordCards</a>, et elle est détectée ici.';
$string['teacherfeedback'] = 'Feedback de l\'enseignant';


$string['cloudpoodllserver'] = 'Serveur Cloud Poodll';
$string['cloudpoodllserver_details'] = 'Le serveur à utiliser pour Cloud Poodll. Ne le modifiez que si Poodll vous en a fourni un autre.';
$string['ningxia'] = 'Ningxia, Chine';