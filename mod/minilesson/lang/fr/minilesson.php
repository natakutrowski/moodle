<?php
/**
 * English strings for Mini Lesson
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_minilesson
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Leçon Mini Poodll';
$string['modulenameplural'] = 'Leçons Mini Poodll';
$string['modulename_help'] = 'Poodll MiniLesson combine plusieurs activités d’apprentissage des langues dans une leçon en ligne simple et autonome.

Une activité MiniLesson se compose d’une séquence d’éléments de leçon. Les types d’éléments incluent :

* Choix multiple  
* Écouter et parler  
* Texte à trous (écrit/oral/écoute)  
* Texte à trous sur un passage  
* Discussion audio  
* Expression orale ou écrite libre';

$string['minilessonname'] = 'Poodll MiniLesson';

$string['minilessonname_help'] = 'Nom de la MiniLesson';
$string['minilesson'] = 'Leçon Mini Poodll';
$string['activitylink'] = 'Lien vers l\'activité suivante';
$string['activitylink_help'] = 'Pour fournir un lien après la tentative vers une autre activité du cours, sélectionnez l\'activité dans la liste déroulante.';
$string['activitylinkname'] = 'Continuer vers l\'activité suivante : {$a}';
$string['pluginadministration'] = 'Administration de la Leçon Mini';
$string['pluginname'] = 'Leçon Mini Poodll';
//$string['someadminsetting'] = 'Paramètre d\'administration';
//$string['someadminsetting_details'] = 'Plus d\'informations sur le Paramètre d\'administration';
//$string['someinstancesetting'] = 'Paramètre d\'instance';
//$string['someinstancesetting_details'] = 'Plus d\'informations sur le Paramètre d\'instance';
//$string['minilessonsettings'] = 'Paramètres de la Leçon Mini';
$string['minilesson:addinstance'] = 'Ajouter une nouvelle Leçon Mini';
$string['minilesson:view'] = 'Voir la Leçon Mini';
$string['minilesson:view'] = 'Aperçu de la Leçon Mini';
$string['minilesson:itemview'] = 'Voir les éléments de la leçon';
$string['minilesson:itemedit'] = 'Modifier les éléments de la leçon';
$string['minilesson:tts'] = 'Peut utiliser la Synthèse Vocale (tts)';
$string['minilesson:managequestions'] = 'Peut gérer les éléments de la leçon';
$string['minilesson:export'] = 'Peut exporter les éléments de la leçon';
$string['minilesson:canmanageattempts'] = 'Peut gérer les tentatives de Leçon Mini';
$string['minilesson:manage'] = 'Peut gérer les instances de Leçon Mini';
$string['minilesson:canpreview'] = 'Peut prévisualiser les activités de Leçon Mini';
$string['minilesson:evaluate'] = 'Peut évaluer les tentatives des élèves sur la Leçon Mini';
$string['minilesson:submit'] = 'Peut soumettre des tentatives de Leçon Mini';
$string['minilesson:push'] = 'Appliquer les paramètres d\'une instance aux autres';

$string['id']='ID';
$string['name']='Nom';
$string['timecreated']='Date de création';
$string['basicheading']='Rapport de base';
$string['attemptsheading']='Rapport des tentatives';
$string['incompleteattemptsheading']='Rapport des tentatives incomplètes';
$string['gradereport']='Rapport de notes';
$string['gradereport_explanation']='Une liste de notes';
$string['gradereportheading']='Rapport de notes';
//$string['attemptsbyuserheading']='Rapport des tentatives par utilisateur';
$string['gradingheading']='Notes pour chaque utilisateur - dernières tentatives.';
$string['gradingbyuserheading']='Notes pour toutes les tentatives par : {$a}';
$string['totalattempts']='Tentatives';
$string['overview']='Aperçu';
$string['overview_help']='Aide sur l\'aperçu';
$string['view']='Voir';
$string['preview']='Aperçu';
$string['viewreports']='Voir les rapports';
$string['reports']='Rapports';
$string['viewgrading']='Voir les notes';
$string['grading']='Notes';
$string['showingattempt']='Affichage de la tentative pour : {$a}';
$string['showingmachinegradedattempt']='Tentative évaluée automatiquement pour : {$a}';
$string['basicreport']='Rapport de base';
$string['basicreport_explanation']='Un rapport de base';

$string['returntoreports']='Retour aux rapports';
$string['returntogradinghome']='Retour au tableau des notes';
$string['exportexcel']='Exporter en CSV';
$string['deletealluserdata'] = 'Supprimer toutes les données utilisateur';
$string['maxattempts'] ='Nombre maximum de tentatives';
$string['maxattempts_details'] ='Nombre maximum de tentatives autorisées pour cette activité.';
$string['unlimited'] ='illimité';
$string['gradeoptions'] ='Options de notation';
$string['gradenone'] ='Aucune note';
$string['gradelowest'] ='tentative avec le score le plus bas';
$string['gradehighest'] ='tentative avec le score le plus élevé';
$string['gradelatest'] ='score de la dernière tentative';
$string['gradeaverage'] ='score moyen de toutes les tentatives';
//$string['defaultsettings'] ='Paramètres par défaut';
$string['exceededattempts'] ='Vous avez atteint le nombre maximum de {$a} tentatives.';
//$string['minilessontask'] ='Tâche de Leçon Mini';
$string['welcomelabel'] ='Message de bienvenue';
$string['welcomelabel_details'] ='Le texte par défaut à afficher dans le champ de bienvenue lors de la création d\'une nouvelle activité de Leçon Mini.';
//$string['feedbacklabel'] ='Retour par défaut';
//$string['feedbacklabel_details'] ='Le texte par défaut à afficher dans le champ de retour lors de la création d\'une nouvelle activité de Leçon Mini.';
$string['welcomelabel'] = 'Message de bienvenue';
//$string['feedbacklabel'] = 'Message de retour';
$string['alternatives']='Alternatives';
$string['alternatives_descr']='Spécifiez les options correspondantes pour certains mots du passage. 1 ensemble de mots par ligne. Par exemple: leur|là|leurs. Voir <a href="https://support.poodll.com/support/solutions/articles/19000096937-tuning-your-read-aloud-activity">la documentation</a> pour plus de détails.';
//$string['defaultwelcome'] = 'Pour commencer l\'activité, testez d\'abord votre microphone. Lorsque nous pouvons entendre le son de votre microphone, un bouton de démarrage apparaîtra. Après avoir appuyé sur le bouton de démarrage, un passage de lecture apparaîtra. Lisez le passage à voix haute aussi clairement que possible.';
//$string['defaultfeedback'] = 'Merci d\'avoir lu. Veuillez patienter jusqu\'à ce que votre tentative soit évaluée.';
$string['timelimit'] = 'Limite de temps';
//$string['gotnosound'] = 'Nous ne vous entendons pas. Veuillez vérifier les permissions et les paramètres de votre microphone et réessayer.';
//$string['done'] = 'Terminé';
$string['processing'] = 'Traitement en cours';
//$string['feedbackheader'] = 'Terminé';
//$string['beginreading'] = 'Commencer la lecture';
$string['errorheader'] = 'Erreur';
//$string['uploadconverterror'] = 'Une erreur est survenue lors de l\'envoi de votre fichier au serveur. Votre soumission n\'a PAS été reçue. Veuillez actualiser la page et réessayer.';
$string['attemptsreport'] = 'Rapport des tentatives';
$string['attemptsreport_explanation']='Une liste des tentatives';
$string['incompleteattemptsreport'] = 'Rapport des tentatives incomplètes';
$string['incompleteattemptsreport_explanation']='Une liste des tentatives incomplètes';
//$string['submitted'] = 'soumis';
$string['id'] = 'ID';
$string['username'] = 'Utilisateur';
//$string['audiofile'] = 'Audio';
$string['timecreated'] = 'Date de création';
$string['nodataavailable'] = 'Aucune donnée disponible pour le moment';
$string['saveandnext'] = 'Enregistrer .... et suivant';
//$string['notgradedyet'] = 'Votre soumission a été reçue, mais n\'a pas encore été notée.';
//$string['enabletts'] = 'Activer la synthèse vocale (expérimental)';
//$string['enabletts_details'] = 'La synthèse vocale n\'est actuellement pas implémentée';
//we hijacked this setting for both TTS STT .... bad ... but they are always the same aren't they?
$string['ttslanguage'] = 'Langue cible/Voix';
$string['deleteattemptconfirm'] = "Êtes-vous sûr de vouloir supprimer cette tentative ?";
$string['deletenow']='Supprimer maintenant';
$string['itemsperpage']='Éléments par page';
$string['itemsperpage_details']='Cela définit le nombre de lignes à afficher dans les rapports ou les listes de tentatives.';
$string['mistakes']='Erreurs';
$string['grade']='Note';
$string['grade_p']='Note (%)';
$string['quiz_p']='Quiz (%)';
$string['quizanswers']='Réponses';

$string['apiuser']='Utilisateur de l\'API Poodll';
$string['apiuser_details']='Le nom d\'utilisateur du compte Poodll qui autorise Poodll sur ce site.';
$string['apisecret']='Secret de l\'API Poodll';
$string['apisecret_details']='Le secret de l\'API Poodll. Voir <a href= "https://support.poodll.com/support/solutions/articles/19000083076-cloud-poodll-api-secret">ici</a> pour plus de détails';


$string['useast1'] = 'États-Unis Est';
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

//$string['forever'] = 'Ne jamais expirer';

$string['en-us'] = 'Anglais (États-Unis)';
$string['es-us'] = 'Espagnol (États-Unis)';
$string['en-au'] = 'Anglais (Australie)';
$string['en-nz'] = 'Anglais (Nouvelle-Zélande)';
$string['en-za'] = 'Anglais (Afrique du Sud)';
$string['en-gb'] = 'Anglais (Royaume-Uni)';
$string['fr-ca'] = 'Français (Canada)';
$string['fr-fr'] = 'Français (France)';
$string['it-it'] = 'Italien (Italie)';
$string['pt-br'] = 'Portugais (Brésil)';
$string['en-in'] = 'Anglais (Inde)';
$string['es-es'] = 'Espagnol (Espagne)';
$string['fil-ph'] = 'Filipino';
$string['de-de'] = 'Allemand (Allemagne)';
$string['de-ch'] = 'Allemand (Suisse)';
$string['de-at'] = 'Allemand (Autriche)';
$string['da-dk'] = 'Danois (Danemark)';
$string['hi-in'] = 'Hindi';
$string['ko-kr'] = 'Coréen';
$string['ar-ae'] = 'Arabe (Golfe)';
$string['ar-sa'] = 'Arabe (Standard Moderne)';
$string['zh-cn'] = 'Chinois (Mandarin - Chine continentale)';
$string['nl-nl'] = 'Néerlandais (Pays-Bas)';
$string['nl-be'] = 'Néerlandais (Belgique)';
$string['en-ie'] = 'Anglais (Irlande)';
$string['en-wl'] = 'Anglais (Pays de Galles)';
$string['en-ab'] = 'Anglais (Écosse)';
$string['he-il'] = 'Hébreu';
$string['id-id'] = 'Indonésien';
$string['ja-jp'] = 'Japonais';
$string['ms-my'] = 'Malais';
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
$string['nb-no'] = 'Norvégien (Bokmål)';
$string['nn-no'] = 'Norvégien (Nynorsk)';
$string['pl-pl'] = 'Polonais';
$string['ro-ro'] = 'Roumain';
$string['mi-nz'] = 'Maori';

$string['bg-bg'] = 'Bulgare'; // Bulgare
$string['cs-cz'] = 'Tchèque'; // Tchèque
$string['el-gr'] = 'Grec'; // Grec
$string['hr-hr'] = 'Croate'; // Croate
$string['hu-hu'] = 'Hongrois'; // Hongrois
$string['lt-lt'] = 'Lituanien'; // Lituanien
$string['lv-lv'] = 'Letton'; // Letton
$string['sk-sk'] = 'Slovaque'; // Slovaque
$string['sl-si'] = 'Slovène'; // Slovène
$string['is-is'] = 'Islandais'; // Islandais
$string['mk-mk'] = 'Macédonien'; // Macédonien
$string['no-no'] = 'Norvégien'; // Norvégien
$string['sr-rs'] = 'Serbe'; // Serbe
$string['vi-vn'] = 'Vietnamien'; // Vietnamien

$string['awsregion']='Région AWS';
$string['region']='Région AWS';
//$string['expiredays']='Jours pour conserver le fichier';

//$string['machinegrading']='Évaluations automatiques';
//$string['viewmachinegrading']='Évaluation automatique';
$string['review']='Revoir';
$string['regrade']='Réévaluer';

//$string['humanevaluatedmessage']='Votre dernière tentative a été notée par votre enseignant et les résultats sont affichés ci-dessous.';
//$string['machineevaluatedmessage']='Votre dernière tentative a été notée <i>automatiquement</i> et les résultats sont affichés ci-dessous.';

//$string['dospotcheck']="Vérification aléatoire";
//$string['spotcheckbutton']="Notation rapide";
//$string['gradingbutton']="Notation manuelle";
//$string['transcriptcheckbutton']="Vérification de la transcription";
//$string['doclear']="Effacer tous les marqueurs";

//$string['gradethisattempt']="Noter cette tentative";
$string['rawgrade_p']='Note brute (%)';
$string['adjustedgrade_p']='Note ajustée (%)';

//$string['evaluationview']="Affichage de l'évaluation";
//$string['evaluationview_details']="Ce qui doit être affiché aux étudiants après qu'ils aient tenté l'activité et reçu une évaluation";
//$string['humanpostattempt']="Affichage de l'évaluation (humaine)";
//$string['humanpostattempt_details']="Ce qui doit être affiché aux étudiants après qu'ils aient tenté l'activité et reçu une évaluation humaine";
//$string['machinepostattempt']="Affichage de l'évaluation (automatique)";
//$string['machinepostattempt_details']="Ce qui doit être affiché aux étudiants après qu'ils aient tenté l'activité et reçu une évaluation automatique";
//$string['postattempt_none']="Afficher le passage. Ne pas afficher l'évaluation ou les erreurs.";
//$string['postattempt_eval']="Afficher le passage, et l'évaluation (scores)";
//$string['postattempt_evalerrors']="Afficher le passage, l'évaluation (scores) et les erreurs";
$string['attemptsperpage']="Tentatives à afficher par page : ";
$string['backtotop']="Retour à la page du cours";
//$string['transcript']="Transcription";
//$string['quickgrade']="Notation rapide";
//$string['ok']="OK";
//$string['ng']="Pas OK";
//$string['notok']="Pas OK";
//$string['machinegrademethod']="Évaluation humaine/automatique";
//$string['machinegrademethod_help']="Utiliser les évaluations automatiques ou humaines comme notes dans le livre de notes.";
//$string['machinegradenone']="Ne jamais utiliser l'évaluation automatique pour la notation";
//$string['machinegrademachine']="Utiliser l'évaluation humaine ou automatique pour la notation";

//$string['noattemptsregrade']='Aucune tentative à réévaluer';
//$string['machineregraded']='{$a->done} tentatives réévaluées avec succès. {$a->skipped} tentatives ignorées.';
//$string['machinegradespushed']='Notes envoyées au livre de notes avec succès';

$string['notimelimit']='Aucune limite de temps';
$string['xsecs']='{$a} secondes';
$string['onemin']='1 minute';
$string['xmins']='{$a} minutes';
$string['oneminxsecs']='1 minute {$a} secondes';
$string['xminsecs']='{$a->minutes} minutes {$a->seconds} secondes';

$string['postattemptheader']='Options après la tentative';
$string['recordingaiheader']='Options d\'enregistrement et d\'IA';

$string['displaysubs'] = '{$a->subscriptionname} : expire le {$a->expiredate}';
$string['noapiuser'] = "Aucun utilisateur d'API n'a été saisi. MiniLesson ne fonctionnera pas correctement.";
$string['noapisecret'] = "Aucun secret d'API n'a été saisi. MiniLesson ne fonctionnera pas correctement.";
$string['credentialsinvalid'] = "L'utilisateur d'API et le secret saisis n'ont pas permis d'accéder. Veuillez les vérifier.";
$string['appauthorised']= "Poodll MiniLesson est autorisé pour ce site.";
$string['appnotauthorised']= "Poodll MiniLesson N'EST PAS autorisé pour ce site.";
$string['refreshtoken']= "Actualiser les informations de licence";
$string['notokenincache']= "Actualisez pour voir les informations de licence. Contactez le support Poodll en cas de problème.";
//these errors are displayed on activity page
$string['nocredentials'] = 'L\'utilisateur d\'API et le secret n\'ont pas été saisis. Veuillez les entrer sur <a href="{$a}">la page des paramètres.</a> Vous pouvez les obtenir sur <a href="https://poodll.com/member">Poodll.com.</a>';
$string['novalidcredentials'] = 'L\'utilisateur d\'API et le secret ont été rejetés et n\'ont pas permis d\'accéder. Veuillez les vérifier sur <a href="{$a}">la page des paramètres.</a> Vous pouvez les obtenir sur <a href="https://poodll.com/member">Poodll.com.</a>';
$string['nosubscriptions'] = "Il n'y a pas d'abonnement en cours pour ce site/plugin.";

$string['privacy:metadata:attemptid']='L\'identifiant unique de la tentative d\'un utilisateur pour une Leçon Mini.';
$string['privacy:metadata:minilessonid']='L\'identifiant unique d\'une instance d\'activité Leçon Mini.';
$string['privacy:metadata:userid']='L\'identifiant utilisateur pour la tentative de Leçon Mini';
$string['privacy:metadata:sessionscore']='Le score de la session pour la tentative';
$string['privacy:metadata:sessiontime']='Le temps de session (temps d\'enregistrement) pour la tentative';
$string['privacy:metadata:sessiondata']='Les données de session pour la tentative';
$string['privacy:metadata:sessionend']='La fin de session pour la tentative';
$string['privacy:metadata:timemodified']='La dernière fois que la tentative a été modifiée';
$string['privacy:metadata:attempttable']='Stocke les scores et autres données utilisateur associées à une tentative de Leçon Mini.';
$string['privacy:metadata:transcriptpurpose']='Les transcriptions courtes des enregistrements.';
$string['privacy:metadata:fulltranscriptpurpose']='Les transcriptions complètes des enregistrements.';
$string['privacy:metadata:cloudpoodllcom:userid']='Le plugin Leçon Mini inclut l\'identifiant utilisateur Moodle dans les URL des enregistrements et des transcriptions';
$string['privacy:metadata:cloudpoodllcom']='Le plugin Leçon Mini stocke les enregistrements dans des buckets AWS S3 via cloud.poodll.com.';
$string['privacy:metadata'] = 'Le plugin Poodll MiniLesson stocke des données personnelles.';
$string['privacy:metadata:moduleid'] = 'L\'identifiant unique d\'une instance d\'activité Leçon Mini.';
$string['privacy:metadata:errorcount']='Le nombre d\'erreurs d\'une tentative de Leçon Mini par un utilisateur.';

//rsquestions
$string['rsquestions'] ='Éléments de la leçon';
$string['managersquestions'] ='Gérer les éléments de la leçon';
$string['correctanswer'] ='Réponse correcte';
$string['incorrectanswer'] ='Réponse incorrecte';
$string['whatdonow'] = 'Ajouter des éléments de leçon à l\'activité :';
$string['addnewitem'] = 'Ajouter un nouvel élément de leçon';
$string['addingitem'] = 'Ajout d\'un nouvel élément {$a}';
$string['editingitem'] = 'Modification d\'un élément {$a}';
$string['createaitem'] = 'Créer un élément de leçon';
$string['item'] = 'Élément';
$string['newitem'] = 'Élément : {$a}';
$string['itemtitle'] = 'Titre de l\'élément';
$string['itemcontents'] = 'Texte/Question de l\'élément';
$string['answer'] = 'Réponse';
$string['saveitem'] = 'Enregistrer l\'élément';
$string['audioitemfile'] = 'Audio de l\'élément (MP3)';
$string['itemname'] = 'Nom de l\'élément';
$string['itemorder'] = 'Ordre de l\'élément';
$string['correct'] = 'Correct';
$string['itemtype'] = 'Type d\'élément';
$string['actions'] = 'Actions';
$string['edititem'] = 'Modifier l\'élément';
$string['previewitem'] = 'Aperçu de l\'élément';
$string['duplicateitem'] = 'Dupliquer l\'élément';
$string['deleteitem'] = 'Supprimer l\'élément';
$string['confirmitemdelete'] = 'Êtes-vous sûr de vouloir <i>SUPPRIMER</i> l\'élément ? : {$a}';
$string['confirmitemdeletetitle'] = 'Vraiment supprimer l\'élément ?';
$string['confirmattemptdeletetitle'] = 'Vraiment supprimer la tentative ?';
$string['confirmattemptdelete'] = 'Êtes-vous sûr de vouloir <i>SUPPRIMER</i> cette tentative ?';
$string['confirmattemptdeletealltitle'] = 'Vraiment supprimer TOUTES les tentatives ?';
$string['confirmattemptdeleteall'] = 'Êtes-vous sûr de vouloir <i>SUPPRIMER TOUTES</i> les tentatives ?';
$string['noitems'] = 'Cette Leçon Mini ne contient aucun élément de leçon';
//$string['itemdetails'] = 'Détails de l\'élément : {$a}';
//$string['itemsummary'] = 'Résumé de l\'élément : {$a}';
//$string['viewreport'] = 'Voir le rapport';
//$string['translate'] = 'Traduire';
//$string['iscorrectlabel'] = 'Correct/Incorrect';
//$string['correcttranslationtitle'] = 'Traduction correcte';
$string['edit'] = 'Modifier';
//$string['gotoactivity'] = 'Commencer l\'activité';
//$string['tryactivityagain'] = 'Réessayer';
//$string['shuffleanswers'] = 'Mélanger les réponses';
//$string['shufflequestions'] = 'Mélanger les questions';
$string['minilesson:itemview'] = 'Voir les éléments';
$string['minilesson:itemedit'] = 'Modifier les éléments';
//$string['fbquestionname'] = 'Élément';
$string['avgcorrect'] = 'Moy. Correct';
$string['avgtotaltime'] = 'Moy. Durée';
//$string['quiz'] = 'Quiz';

//MSV stuff
//$string['error']="Erreur";
//$string['notes']="Notes";


$string['addmultichoiceitem']='Choix multiple';
$string['addmultiaudioitem']='Audio MC';
$string['adddictationchatitem']='Dictée par Chat';
$string['adddictationitem']='Dictée';
$string['addlistenrepeatitem']='Écouter et Parler';
$string['addspeechcardsitem']='Cartes de Parole';
$string['addpageitem']='Page de contenu';
$string['addsmartframeitem']='Cadre Intelligent';
$string['addshortansweritem']='Réponse courte';
$string['addlisteninggapfillitem']='Écoute - Texte à trous';
$string['addspeakinggapfillitem']='Parole - Texte à trous';
$string['addtypinggapfillitem']='Dactylographie - Texte à trous';
$string['addcomprehensionquizitem']='Quiz de Compréhension';
$string['addspacegameitem']='Jeu de l\'espace';
$string['addfreewritingitem']='Écriture libre';
$string['addfreespeakingitem']='Parole libre';
$string['addfluencyitem']='Fluence';
$string['addpassagereadingitem']='Lecture de Passage';
$string['addconversationitem']='Conversation';

$string['multichoice'] = 'Choix multiple';
$string['multiaudio'] = 'Audio MC';
$string['dictation']='Dictée';
$string['dictationchat']='Dictée par Chat';
$string['speechcards']='Cartes de Parole';
$string['listenrepeat']='Écouter et Parler';
$string['page']='Page de Contenu';
$string['smartframe']='Cadre Intelligent';
$string['shortanswer']='Réponse courte';
$string['lgapfill']='Écoute - Texte à trous';
$string['sgapfill']='Parole - Texte à trous';
$string['tgapfill']='Dactylographie - Texte à trous';
$string['spacegame']='Jeu de l\'espace';
$string['freewriting']='Écriture libre';
$string['freespeaking']='Parole libre';
$string['fluency']='Fluence';
$string['passagereading']='Lecture de Passage';
$string['conversation']='Conversation';
$string['transcriber'] = 'Transcripteur';
$string['transcriber_details'] = 'Le moteur de transcription à utiliser';
$string['transcriber_auto'] = 'Transcription ouverte (Strict)';
$string['transcriber_poodll'] = 'Transcription guidée (Poodll)';

$string['pagelayout'] = 'Disposition de la page';

$string['thatsnotright'] = 'Quelque chose ne va pas';
//$string['invalidattempt'] = 'Tentative invalide';
//$string['notyourattempt'] = 'Je pense que ce n\'est pas votre tentative de lecture.';
//$string['notfinished'] = 'Cette lecture n\'est pas terminée';

//$string['title'] = 'Titre';
//$string['level'] = 'Niveau';
//$string['errors'] = 'Erreurs';
//$string['studentname'] = 'Étudiant';
//$string['goback'] = 'Retourner';
//$string['teacher'] = 'Enseignant';
//$string['close'] = 'Fermer';

//$string['submitrawaudio'] = 'Soumettre l\'audio non compressé';
//$string['submitrawaudio_details'] = 'Soumettre de l\'audio non compressé peut augmenter la précision de la transcription, mais au détriment de la vitesse de téléchargement et de la fiabilité.';

//dictation chat
$string['dc_results'] = 'Résultats';
$string['listenandtype'] = 'Écouter et taper';
$string['listen'] = 'Écouter';
$string['check'] = 'Vérifier';
$string['skip'] = 'Passer';
$string['start'] = 'Commencer';
//$string['next'] = 'Suivant';
$string['nextlessonitem'] = 'Page suivante';
$string['loading'] = 'Chargement...';
$string['dictation_instructions1'] = 'Écoutez et tapez chaque phrase que vous entendez.';
$string['sc_instructions1'] = 'Lisez chaque phrase sur la carte à voix haute.';
$string['dc_instructions1'] = 'Écoutez et tapez les phrases que vous entendez.';
//$string['dc_instructions2'] = 'Cliquez sur "Commencer" pour démarrer !';
//dictation
$string['d_question'] = 'Élément';
//listen and repeat
$string['listenandrepeat'] = 'Écouter et Parler';
$string['lr_instructions1'] = 'Écoutez et répondez aux phrases que vous entendez.';
//$string['lr_instructions2'] = 'Cliquez sur "Commencer" pour démarrer !';
$string['spacegame_instructions1'] = 'Tirez sur les aliens en sélectionnant la bonne réponse.';
$string['freespeaking_instructions1'] = 'Utilisez le microphone pour enregistrer votre réponse à la question.';
$string['passagereading_instructions1'] = 'Utilisez le microphone pour vous enregistrer en train de lire le passage.';
$string['freewriting_instructions1'] = 'Tapez votre réponse à la question dans la zone de texte ci-dessous.';
$string['fluency_instructions1'] = 'Remplacer ces instructions.';
$string['conversation_instructions1'] = 'Remplacer ces instructions.';

$string['choosevoice'] = "Choisissez la voix de l'interlocuteur";
$string['choosemultiaudiovoice'] = "Choisissez la voix du lecteur de réponse";
$string['showoptionsastext'] = 'Afficher les réponses sous forme de texte';
$string['showtextprompt'] = 'Afficher l\'invite de texte';
$string['textprompt_words'] = 'Afficher le texte complet';
$string['textprompt_dots'] = 'Afficher des points au lieu des lettres';
$string['listenorread'] = "Afficher les options sous forme de";
$string['listenorread_read'] = 'texte brut';
$string['listenorread_listen']= 'lecteurs audio + points';
$string['listenorread_listenandread']= 'lecteurs audio + texte brut';
$string['listenorread_image']= 'images + texte brut';

//$string['gradenow']= 'Noter maintenant';

$string['itemtype']= 'Type d\'élément';
$string['action']= 'Action';
$string['order']= 'Ordre';
$string['deleteitem'] = 'Supprimer l\'élément';
$string['deleteitem_message'] = 'Vraiment supprimer l\'élément :&nbsp;';
$string['deletebuttonlabel'] = 'SUPPRIMER';

$string['noitems'] ='Il n\'y a pas encore d\'éléments de leçon dans cette activité';
$string['letsadditems'] ='Ajoutons des éléments de leçon !';
$string['additems'] ='Ajouter des éléments';
$string['showqtitles'] ='Afficher les titres des éléments dans la leçon';
$string['previewitem'] ='Aperçu de l\'élément';
$string['showitemscores'] ='Voir tous les résultats';
//$string['ttshorturl'] = 'URL SmartFrame :';
$string['reattempt'] ='Réessayer';
$string['attemptresultsheading']='{$a->username} : Tentative ({$a->attemptid}) : Score : {$a->sessionscore}% : - {$a->date} ';
$string['result'] ='Résultat';
$string['qnumber'] ='N°';
$string['title'] ='Titre';
$string['type'] ='Type';
$string['sentences'] ='Phrases';
$string['correctresponses'] ='Réponses correctes';
$string['enterresponses'] ='Entrez une liste de réponses correctes dans la zone de texte ci-dessous. Placez chaque réponse sur une nouvelle ligne.';
$string['sentenceprompts'] ='Phrases (invitations)';
//$string['entersentences'] ='Entrez une liste de phrases dans la zone de texte ci-dessous. Placez chaque phrase sur une nouvelle ligne.';
$string['phraseresponses'] ='Entrez une liste d\'éléments dans la zone de texte ci-dessous. Chaque élément doit être sur une nouvelle ligne. Le format est :<br> invitation audio | réponse correcte (facultatif) | texte d\'invitation (facultatif) <br>Ex : Comment ça va ?|Je vais bien.';

$string['itemmedia'] ='Image, audio ou vidéo à afficher';
$string['itemttsquestion'] ='Texte de l\'invitation TTS';
$string['itemttsquestionvoice'] ='Voix de l\'invitation TTS';
$string['itemiframe'] ='Code d\'intégration iFrame';
$string['itemtextarea'] = 'Bloc de texte';
$string['prompt-separate'] ='Texte et média séparés (recommandé)';
$string['prompt-richtext'] ='Texte enrichi';
$string['prompttype'] ='Texte et Média';
$string['prompttype_help'] ='Utilisez un texte brut et des sélecteurs séparés pour ajouter des fichiers multimédias, ou un éditeur de texte enrichi';

//repeatable
//$string['sentence'] ='Phrase';
//$string['sentence_help'] ='Aide sur la phrase';
//$string['sentenceno'] ='Numéro de phrase';
//$string['sentence_add_fields'] ='Ajouter une autre phrase';

//reattempt
$string['reattempt'] = 'Réessayer';
$string['reattempttitle'] = 'Vraiment réessayer ?';
$string['reattemptbody'] = 'Si vous continuez, votre tentative précédente sera remplacée par celle-ci. OK ?';

//media toggles
$string['addmedia'] = 'Image / audio ou vidéo';
$string['addmedia_instructions'] = 'Choisissez le type de média que vous souhaitez afficher dans l\'élément de leçon.';
$string['addiframe'] = 'iFrame / HTML personnalisé';
$string['addiframe_instructions'] = 'Collez le code d\'intégration de l\'iframe que vous souhaitez afficher dans l\'élément de leçon.';
$string['addttsaudio'] = 'Audio TTS';
$string['addttsaudio_instructions'] = 'Entrez le texte que vous souhaitez faire lire par le moteur TTS.';
$string['addtextarea'] = 'Bloc de texte';
$string['addtextarea_instructions'] = 'Entrez le texte que vous souhaitez afficher dans l\'élément de leçon.';
$string['addyoutubeclip'] = 'Extrait YouTube';
$string['addyoutubeclip_instructions'] = 'Entrez l\'ID de la vidéo YouTube/Vimeo ainsi que les temps de début et de fin pour l\'extrait que vous souhaitez afficher dans l\'élément de leçon.';
$string['addttsdialog'] = "Dialogue TTS";
$string['addttsdialog_instructions'] = "Ajouter un dialogue TTS";
$string['addttspassage'] = "Passage TTS";
$string['ttspassageinstructions']="Choisissez la voix de l\'interlocuteur, la vitesse, et entrez le passage à lire.";
$string['addttspassage_instructions']="Choisissez la voix de l\'interlocuteur, la vitesse, et entrez le passage à lire.";

//showtextprompt
$string['enablesetuptab']="Activer l'onglet de configuration";
$string['enablesetuptab_details']="Ne cochez probablement pas cette case. Cela affichera un onglet contenant les paramètres de l\'instance d\'activité pour les administrateurs. Cela concerne un cas d'utilisation spécial et les pages de la leçon mini apparaîtront sans en-têtes, pieds de page ou blocs.";
$string['setup']="Configuration";

//TTS options
$string['ttsnormal']='Normal';
$string['ttsslow']='Lent';
$string['ttsveryslow']='Très lent';
$string['ttsssml']='SSML';
$string['choosevoiceoption']='Options d\'invitation TTS';
$string['autoplay']='Lecture automatique';

$string['reportsmenutoptext'] = "Consultez les notes et les détails des tentatives à l'aide des boutons de rapport ci-dessous.";

$string['mediaprompts']="Invitations multimédia";
$string['ignorepunctuation'] = 'Ignorer la ponctuation';

$string['chooselayout']='Choisir une disposition';
$string['layoutauto']='Automatique';
$string['layoutvertical']='Vertical';
$string['layouthorizontal']='Horizontal';
$string['layoutmagazine']='Magazine';

$string['freetrial'] = "Obtenir des identifiants API Cloud Poodll et un essai gratuit";
$string['freetrial_desc'] = "Une boîte de dialogue devrait apparaître pour vous permettre de vous inscrire à un essai gratuit avec Poodll. Après votre inscription, connectez-vous au tableau de bord des membres pour obtenir votre utilisateur API et votre secret. Et pour enregistrer l\'URL de votre site.";
//$string['memberdashboard'] = "Tableau de bord des membres";
//$string['memberdashboard_desc'] = "";
$string['fillcredentials']="Définir l'utilisateur API et le secret avec des identifiants existants";


$string['viewstart']="Ouverture de l'activité";
$string['viewend']="Fermeture de l'activité";
$string['viewstart_help']="Si défini, empêche un étudiant d'entrer dans l'activité avant la date/heure de début.";
$string['viewend_help']="Si défini, empêche un étudiant d'entrer dans l'activité après la date/heure de fermeture.";
$string['activitydate:submissionsdue'] = 'À rendre :';
$string['activitydate:submissionsopen'] = 'Ouvre :';
$string['activitydate:submissionsopened'] = 'Ouvert :';
$string['activityisnotopenyet']="Cette activité n'est pas encore ouverte.";
$string['activityisclosed']="Cette activité est fermée.";
$string['open']="Ouverture : ";
$string['until']="Jusqu'à : ";
$string['activityopenscloses']="Dates d'ouverture/fermeture de l'activité";

$string['ytclipdetails'] = "Extrait YouTube";
$string['itemytid'] = "ID de la vidéo YouTube";
$string['itemytstart'] = "Secondes de début";
$string['itemytend'] = "Secondes de fin";
$string['itemscomplete'] = "Éléments terminés";

$string['ttsdialog'] = "Dialogue TTS";
$string['ttsdialogvoicea'] = "Voix A";
$string['ttsdialogvoiceb'] = "Voix B";
$string['ttsdialogvoicec'] = "Voix C";
$string['ttsdialogvisible'] = "Dialogue Visible";
$string['ttsdialogvisible_desc'] = "Décochez si les élèves ne doivent pas voir le texte du dialogue.";

$string['ttspassage']="Passage TTS";
$string['ttspassagespeed']="Vitesse";
$string['ttspassagevoice']="Voix";

$string['totalscore'] = 'Score Total :';
$string['score'] = 'Score';
$string['questiontext'] = 'Question';
$string['ttsdialoginstructions']="Choisissez les voix des intervenants pour les rôles A, B et C, et entrez le dialogue. Commencez chaque ligne de dialogue par le rôle de l'intervenant + ')'. Par exemple A) Bonjour. Les lignes d'effets sonores commencent par >> par exemple >>mouettes";

$string['courseattempts'] = 'Tentatives du cours';
$string['courseattemptsreport'] = 'Rapport des tentatives du cours';
$string['courseattemptsheading'] = 'Rapport des tentatives du cours';
$string['courseattemptsreport_explanation']='Toutes les tentatives de leçon mini dans le cours';
$string['studentid']="Numéro d'étudiant";
$string['studentname']="Nom de l'étudiant";
$string['activityname']="Nom de la leçon";
$string['itemcount']="Nombre d'éléments";
$string['correctcount']="Nombre d'éléments corrects";
$string['lessonkey']="Clé de la leçon";
$string['lessonkey_details'] =
    'La clé de la leçon est simplement un tag qui sera exporté en CSV avec certains rapports pour faciliter le post-traitement de ces rapports dans un tableur. Vous pouvez laisser ce champ vide.';
$string['lessonkey_help'] =
    'La clé de la leçon est simplement un tag qui sera exporté en CSV avec certains rapports pour faciliter le post-traitement de ces rapports dans un tableur.';
$string['csskey']="Clé CSS";
$string['csskey_details'] =
    'La clé CSS est simplement une classe CSS personnalisée qui sera ajoutée au conteneur de la question, afin que les concepteurs puissent personnaliser facilement l\'apparence. Vous pouvez laisser ce champ vide.';
$string['csskey_help'] =
    'La clé CSS est simplement une classe CSS personnalisée qui sera ajoutée au conteneur de la question, afin que les concepteurs puissent personnaliser facilement l\'apparence.';
$string['reportstable']="Style des rapports";
$string['reportstable_details']="Les tableaux Ajax sont plus rapides à utiliser et peuvent trier les données. Les tableaux paginés se chargent plus rapidement mais sont plus difficiles à naviguer.";
$string['reporttableajax']="Tableaux Ajax";
$string['reporttablepaged']="Tableaux paginés";
$string['anim_fancy']="Animation élaborée";
$string['anim_plain']="Animation simple";
$string['animations']="Animations";
$string['animations_details']="Les transitions entre les sous-types d'éléments sont animées. Si l'animation élaborée cause des problèmes, choisissez l'animation simple.";
$string['confirmchoice_formlabel']="Tentative obligatoire (pas de saut possible)";
$string['continue']="Continuer <i class='fa fa-arrow-right'></i>";
$string['confirmchoice']="Vérifier";
$string['containerwidth_details']="Définit la largeur maximale du conteneur de l'activité MiniLeçon en mode affichage.";
$string['containerwidth_help']="Définit la largeur maximale du conteneur de l'activité MiniLeçon en mode affichage.";
$string['containerwidth']="Largeur du conteneur";
$string['contwidth-compact']="Compact";
$string['contwidth-wide']="Large";
$string['contwidth-full']="Plein écran";
$string['lessonfont']="Police personnalisée";
$string['lessonfont_help']="Nom de la police qui remplacera celle par défaut du site pour cette MiniLeçon lorsqu'elle sera affichée. Doit être écrit exactement (majuscule/minuscule) par exemple Andika ou Comic Sans MS";
$string['advanced']="Avancé";
$string['multiaudio_instructions1'] = 'Choisissez la bonne réponse. Utilisez le micro pour la lire à haute voix.';
$string['multichoice_instructions1'] = 'Choisissez la bonne réponse.';
$string['shortanswer_instructions1'] = 'Répondez à la question en utilisant le micro.';
$string['smartframe_instructions1'] = 'Le contenu de la page se chargera ci-dessous.';

$string['lg_results'] = 'Résultats';
$string['sg_results'] = 'Résultats';
$string['listeninggapfill'] = 'Texte à trous - Écoute';
$string['speakinggapfill'] = 'Texte à trous - Parole';
$string['typinggapfill'] = 'Texte à trous - Dactylographie';
$string['comprehensionquiz'] = 'Quiz de Compréhension';
$string['lg_instructions1'] = 'Instructions pour le Texte à trous - Écoute';
$string['sg_instructions1'] = 'Instructions pour le Texte à trous - Parole';
$string['tg_instructions1'] = 'Instructions pour le Texte à trous - Dactylographie';
$string['compquiz_results'] = 'Résultats';
$string['compquiz_instructions1'] = 'Instructions pour le Quiz de Compréhension';
$string['iteminstructions'] = 'Instructions de l\'élément';
$string['modaleditform'] = 'Formulaire de modification de l\'élément';
$string['modaleditform_details'] = 'L\'ajout ou la modification d\'éléments dans la MiniLeçon peut être fait via un formulaire modal (popup) ou sur une nouvelle page';
$string['modaleditform_newpage'] = 'Nouvelle page';
$string['modaleditform_modalform'] = 'Formulaire modal (popup)';
$string['timelimit'] = 'Limite de temps';
$string['gapfillitemsdesc'] ='Entrez la liste des éléments dans la zone de texte ci-dessous. Chaque élément doit être sur une nouvelle ligne. Les lacunes doivent être placées entre crochets : [ ]. Le format est :<br>Texte d\'invitation | indice<br>Ex : Ceci est mon ch[ien]| un animal de compagnie commun';
$string['listeninggapfillitemsdesc'] ='Entrez la liste des éléments dans la zone de texte ci-dessous. Chaque élément doit être sur une nouvelle ligne. Les lacunes doivent être placées entre crochets : [ ]. Le format est :<br>Texte d\'invitation<br>Ex : Ceci est mon ch[ien]';
$string['readsentences'] = 'Lire les phrases (TTS)';
$string['readsentences_desc'] = 'Si coché, chaque phrase sera lue à haute voix. Ce sera une forme de dictée';
$string['allowretry'] = 'Autoriser la reprise';
$string['allowretry_desc'] = 'Si coché, permet aux élèves de soumettre de nouvelles tentatives si leur réponse précédente n\'était pas correcte.';
$string['eventminilessonstepsubmitted'] = 'Étape de la MiniLeçon soumise';
$string['eventminilessonattemptsubmitted'] = 'Tentative de MiniLeçon soumise';
$string['import'] = 'Importer';
$string['importing'] = 'Importation en cours';
$string['importresults'] = 'Résultats de l\'importation';
$string['backtoimport'] = 'Retour au haut de la page d\'importation';
$string['importinstructions']='Vous pouvez importer des éléments de MiniLeçon en utilisant le formulaire ci-dessous. Le fichier d\'importation doit être au format CSV ou JSON. Consultez la <a href="https://support.poodll.com/en/support/solutions/articles/19000153051-importing-items-into-minilesson" target="_blank">documentation d\'importation</a> pour connaître les données et le format de données à inclure pour chaque type d\'élément. Un exemple de fichier d\'importation se trouve ci-dessous. Ou utilisez le bouton d\'exportation plus bas sur la page pour exporter les éléments de cette activité au format JSON.';
$string['cannotsavecsv'] = 'Impossible de sauvegarder le fichier CSV';
$string['csvdelimiter'] = 'Délimiteur';
$string['importitemsresult']="Résultats de l'importation des éléments";
$string['examplecsv'] = 'Exemple de fichier CSV/texte';
$string['examplecsv_help'] = 'Pour utiliser le fichier CSV d\'exemple, téléchargez-le puis ouvrez-le avec un éditeur de texte ou un tableur. Ne modifiez pas la première ligne, puis modifiez les lignes suivantes (enregistrements) et ajoutez vos données d\'éléments de MiniLeçon, en ajoutant autant de lignes que nécessaire. Enregistrez le fichier au format CSV puis téléchargez-le.';
$string['examplejson'] = 'Exemple de fichier JSON';
$string['examplejson_help'] = 'Pour utiliser le fichier JSON d\'exemple, téléchargez-le puis ouvrez-le avec un éditeur de texte ou un tableur. Modifiez et réutilisez les éléments du tableau "items" lorsqu\'ils correspondent au type d\'élément dont vous avez besoin, et supprimez les éléments dont vous n\'avez pas besoin. Enregistrez le fichier au format JSON puis téléchargez-le.';

$string['error:emptyfield'] = 'NE PEUT PAS ÊTRE VIDE';
$string['error:failed'] = 'ÉCHEC';
$string['error:correctanswer'] = 'RÉPONSE INVALIDE';
$string['error:invaliditemtype'] = 'TYPE D\'ÉLÉMENT INVALIDE';
$string['error:invalidjson'] = 'JSON INVALIDE';
$string['error:noitemsinjson'] = 'AUCUN ÉLÉMENT DANS LE JSON';
$string['error:csvloaderror'] = 'ERREUR DE CHARGEMENT CSV';

$string['bulkdelete'] = 'Supprimer la sélection';
$string['bulkdeletequestion'] = 'Êtes-vous sûr de vouloir supprimer la question sélectionnée ?';

$string['speechtester_recordinstructions'] = 'Enregistrez l\'audio ici pour l\'utiliser dans les tests. Vous pouvez également choisir de télécharger un fichier audio en appuyant sur le bouton de téléchargement d\'audio.';
$string['modelaudio_playerinstructions'] = 'L\'audio actuel peut être lu en utilisant le lecteur ci-dessous.';
$string['speechtester_recordtitle'] = 'Enregistrer un audio';
$string['speechtester_playertitle'] = 'Lire l\'audio';
$string["savestaudio"]='Enregistrer l\'audio';
$string["uploadstaudio"]='Télécharger un audio';
$string["sttstaudio"]='Audio STT';
$string["speechtester"]='Testeur de parole';
$string["exportinstructions"]='Exporter les éléments de cette activité MiniLeçon dans un fichier JSON. Cela peut être utilisé pour sauvegarder ou transférer des éléments vers une autre activité MiniLeçon. Vous pouvez également modifier les éléments exportés et les réimporter en tant que nouveaux éléments.';
$string["exportitems"]='Exporter les éléments';
$string["importformat"]="Format d'importation";
$string["exportheading"]='Exporter les éléments en tant que JSON';
$string["importheading"]="Importer des éléments JSON ou CSV";
$string["allowmicaccess"]="Veuillez autoriser l'accès à votre microphone.";
$string["nomicdetected"]="Aucun microphone détecté.";
$string["speechnotrecognized"]="Nous n'avons pas pu reconnaître votre discours.";
$string["nominilessons"]="Aucune MiniLeçon";

$string["reallydeletemediaprompt"]="Vraiment supprimer le média : ";
$string["deletemediaprompt"]="Supprimer le média ?";
$string["choosemediaprompt"]="Choisir un type de média...";
$string["deletefilesfirst"]="Supprimez tous les fichiers que vous avez ajoutés manuellement. Ils ne seront pas supprimés automatiquement.";
$string["cleartextfirst"]="Supprimez tout contenu que vous avez ajouté manuellement. Il ne sera pas supprimé automatiquement.";

$string["itemsettingsheadings"]="Paramètres de l'élément";

$string["finishscreen"]="Écran de fin";
$string["finishscreen_details"]="Lorsque vous terminez l'activité, vous pouvez voir un écran simple, un écran complet ou un écran personnalisé. L'écran personnalisé est une page que vous pouvez concevoir vous-même.";
$string["finishscreen_help"]="Lorsque vous terminez l'activité, vous pouvez voir un écran simple, un écran complet ou un écran personnalisé. L'écran personnalisé est une page que vous pouvez concevoir vous-même.";
$string["finishscreen_simple"]="Simple";
$string["finishscreen_full"]="Complet";
$string["finishscreen_custom"]="Personnalisé";
$string["finishscreencustom"]="Écran de fin personnalisé";
$string["finishscreencustom_help"]="L'écran personnalisé est une fonctionnalité avancée qui vous permet de créer un écran de fin personnalisé en utilisant la notation mustache et des variables. Certaines des variables sont : {total} {courseurl} {coursename} {yellowstars} {graystars} {reattempturl} et un tableau de {results} chacun avec les variables {title}, {grade}, {yellowstars} et {graystars}.";
$string['finishscreencustom_details'] = "Si les options de l'écran de fin de l'activité sont définies sur 'personnalisé', ceci sera le modèle mustache par défaut qui génère l'écran de fin. Cela peut être remplacé au niveau de l'activité.";
$string['freewritingdesc'] ='Définissez un objectif de nombre de mots ainsi que des directives de notation et de feedback pour l\'évaluation IA. Les étudiants doivent taper leur réponse au sujet, et ils recevront une note et un retour générés par l\'IA.';
$string['freespeakingdesc'] ='<b>Parole libre est un type d\'élément BETA.</b> Les différents navigateurs et appareils mobiles peuvent se comporter différemment.<br/><br/> Définissez un objectif de nombre de mots ainsi que des directives de notation et de feedback pour l\'évaluation IA. Les étudiants doivent s\'enregistrer en parlant sur le sujet, et ils recevront une note et un retour générés par l\'IA.';
$string['conversationdesc'] ='[Description de la conversation ici]';
$string['fluencydesc'] ='[Description de la fluence ici]';
$string['spacegamedesc'] ='[Description du jeu de l\'espace ici]';
$string['passagereadingdesc'] ='<b>Lecture de passage est un type d\'élément BETA.</b> Les différents navigateurs et appareils mobiles peuvent se comporter différemment.<br/><br/> Entrez un passage à lire dans la zone de texte ci-dessous. L\'étudiant doit lire le passage à haute voix.';
$string['passagetoread'] ='Passage à lire';
// Spacegame.
$string['achievedhighscoreof'] = 'Score élevé atteint : {$a}';
$string['addtomywords'] = "Cliquez pour ajouter à Mes Mots";
$string['emptyquiz'] = 'Il n\'y a pas de questions à choix multiple dans la catégorie sélectionnée.';
$string['endofgame'] = 'Votre score est de : {$a}. Appuyez sur espace ou cliquez pour recommencer.';
$string['fullscreen'] = 'Plein écran';
$string['howtoplay'] = 'Comment jouer';
$string['howtoplay_help'] = 'Vous pouvez déplacer le vaisseau en utilisant les touches fléchées ou en le faisant glisser avec la souris.
Appuyez sur la barre d\'espace ou cliquez pour tirer, ou tapez avec deux doigts n\'importe où sur le jeu.
Éliminez autant de questions que possible en tirant sur la bonne réponse. Bonne chance !';
$string['notyetplayed'] = 'Pas encore joué';
$string['playedxtimeswithhighscore'] = 'Joué {$a->times} fois. Le dernier jeu s\'est terminé avec un score élevé de {$a->score}';
$string['playerscores'] = 'Scores des joueurs';
$string['points'] = 'Points';
$string['removefrommywords'] = "Cliquez pour supprimer de Mes Mots";
$string['removescores'] = 'Supprimer tous les scores des utilisateurs';
$string['score'] = 'Score';
$string['scoreheader'] = 'Score';
$string['scoreslink'] = 'Voir toutes les tentatives';
$string['scoreslink_help'] = 'Voir toutes les tentatives des joueurs et leurs scores';
$string['shootthepairs'] = 'Tirez sur les Paires';
$string['spacegameclickclick']  = 'Cliquez sur le bouton pour démarrer le jeu';
$string['spacetostart'] = 'Appuyez sur espace ou cliquez pour commencer';
$string['sound'] = 'Son';
$string['done'] = 'Terminé';
$string['starttest'] = 'Commencer';
$string['tryagain'] = 'Réessayer';
$string['lives'] = 'Vies : ';
$string['includematching'] = 'Inclure Tirer sur les Paires';
$string['includematching_desc'] = 'Inclure le jeu de correspondance « Tirer sur les Paires » dans le Jeu de l\'Espace';
$string['aliencount_mc'] = 'Nombre d\'Aliens (Choix Multiple)';
$string['aliencount_match'] = 'Nombre de Paires d\'Aliens (Tirer sur les Paires)';
$string['spacegameitems'] = 'Éléments du Jeu de l\'Espace';
$string['enterspacegameitems'] = 'Entrez la liste des éléments dans la zone de texte ci-dessous. Chaque élément doit être sur une nouvelle ligne. Le format est :<br>Question | Réponse<br>Ex. Quelle est la capitale de la France ?|Paris';
$string['showdetailedresults'] = 'Afficher les résultats détaillés';
$string['hidestartpage'] = 'Masquer la page de démarrage';
$string['hidestartpage_desc'] = 'Si coché, l\'élément d\'activité commence dès qu\'il est chargé.';
$string['aigrade_modelanswer'] = 'Réponse modèle';
$string['relevancetype'] = 'Type de pertinence';
$string['relevancetype_desc'] = 'L\'IA pénalisera les réponses de faible pertinence. Choisissez le type de pertinence à utiliser.';
$string['aigrade_instructions'] = 'Instructions de notation pour l\'IA';
$string['aigrade_feedback'] = 'Instructions de retour pour l\'IA';
$string['aigrade_feedback_language'] = 'Langue du retour IA';
$string['targetwordcount_title'] = 'Nombre cible de mots';
$string['totalmarks'] = 'Notes totales';
$string['relevancetype_none'] = 'Pertinence non prise en compte';
$string['relevancetype_question'] = 'Pertinence par rapport à la question (texte de l\'élément)';
$string['relevancetype_modelanswer'] = 'Pertinence par rapport à une réponse modèle';
$string['numberonly'] = 'Uniquement des nombres';
$string['targetwordcount'] = 'Nombre cible de mots : {$a}';
$string['currentwordcount'] = 'Nombre de mots : ';
$string['reallyreattempt'] = 'Votre tentative précédente sera écrasée. Voulez-vous vraiment essayer à nouveau ?';
$string['pr_totalmarks_instructions'] = 'Le nombre total de points que cette lecture de passage contribue au score de l\'activité MiniLeçon. Laissez à 0 et les points totaux seront égaux au nombre de mots du passage. Un score sera calculé en fonction du pourcentage de mots lus correctement.';
$string['alternates'] = 'Alternatives';
$string['pr_alternates_instructions'] =  'Les alternatives permettent à l\'auteur de l\'activité de spécifier des transcriptions acceptables pour certains mots du passage. 1 ensemble de mots par ligne. Ex. their|there|they\'re Voir <a href="https://support.poodll.com/support/solutions/articles/19000096937-tuning-your-read-aloud-activity">docs</a> pour plus de détails.';
$string['rp_result_read'] = "Mots lus";
$string['rp_result_correct'] = "Correct";
$string['rp_result_incorrect'] = "Incorrect";
$string['rp_result_unreached'] = "Non lu";
$string['rp_result_accuracy'] = "Précision";
$string['rp_result_score'] = "Score";
$string['showcorrections'] = "Afficher les corrections en ligne";
$string['hidecorrections'] = "Masquer les corrections en ligne";
$string['showitemreview'] = "Afficher la révision de l'élément";
$string['showitemreview_help']="Immédiatement après que l'élève a tenté l'élément, montrez-lui la réponse correcte et tout retour, si l'élément le permet.";
$string['showitemreview_details']="Immédiatement après que l'élève a tenté l'élément, montrez-lui la réponse correcte et tout retour, si l'élément le permet.";
$string['enablepushtab'] = "Activer l'onglet Push";
$string['enablepushtab_details'] = "L'onglet Push permet à quelqu'un ayant la capacité de push sur MiniLeçon (par défaut .. les gestionnaires) de transmettre des paramètres de cette activité à d'autres activités sur le site.";
$string['push'] = 'Transférer';
$string['pushpage'] = 'Page de transfert';
$string['pushpage_explanation'] = 'Utilisez les boutons de cette page pour transférer un paramètre de CETTE instance de MiniLeçon vers d\'autres activités MiniLeçon. Soyez prudent. Il n\'y a pas de retour en arrière.';
$string['pushpage_clonecount'] = 'Le transfert des paramètres de cette activité affectera <b>{$a}</b> autres activités.';
$string['pushpage_noclones'] = 'Il n\'y a pas d\'autres activités dans la portée affectée. Il n\'y a donc rien à transférer.<br><br>';
$string['pushpage_done'] = 'Paramètres transférés à {$a} activités MiniLeçon';
$string['pushpage_scopemodule'] = 'Activités MiniLeçon (site entier) avec ce nom d\'activité : <b>{$a}</b>';
$string['pushpage_scopecourse'] = 'Activités MiniLeçon dans ce cours : <b>{$a}</b>';
$string['pushpage_scopesite'] = 'Toutes les activités MiniLeçon sur le site';
$string['pushpage_scopenone'] = 'Aucune activité MiniLeçon';
$string['pushconfirm'] = 'Vous êtes sur le point de transférer le paramètre : <b>{$a->pushthing}</b> à {$a->clonecount} autres activités. Êtes-vous sûr ?';
$string['scopeselector']  = 'Portée : ';

$string['writehere']  = 'Écrivez ici ..';
$string['submit']='Soumettre';
$string['feedback']='Retour';
$string['totalwords'] = 'Nombre total de mots';
$string['sentences'] = 'Phrases';
$string['uniquewords'] = 'Mots uniques';
$string['ideacount'] = 'Concepts';
$string['relevance'] = 'Pertinence';
$string['original'] = 'Original';
$string['corrected'] = 'Corrigé';
$string['answerdetails'] = 'Détails de la réponse';
$string['seeanswerdetails'] = 'voir les détails';
$string['notsubmit'] = 'Non soumis';
$string['notsubmitted'] = 'Vous n\'avez pas soumis votre réponse. Soumettre maintenant ?';
$string['submitnow'] = 'Soumettre maintenant';
$string['enablenativelanguage'] = "Activer la langue native";
$string['enablenativelanguage_details'] = 'Si défini, l\'élève peut choisir sa langue maternelle, cela remplacera la langue par défaut du retour IA. La langue doit actuellement être <a href="https://support.poodll.com/en/support/solutions/articles/19000163890-definitions-in-user-s-native-language">définie dans Poodll WordCards</a>, et elle est détectée ici.';
$string['nopasting'] = "Désactiver le copier/coller";
$string['nopasting_desc'] = "Désactiver le copier/coller dans la zone de texte. Cela permet d\'éviter que les élèves collent des réponses provenant d\'ailleurs.";
$string['attemptfor'] = 'Tentative : {$a}';
$string['alternatestreaming'] = 'Activer le streaming alternatif';

$string['cloudpoodllserver'] = 'Serveur Cloud Poodll';
$string['cloudpoodllserver_details'] = 'Le serveur à utiliser pour Cloud Poodll. Ne le modifiez que si Poodll vous en a fourni un autre.';
$string['fluency_instructions1'] = 'Lisez les phrases à voix haute. Cliquez sur l’icône du micro pour commencer l’enregistrement.';
$string['fluencyresults'] = 'Résultats de fluidité';
$string['lg_instructions1'] = 'Écoutez et complétez les lacunes';
$string['ningxia'] = 'Ningxia, Chine';
$string['sg_instructions1'] = 'Utilisez le microphone et dites la phrase complète en incluant les mots manquants';
$string['tg_instructions1'] = 'Complétez les mots ou lettres manquants';

$string['minilesson:canuseaigen'] = 'Peut utiliser la fonctionnalité de génération par IA';
$string['fa-ir'] = 'Persan';
$string['so-so'] = 'Somali';
$string['ps-af'] = 'Pachto (afghan)';
$string['region_details'] = 'La région AWS définit où les données sont stockées et traitées.';
$string['addpassagegapfillitem'] = 'Texte à trous';
$string['addh5pitem'] = 'H5P';
$string['addaudiochatitem'] = 'Chat audio';
$string['addwordshuffleitem'] = 'Mots mélangés';
$string['addscatteritem'] = 'Associer';
$string['pgapfill'] = 'Texte à trous';
$string['audiochat'] = 'Chat audio';
$string['scatter'] = 'Associer';
$string['wordshuffle'] = 'Mots mélangés';
$string['skip-fluency'] = 'Continuer';
$string['audiochat_instructions1'] = 'Entraînez-vous à parler avec votre partenaire IA sur le sujet.';
$string['wordshuffle_instructions1'] = 'Remettez les mots dans le bon ordre pour former une phrase.';
$string['scatter_instructions1'] = 'Associez les paires de mots';
$string['fluencyresponses'] = 'Entrez une liste de phrases dans la zone de texte ci-dessous. Chaque élément doit être sur une nouvelle ligne.';
$string['addaudiostory'] = 'Histoire audio';
$string['addaudiostory_instructions'] = 'Pour créer une histoire audio, ajoutez un fichier audio, des images nommées par des numéros (ex : 1.png, 2.jpg) et éventuellement un fichier de sous-titres (.vtt). Indiquez les temps d’entrée des images (format HH:MM:SS) dans la zone de texte.';
$string['audiostorytimes'] = 'Temps d’affichage des images';
$string['audiostoryfiles'] = 'Fichiers de l’histoire audio';
$string['lessonfont_details'] = "Nom de la police qui remplacera celle du site pour cette mini-leçon. Doit être exact (ex : Andika ou Comic Sans MS).";
$string['h5p'] = 'H5P';
$string['pg_instructions1'] = 'Complétez chaque mot manquant.';
$string['h5p_results'] = 'Résultats';
$string['h5p_instructions1'] = 'Complétez l’activité ci-dessous.';
$string['audiochatdesc'] = 'Définissez un sujet de discussion, des instructions et un rôle pour l’IA';
$string['passagegapfilldesc'] = 'Entrez un passage de texte ci-dessous. Les mots manquants doivent être entre crochets : [ ]. Ex : C’est mon [chien] et mon [chat].';
$string['passagewithgaps'] = 'Texte avec trous';
$string['totalmarks_instructions'] = 'Note totale que cet élément apporte à l’activité MiniLeçon.';
$string['pushpage_noactivities'] = 'Aucune activité MiniLeçon ne correspond aux critères';
$string['pushpage_noitems'] = 'Aucun élément dans cette activité MiniLeçon';
$string['pushitems'] = 'Éléments de la leçon';
$string['pushitems_details'] = 'Les éléments de cette MiniLeçon seront copiés dans les MiniLeçons correspondantes (type, ordre et nom identiques). Seul le contenu (texte, images, audio) sera écrasé, pas le type ou l’ordre.';
$string['alternatestreaming_details'] = 'Diffuse l’audio enregistré pour transcription ouverte. Plus lent que la transcription par navigateur, et fonctionne uniquement en anglais. Activé par défaut sur iOS.';
$string['language'] = 'Langue';
$string['voice'] = 'Voix';
$string['passagegapfill'] = 'Texte à trous';
$string['hints'] = 'Indices';
$string['gethints'] = 'Afficher les indices';
$string['sentenceimage'] = 'Images de phrase';
$string['sentenceimage_help'] = '(optionnel) Téléversez des images correspondant aux phrases. Le nom du fichier doit correspondre au numéro de la phrase (ex : 1.png).';
$string['sentenceaudio'] = 'Audios de phrase';
$string['sentenceaudio_help'] = '(optionnel) Téléversez des audios correspondant aux phrases. Le nom du fichier doit correspondre au numéro de la phrase (ex : 1.mp3).';
$string['correctthreshold_desc'] = 'Pourcentage de réussite requis pour valider l’élément.';
$string['correctthreshold'] = 'Seuil de réussite (%)';
$string['anotherhint'] = 'Autre indice';
$string['finish'] = 'Terminer';
$string['penalizehints'] = 'Pénaliser les indices';
$string['penalizehints_desc'] = 'Si coché, l’élève sera pénalisé pour l’utilisation des indices.';
$string['h5pforminstructions'] = 'Sélectionnez une activité H5P depuis la banque de contenus et définissez la note à attribuer. Créez une activité si nécessaire.';
$string['aigen'] = 'Génération IA';
$string['aigenpage'] = 'Génération IA (bêta)';
$string['aigenpage_done'] = 'Génération IA terminée : {$a} éléments ajoutés à l’activité.';
$string['aigenpage_explanation'] = 'Choisissez un modèle de leçon, configurez ses options et l’IA générera les éléments selon les réglages actuels de l’activité.';
$string['aigenpage_notemplates'] = 'Aucun modèle disponible. Veuillez en créer un.';
$string['itemcount'] = 'Nombre d’éléments';
$string['aigenconfirm'] = 'Vous allez utiliser le modèle : <b>{$a->title}</b> pour générer {$a->templatecount} éléments. Confirmez-vous ?';
$string['contextmapping:enabled'] = 'Activé';
$string['contextmapping:title'] = 'Titre';
$string['contextmapping:title_desc'] = 'Saisir le titre';
$string['contextmapping:description'] = 'Explication';
$string['contextmapping:description_desc'] = 'Saisir l’explication';
$string['contextmapping:type'] = 'Type de champ';
$string['contextmapping:options'] = 'Options de champ';
$string['contextmapping:options_desc'] = 'Saisir les options';
$string['aigenmodaltitle'] = 'Saisir les données de contexte';
$string['generatingtextdata'] = 'Génération de texte pour : {$a}';
$string['generatingimagedata'] = 'Génération d’images pour : {$a}';
$string['aigenpageimporting'] = 'Importation des éléments IA en cours';
$string['aigenpagecomplete'] = 'Génération IA terminée';
$string['aigenviewresult'] = 'Voir les éléments générés par IA';
$string['col:templateid'] = 'ID';
$string['col:name'] = 'Nom';
$string['col:timemodified'] = 'Mis à jour le';
$string['col:action'] = 'Action';
$string['action:addtemplate'] = '+ Ajouter un modèle';
$string['action:edittemplate'] = 'Modifier';
$string['action:deletetemplate'] = 'Supprimer';
$string['action:duplicatetemplate'] = 'Dupliquer';
$string['action:downloadtemplate'] = 'Télécharger';
$string['templatedeleteconfirmation'] = 'Confirmez-vous la suppression du modèle ?';
$string['minilesson:managetemplate'] = 'Gérer les modèles';
$string['templatedeleted'] = 'Modèle supprimé avec succès';
$string['templateduplicated'] = 'Modèle dupliqué avec succès';
$string['uniqueid'] = 'ID unique';
$string['version'] = 'Version';
$string['sametemplatefound'] = "Clé déjà utilisée dans le modèle « {\$a->templatename} »";
$string['uploadtemplate'] = 'Téléverser un modèle';
$string['error:atleast2jsonfiles'] = 'Au moins deux fichiers JSON sont requis';
$string['error:templatefilenotuploaded'] = "Fichier modèle '*_template.json' non téléversé";
$string['error:templatefilejsonparsingfailed'] = "Le fichier modèle '{\$a}' est illisible";
$string['error:configfilenotuploaded'] = "Fichier de configuration '*_template.json' non téléversé";
$string['error:configfilejsonparsingfailed'] = "Le fichier de configuration '{\$a}' est illisible";
$string['error:configfile:uniqueidmissing'] = "ID unique du modèle non défini dans le fichier de config";
$string['error:configfile:lessontitlemissing'] = "Nom du modèle non défini dans le fichier de config";
$string['error:configfile:lessondescriptionmissing'] = "Description du modèle non définie dans le fichier de config";
$string['action:uploadtemplate'] = 'Téléverser';
$string['aigentemplatename:passagereading'] = 'Lecture de texte';
$string['aigentemplatename:ayoutubelesson'] = 'Leçon YouTube';
$string['aigentemplatename:youtubefinalelesson'] = 'Finale YouTube';
$string['aigentemplatename:wordpractice'] = 'Exercice de vocabulaire';
$string['aigentemplatename:audiostory'] = 'Histoire audio';
$string['aigentemplatename:keywordstogapfillsfluency'] = 'Mots-clés → textes à trous / fluidité';
$string['aigentemplatedescription:passagereading'] = 'Saisissez une liste de mots-clés et un sujet. L’IA générera un texte avec des activités de lecture et d’oral.';
$string['aigentemplatedescription:ayoutubelesson'] = 'Entrez un ID ou un lien YouTube et des résumés. Poodll générera une leçon courte avec QCM et oral.';
$string['aigentemplatedescription:youtubefinalelesson'] = 'Leçon basée sur une vidéo YouTube, visible seulement à la fin.';
$string['aigentemplatedescription:wordpractice'] = 'Saisissez une liste de 5 mots, Poodll créera une activité pour les pratiquer.';
$string['aigentemplatedescription:audiostory'] = 'Entrez un sujet, le niveau et le type d’histoire. L’IA créera une histoire audio avec exercices.';
$string['aigentemplatedescription:keywordstogapfillsfluency'] = 'Saisissez jusqu’à 10 mots. Poodll générera une activité de pratique.';
$string['generationnotice'] = '<p>NB : Après avoir cliqué sur « Enregistrer », veuillez patienter une à deux minutes.</p>';
$string['processaigentask'] = 'Lancer la génération IA';
$string['successful'] = 'Réussi';
$string['col:progress'] = 'Progression';
$string['col:timecreated'] = 'Planifié le';
$string['col:usageid'] = 'N°';
$string['processpending'] = 'En attente';
$string['processinprogress'] = 'En cours';
$string['multichoiceanswers'] = 'Réponses';
$string['multichoiceanswerimages'] = 'Images des réponses';
$string['multichoiceansweraudios'] = 'Audios des réponses';
$string['mcanswerresponses'] = 'Entrez les options de réponse ci-dessous. Une par ligne. Min. 2, max. 4.';
$string['mcimageresponses'] = '(optionnel) Téléversez des images pour les réponses. Le nom doit correspondre au numéro (ex : 1.png).';
$string['mcaudioresponses'] = '(optionnel) Téléversez des audios pour les réponses. Nom = numéro (ex : 1.mp3). Remplace la voix de synthèse.';
$string['multiaudioaudioresponses'] = '(optionnel) Téléversez des audios pour les réponses. Nom = numéro (ex : 1.mp3).';
$string['audiochat_instructions'] = 'Instructions pour l’IA';
$string['audiochat_instructions_instructions'] = 'Ce modèle combine le rôle, la langue cible, la langue de l’élève et le sujet. Vous pouvez utiliser : {ai role}, {target language}, {native language}, {topic}.';
$string['audiochat_role'] = 'Rôle de l’IA';
$string['audiochat_voice'] = 'Voix de l’IA';
$string['audiochat_role_default'] = 'Un professeur de langue bienveillant';
$string['audiochat_topic'] = 'Sujet du chat';
$string['audiochat_topic_default'] = 'Qu’as-tu fait aujourd’hui ?';
$string['audiochat_native_language'] = 'Langue maternelle de l’élève';
$string['wordshuffledesc'] = 'Entrez la liste des éléments. Un par ligne.';
$string['wordshuffle_results'] = 'Résultats – Mots mélangés';
$string['scatteritems'] = 'Éléments – Associer';
$string['enterscatteritems'] = 'Entrez les paires dans ce format :<br>Terme | Définition<br>ex : Œuf|Chose ovale blanche';
$string['openaikey'] = 'Clé OpenAI';
$string['openaikey_details'] = 'La clé OpenAI est utilisée pour le Chat Audio. Non requise pour les autres types.';
$string['textgenerationfailed'] = 'Échec de génération pour l’élément {$a->itemindex} ({$a->itemtype})';
$string['failed'] = 'Échec';



$string['audiostoryzoomandpan'] = 'Zoom et panoramique';

$string['zoomandpannone'] = 'aucun';
$string['zoomandpanlite'] = 'lent';
$string['zoomandpanmedium'] = 'moyen';
$string['zoomandpanmore'] = 'rapide';

$string['audiochatsessionactive'] = 'Le chat est actif !!';
$string['audiochatsessionconnecting'] = 'Démarrage… La connexion peut prendre 15 secondes.';
$string['audiochatsessionstart'] = "Démarrer";
$string['audiochatsessionend'] = "Terminer";
$string['audiochatsessiontryagain'] = "Réessayer";
$string['audiochatsessionfinished'] = "Terminé";
$string['audiochatcancelconnecting'] = "Annuler";

$string['gradebywordcount'] = "Cette activité a été notée selon le nombre de mots prononcés dans la transcription.";

$string['audiochat_gradeexplanation'] = 'Explication de la note';
$string['audiochat_conversation'] = 'Conversation';
$string['audiochat_gradeinstructions'] = 'Instructions de notation / de retour pour l’IA';

$string['audiochat_gradeinstructions_instructions'] = 'Les instructions de notation doivent indiquer comment générer le retour et la note (0–100). Elles servent de modèle combiné avec le rôle de l’IA, sa voix, le sujet, les données personnalisées 1 et 2 et la langue maternelle de l’étudiant. Vous pouvez utiliser les variables : {ai role}, {ai voice}, {target language}, {native language}, {ai data1}, {ai data2} et {topic}. Si ce champ est vide, la note sera calculée selon le nombre total de mots prononcés par rapport au nombre de mots attendu.';

$string['audiochat_aidata1'] = 'Donnée IA personnalisée 1';
$string['audiochat_aidata2'] = 'Donnée IA personnalisée 2';

$string['audiochat_autosend'] = 'Envoi automatique';
$string['audiochat_autosend_desc'] = 'Si cette option est cochée, la détection de tour de parole est automatique : l’IA enverra automatiquement l’audio de l’étudiant lorsqu’elle détecte qu’il a fini de parler. Pour les jeunes ou les locuteurs plus lents, cela peut être frustrant. Si cette option n’est pas cochée, l’audio est envoyé quand l’étudiant appuie sur le bouton d’arrêt du micro. L’envoi automatique peut aussi être activé ou désactivé pendant l’activité.';

$string['clicktosend'] = 'cliquer pour envoyer';
$string['cantchat'] = 'Le chat n’est PAS disponible. Pour utiliser ce type d’élément, vous devez avoir défini une clé OpenAI valide dans les paramètres administrateur de l’activité MiniLesson. Le chat n’est pas disponible en Chine (région Ningxia).';

$string['confirm_desc'] = 'Vous n’avez pas terminé cette étape. Êtes-vous sûr(e) de vouloir passer à la suivante ?';

$string['ai_topic'] = 'Sujet';
$string['ai_data1'] = 'Donnée IA 1';
$string['ai_data2'] = 'Donnée IA 2';

$string['ai_topic_help'] = 'Le sujet est un champ optionnel {topic} pouvant être combiné avec les instructions de notation ou de retour pour donner du contexte à l’IA.';
$string['ai_data1_help'] = 'La donnée IA 1 est un champ optionnel {ai data1} pouvant être combiné avec les instructions de notation ou de retour pour donner du contexte à l’IA.';
$string['ai_data2_help'] = 'La donnée IA 2 est un champ optionnel {ai data2} pouvant être combiné avec les instructions de notation ou de retour pour donner du contexte à l’IA.';

$string['instructionprompt'] = 'Instruction';

$string['freewriting:gradingprompt1'] = 'Par défaut';
$string['freewriting:gradingprompt2'] = 'Réponse courte — nécessite la bonne réponse (ai data1)';
$string['freewriting:gradingprompt3'] = 'Décrire l’image — nécessite la description d’une image (ai data1)';

$string['freewriting:gradingprompt_dec1'] = 'Retirer 1 point pour chaque faute de grammaire, d’orthographe ou de ponctuation.';
$string['freewriting:gradingprompt_dec2'] = 'La bonne réponse est « {ai data1} ». Donner 0 point pour une réponse incorrecte.  
Donner 2 points pour une phrase complète ayant le sens correct ; donner 1 point pour une phrase incomplète mais au sens correct.';
$string['freewriting:gradingprompt_dec3'] = 'L’étudiant décrit une image. L’image représente la scène suivante : [{topic}]  
Donner 2 points pour chaque phrase grammaticalement correcte décrivant une partie de l’image. Donner 1 point si la phrase est presque correcte.';

$string['freewriting:feedbackprompt1'] = 'Par défaut';
$string['freewriting:feedbackprompt2'] = 'Décrire l’image';
$string['freewriting:feedbackprompt_dec1'] = 'Expliquer chaque erreur simplement.';
$string['freewriting:feedbackprompt_dec2'] = 'Expliquer simplement les principales erreurs de grammaire. Suggérer les éléments de la scène que l’étudiant n’a pas décrits.';

$string['freespeaking:gradingprompt1'] = 'Par défaut';
$string['freespeaking:gradingprompt2'] = 'Réponse courte — nécessite la bonne réponse (ai data1)';
$string['freespeaking:gradingprompt3'] = 'Décrire l’image — nécessite la description d’une image (ai data1)';

$string['freespeaking:gradingprompt_dec1'] = 'Retirer 1 point pour chaque faute de grammaire.';
$string['freespeaking:gradingprompt_dec2'] = 'La bonne réponse est « {ai data1} ». Donner 0 point pour une réponse incorrecte.  
Donner 2 points pour une phrase complète ayant le sens correct ; donner 1 point pour une phrase comportant des erreurs mais au sens correct.';
$string['freespeaking:gradingprompt_dec3'] = 'L’étudiant décrit une image. L’image représente la scène suivante : [{topic}]  
Donner 2 points pour chaque phrase grammaticalement correcte décrivant une partie de l’image. Donner 1 point si la phrase est presque correcte.';

$string['freespeaking:feedbackprompt1'] = 'Par défaut';
$string['freespeaking:feedbackprompt2'] = 'Décrire l’image';
$string['freespeaking:feedbackprompt_dec1']  = 'Expliquer simplement chaque faute de grammaire. Ne pas commenter l’orthographe ni la ponctuation.';
$string['freespeaking:feedbackprompt_dec2'] = 'Expliquer simplement les erreurs de grammaire non mineures. Ne pas commenter l’orthographe ni la ponctuation. Suggérer les éléments de la scène que l’étudiant n’a pas décrits.';

$string['instructionsprompt_header'] = 'En-tête des instructions';
$string['instructionsprompt'] = 'Instructions';
$string['gradingprompt_header'] = 'En-tête des consignes de notation';
$string['gradingprompt'] = 'Consignes de notation';
$string['feedbackprompt_header'] = 'En-tête du retour';
$string['feedbackprompt'] = 'Retour';

$string['audiochat:instructionsprompt1'] = 'Discussion ouverte — nécessite un sujet';
$string['audiochat:instructionsprompt2'] = 'Discussion sur une image — nécessite une description (topic) et des questions (ai data1)';
$string['audiochat:instructionsprompt3'] = 'Discussion sur un texte — nécessite un texte (topic) et des questions (ai data1)';

$string['audiochat:instructionsprompt_dec1'] = "Vous êtes {ai role}. Vous vous appelez {ai voice}. Vous enseignez {target language}. L’étudiant est locuteur natif de {native language}. Aujourd’hui, le sujet de discussion est : {topic}. Discutez-en avec votre étudiant. Parlez lentement et simplement. Vos réponses doivent être brèves. Votre objectif est de donner à l’étudiant l’occasion de parler. Expliquez les mots et les notions dans la langue maternelle de l’étudiant s’il ne comprend pas.";
$string['audiochat:instructionsprompt_dec2'] = 'Vous êtes {ai role}. Vous vous appelez {ai voice}. Vous enseignez {target language}. L’étudiant est locuteur natif de {native language}. Il regarde une image représentant la scène suivante : « {topic} ».  

Votre tâche est de poser les questions suivantes, une par une : {ai data1}  

Parlez lentement et simplement. Vos réponses doivent être brèves. Ne commentez pas le contenu ou la justesse de la réponse. Aidez l’étudiant s’il ne comprend pas la question. Quand il a répondu à toutes, remerciez-le et dites-lui d’« appuyer sur le bouton Terminer ».';
$string['audiochat:instructionsprompt_dec3'] = 'Vous êtes {ai role}. Vous vous appelez {ai voice}. Vous enseignez {target language}. L’étudiant est locuteur natif de {native language}. Il vient de lire le texte suivant : « {topic} ».  

Votre tâche est de poser les questions suivantes, une par une : {ai data1}  

Parlez lentement et simplement. Vos réponses doivent être brèves. Ne commentez pas le contenu ou la justesse de la réponse. Aidez l’étudiant s’il ne comprend pas la question. Quand il a répondu à toutes, remerciez-le et dites-lui d’« appuyer sur le bouton Terminer ».';

$string['audiochat:feedbackprompt1'] = 'Discussion ouverte';
$string['audiochat:feedbackprompt2'] = 'Discussion avec questions — nécessite des points et des questions (ai data1)';
$string['audiochat:feedbackprompt_dec1'] = "Pour la note, tenez compte des critères suivants : pertinence par rapport au sujet « {topic} », fluidité et utilisation du vocabulaire. Le retour doit être simple et dans la langue maternelle de l’étudiant : {native language}.";
$string['audiochat:feedbackprompt_dec2'] = "Attribuer 25 points pour chaque réponse correcte à une question posée à l’étudiant, 0 point sinon. Donner un retour sur l’usage de la langue et sur les réponses de l’étudiant, dans sa langue maternelle : {native language}. Voici les questions : {ai data1}";

$string['hidecorrection'] = 'Masquer les corrections';
$string['showgrade'] = "Afficher la note";
$string['starrating'] = "Évaluation par étoiles — par défaut";
$string['percentagescore'] = "Score en pourcentage";
$string['detailedresults'] = 'Résultats détaillés — par défaut';
$string['basciresult'] = 'Résultats simples';
$string['showresult'] = 'Afficher le résultat';
$string['aigradingandfeedback'] = 'Notation et retour IA';
$string['resultsdisplay'] = 'Affichage des résultats';

$string['manageminilessonitem'] = 'Gérer les éléments de leçon';
$string['successfullyupdated'] = 'Mise à jour réussie';
$string['manageminilessonitems_explanation'] = 'Vous pouvez ici définir la visibilité des éléments dans la boîte de dialogue « Ajouter un nouvel élément ». Le masquage ou l’affichage ici n’affecte pas les éléments déjà créés.';

$string['completionwhenfinished'] = 'L’étudiant a terminé l’activité.';
$string['completionwhenfinisheddesc'] = 'Terminer toutes les étapes de l’activité.';
$string['completiondetail:finished'] = 'Terminer toutes les étapes de l’activité';
$string['completiondetail:mingrade'] = 'Obtenir une note de {$a}% ou plus';
$string['mingrade_help'] = 'La note minimale requise pour « terminer » cette activité.';
$string['mingrade'] = 'Note minimale';

$string['reorder'] = 'Réorganiser';
$string['chooseitem'] = 'Choisir un élément';

$string['item_page_desc'] = 'La page de contenu est le type d’élément le plus simple. Elle affiche simplement le texte ou le média défini par l’auteur de l’activité et un bouton « Suivant ». Utilisez ce type pour préparer les étudiants aux éléments suivants ou inclure un contenu explicatif.';
$string['item_multichoice_desc'] = 'L’élément à choix multiple affiche une question à choix multiple comportant jusqu’à 4 réponses possibles, dont une seule correcte. Les options peuvent être du texte, de l’audio ou des images.';
$string['item_dictation_desc'] = 'L’élément dictée affiche une série de paires lecteur audio + champ texte. L’étudiant doit écouter l’audio et taper ce qu’il entend.';
$string['item_speechcards_desc'] = 'L’élément cartes de lecture à voix haute affiche une série de cartes contenant une phrase. L’étudiant doit lire chaque carte à voix haute.';
$string['item_listenrepeat_desc'] = 'L’élément « Écouter et répéter » présente une série de consignes audio auxquelles l’étudiant doit répondre oralement. Utilisé surtout pour des activités d’écoute et répétition.';
$string['item_multiaudio_desc'] = 'L’élément choix multiple audio propose une question à choix multiples où l’étudiant doit répondre oralement.';
$string['item_shortanswer_desc'] = 'L’élément réponse courte est une question unique à laquelle l’étudiant doit répondre oralement. Les bonnes réponses sont définies par l’auteur.';
$string['item_listeninggapfill_desc'] = 'L’élément texte à trous (écoute) affiche des phrases comportant un ou plusieurs mots manquants. L’étudiant écoute et complète les mots manquants.';
$string['item_typinggapfill_desc'] = 'L’élément texte à trous (saisie) affiche des phrases avec des mots manquants. L’étudiant doit les saisir.';
$string['item_speakinggapfill_desc'] = 'L’élément texte à trous (oral) affiche des phrases avec des mots manquants. L’étudiant doit dire la phrase complète à voix haute.';
$string['item_spacegame_desc'] = 'Le jeu spatial est un mini-jeu de type arcade basé sur des paires de mots. L’étudiant doit « tirer » sur les bons mots.';
$string['item_freespeaking_desc'] = 'L’élément expression orale libre permet à l’enseignant de proposer des tâches orales notées automatiquement avec un retour fourni à l’étudiant.';
$string['item_freewriting_desc'] = 'L’élément expression écrite libre permet à l’enseignant de proposer des tâches écrites notées automatiquement avec un retour fourni à l’étudiant.';
$string['item_passagereading_desc'] = 'L’élément lecture de texte affiche une question à choix multiples comportant jusqu’à 4 réponses possibles, dont une seule correcte. Les options peuvent être du texte, de l’audio ou des images.';
$string['item_passagegapfill_desc'] = 'L’élément texte à trous (passage) fournit un texte avec plusieurs mots supprimés. L’étudiant écoute l’audio et remplit les blancs.';
$string['item_h5p_desc'] = 'L’élément H5P permet d’insérer une activité H5P existante depuis la banque de contenus et de l’intégrer dans la MiniLesson.';
$string['item_wordshuffle_desc'] = 'L’élément mélange de mots prend un ensemble de phrases dont les mots sont mélangés. L’étudiant doit les remettre dans le bon ordre.';
$string['item_scatter_desc'] = 'L’élément association présente une grille de cartes formées de paires de mots. L’étudiant doit les associer pour vider la grille.';
$string['item_fluency_desc'] = 'L’élément fluidité présente une série de phrases à dire à voix haute. Il indique les parties bien, moyennement ou mal prononcées.';
$string['item_audiochat_desc'] = 'L’élément chat audio crée une conversation audio avec un partenaire IA. Il peut être utilisé pour discuter d’un sujet ou poser des questions sur une image. Les consignes données au bot sont libres et modifiables.';
$string['item_smartframe_desc'] = 'L’élément SmartFrame est un espace réservé pour un type d’élément non encore développé.';
$string['item_comprehensionquiz_desc'] = 'L’élément quiz de compréhension est un espace réservé pour un type d’élément non encore développé.';
$string['item_conversation_desc'] = 'L’élément conversation est un espace réservé pour un type d’élément non encore développé.';
$string['item_dictationchat_desc'] = 'L’élément dictée-chat présente une série de consignes audio auxquelles l’étudiant doit répondre par écrit. Utilisé surtout pour les dictées.';

$string['noitemyet'] = "Aucun élément pour le moment";
$string['addingcontent'] = "Cliquez sur « Ajouter un nouvel élément » pour commencer à ajouter du contenu.";
$string['hidewarning'] = "Masquer les résultats intermédiaires";
$string['hidewarning_details'] = "Afficher uniquement les bonnes (vert) et mauvaises (rouge) réponses ; ne pas afficher les réponses presque correctes (orange).";

$string['responsetype'] = 'Type de réponse';
$string['audiorecorder'] = 'Enregistreur audio';
$string['textinput'] = 'Saisie texte';
$string['partiallycorrectresponses'] = 'Réponses partiellement correctes';
$string['partiallycorrectmarks'] = 'Points pour réponses partiellement correctes';


$string['promptsettings'] = 'Paramètres du prompt';
$string['addnewitem'] = 'Ajouter un nouvel élément';
$string['enterresponses'] = 'Saisissez ci-dessous une liste de réponses correctes et, en option, une liste de réponses partiellement correctes.';
$string['addaudiostory_instructions'] = 'Pour créer une histoire audio, ajoutez un fichier audio, des images nommées avec des numéros (ex. 1.png, 2.jpg) et, en option, une piste de sous-titres (.vtt). Indiquez les instants d’affichage des images (format HH:MM:SS) dans la zone de texte. S’il n’y a pas de fichier audio et qu’un prompt média TTS est défini, l’histoire audio l’utilisera.';
$string['audiochatretry_desc'] = 'Si cette option est cochée, les étudiants peuvent relancer la session de chat immédiatement après l’avoir terminée.';
$string['relevancetype_desc'] = 'L’IA peut pénaliser les réponses peu pertinentes. Choisissez le type de pertinence à utiliser.';

$string['relevancetype_help'] = 'L’IA peut pénaliser les réponses peu pertinentes. Choisissez le type de pertinence à utiliser. Si vous choisissez la pertinence par rapport à la question, l’IA vérifiera si la réponse est bien sur le sujet. Si vous choisissez la pertinence par rapport à une réponse modèle, l’IA vérifiera si la réponse ressemble, par son contenu, à la réponse modèle fournie.';
$string['aigrade_instructions_desc'] = 'Ces instructions indiquent à l’IA comment générer un « score » entre 0 et le total de points de cet item. Elles servent de modèle fusionné avec le sujet IA, les données IA 1 et IA 2 pour créer les consignes. Vous pouvez utiliser les variables suivantes : {topic}, {ai data1}, {ai data2}. La note IA résultante sera multipliée par le nombre total de mots prononcés au-delà du nombre de mots cible, afin de pénaliser les réponses trop brèves. Définissez le nombre de mots cible à 0 si ce n’est pas souhaité.';

$string['aigrade_instructions_help'] = 'Ces instructions indiquent à l’IA comment générer un « score » entre 0 et le total de points de cet item. Elles servent de modèle fusionné avec le sujet IA, les données IA 1 et IA 2. Variables utilisables : {topic}, {ai data1}, {ai data2}. La note IA résultante sera multipliée par le nombre total de mots prononcés au-delà du nombre de mots cible, pour pénaliser les réponses trop brèves. Mettez le nombre de mots cible à 0 si ce n’est pas souhaité.';
$string['aigrade_feedback_desc'] = 'Ces consignes indiquent à l’IA comment donner un retour à l’étudiant sur sa réponse. Modèle fusionné avec le sujet IA et les données IA 1/2. Variables possibles : {topic}, {ai data1}, {ai data2}.';
$string['aigrade_instructions_preset'] = 'Choisissez un préréglage pour charger des instructions prêtes à l’emploi dans la zone de texte ci-dessous. Vous pouvez ensuite les modifier.';
$string['aigrade_feedback_help'] = 'Ces consignes indiquent à l’IA comment fournir un feedback à l’étudiant. Modèle fusionné avec le sujet IA et les données IA 1/2. Variables possibles : {topic}, {ai data1}, {ai data2}.';

$string['aigentemplatename:reading_aic_passagegen'] = 'Lecture + Chat IA (texte généré)';
$string['aigentemplatename:reading_aic_passageupload'] = 'Lecture + Chat IA (texte importé)';
$string['aigentemplatename:keywords_to_ws_sc'] = 'Mots-clés → Word Shuffle et Scatter';
$string['aigentemplatename:keywords_to_ws_sc_sg'] = 'Mots-clés → Word Shuffle, Scatter et Space Game';

$string['aigentemplatedescription:keywordstogapfillsfluency'] = 'Saisissez une liste de mots (maximum 30) et Poodll créera une courte activité pour que les étudiants s’exercent à ces mots.';
$string['aigentemplatedescription:reading_aic_passagegen'] = 'À partir d’un sujet et d’un niveau, génère un texte de lecture qui sera utilisé pour une activité de lecture puis une session de chat audio.';
$string['aigentemplatedescription:reading_aic_passageupload'] = 'Collez un texte de lecture et 4 questions à réponse courte. Ils seront utilisés pour une lecture puis une session de chat audio.';
$string['aigentemplatedescription:keywords_to_ws_sc'] = 'Entrez 4 à 10 mots-clés. Ils serviront à générer une activité Word Shuffle et Scatter, ainsi qu’une image de chaque mot-clé sur un tableau noir avec un mignon professeur chat à côté.';
$string['aigentemplatedescription:keywords_to_ws_sc_sg'] = 'Entrez 4 à 10 mots-clés. Ils serviront à générer des activités Word Shuffle, Scatter et Space Game, ainsi qu’une image de chaque mot-clé sur un tableau noir avec un mignon professeur caniche.';

$string['audiochat_instructions_instructions'] = 'Ces instructions sont un modèle combiné avec le rôle IA, la voix IA, le sujet, les données personnalisées IA 1/2 et la langue maternelle de l’étudiant pour former les consignes transmises à l’IA. Variables utilisables : {ai role}, {ai voice}, {target language}, {native language}, {ai data1}, {ai data2}, {topic}. Les instructions par défaut conviennent généralement, mais vous pouvez les adapter à votre usage.';


$string['addslidesitem'] = 'Diapositives';
$string['slides'] = 'Diapositives';
$string['slides_instructions1'] = 'Cliquez sur > ou < pour avancer ou revenir en arrière dans les diapositives.';
$string['aigentemplatename:set_of_slides'] = 'Ensemble de diapositives';
$string['aigentemplatename:set_of_slides_nopics'] = 'Ensemble de diapositives (sans images)';
$string['aigentemplatedescription:set_of_slides'] = 'Saisissez une description du tutoriel que vous souhaitez que l’IA génère ; elle créera pour vous un ensemble de diapositives avec du texte et des images prêtes à l’emploi.';
$string['aigentemplatedescription:set_of_slides_nopics'] = 'Saisissez une description du tutoriel que vous souhaitez que l’IA génère ; elle créera pour vous un ensemble de diapositives prêtes à l’emploi.';
$string['item_slides_desc'] = 'L’élément « Diapositives » permet à l’auteur de créer une série de diapositives. Celles-ci peuvent être utilisées pour présenter un contenu pédagogique. Les diapositives sont créées à l’aide de la syntaxe Markdown ; il s’agit donc d’une fonctionnalité destinée aux utilisateurs avancés.';
$string['lessonbank'] = 'Banque de leçons';
$string['keyword'] = 'Mot-clé';
$string['keywordcolon'] = 'Mot-clé :';
$string['descriptioncolon'] = 'Description :';
$string['itemscolon'] = 'Éléments :';
$string['showadvanced'] = 'Afficher les options avancées';
$string['level'] = 'Niveau';
$string['items'] = 'Éléments';
$string['lessonitemcreate'] = 'Élément de leçon créé avec succès';
$string['error:functionnotfound'] = 'Fonction introuvable';
$string['lessonbank:desc'] = 'Vous pouvez importer des éléments prêts à l’emploi depuis la banque de leçons dans votre activité MiniLesson. Faites une recherche avec le formulaire ci-dessous, puis utilisez le bouton « Importer » dans les résultats pour importer les éléments de leçon.';
$string['enablelessonbank'] = 'Activer la banque de leçons';
$string['enablelessonbank_details'] = 'Activer la banque de leçons';
$string['lessonbankurl'] = 'URL de la banque de leçons';
$string['lessonbankurl_details'] = 'URL de la banque de leçons';
$string['notconfigured'] = 'URL de la banque de leçons non configurée';
$string['hideitemscores'] = 'Masquer tous les résultats';
$string['shuffleanswer'] = 'Mélanger les réponses';
$string['enterslidesmarkdown'] = 'Saisissez le Markdown des diapositives. Markdown est une sorte de syntaxe abrégée pour créer du HTML. Voir le <a href="https://poodll.freshdesk.com/a/solutions/articles/19000169462" target="_blank">guide Slides Markdown</a> pour plus de détails.';
$string['slidesmarkdown'] = 'Markdown des diapositives';
$string['slides:attachments'] = 'Pièces jointes';
$string['slides:theme'] = 'Thème';
$string['slides:fontsize'] = 'Taille de police';
$string['slides:fontsmall'] = 'Petit';
$string['slides:fontsmaller'] = 'Plus petit';
$string['slides:fontstandard'] = 'Standard';
$string['slides:fontlarger'] = 'Plus grand';
$string['slides:fontlarge'] = 'Grand';
$string['slides:preview'] = 'Aperçu';
$string['slides:previewmodaltitle'] = 'Aperçu';

$string['wordshuffle_wordbank_label'] = 'Banque de mots';
$string['wordshuffle_drop_slot_label'] = 'Zone de dépôt {$a}';
$string['wordshuffle_a11y_returned_to_bank'] = '« {$a} » remis dans la banque de mots';
$string['wordshuffle_a11y_placed_in_slot'] = '« {$a} » placé dans la zone de dépôt';

$string['aigentemplatename:dialog_multichoice'] = 'Écouter 4 dialogues';
$string['aigentemplatename:image_slides'] = 'Diapositives illustrées';
$string['aigentemplatename:choose_best_reply'] = 'Choisir la bonne réplique';

$string['aigentemplatedescription:dialog_multichoice'] = 'Les étudiants choisissent l’image qui représente le mieux le dialogue en cours de lecture. Pour créer l’activité, saisissez 4 dialogues. Ils formeront 4 questions à choix multiples où chaque réponse est une image représentant le dialogue en cours. Les dialogues doivent être au format TTS Dialog, par ex. : A) I like toast. [new line] B) Me too.';
$string['aigentemplatedescription:image_slides'] = 'Saisissez 5 descriptions d’images et un style d’image pour obtenir 5 diapositives illustrées.';
$string['aigentemplatedescription:choose_best_reply'] = 'L’étudiant écoute 10 courtes conversations et doit choisir la meilleure réplique suivante parmi les 3 options proposées. Chaque question est accompagnée d’une image. Ce modèle est basé sur la partie compréhension orale du test Eiken niveau 3 en anglais. Vous devez saisir le lieu et la situation (généralement un problème) pour chaque conversation, par ex. « two friends at the beach and one has lost their wallet. »';

$string['templatetags'] = 'Balises de modèle';

$string['hideanswertext'] = 'Masquer le texte de la réponse';
$string['hideanswertext_detail'] = 'Lorsque vous utilisez des images ou de l’audio comme réponses, cochez cette option pour masquer le texte de la réponse à l’étudiant. Le texte de la réponse sera utilisé lors de l’affichage des résultats.';

$string['answerlayout'] = 'Disposition des réponses';
$string['twocolumn'] = '2 colonnes';


$string['continuepreviousattempt'] = 'Continuer la tentative précédente ?';
$string['allowcontinueattempts'] = 'Autoriser la reprise de la tentative précédente';
$string['allowcontinueattempts_details'] = 'Si activé, les étudiants peuvent reprendre une tentative incomplète là où ils se sont arrêtés.';
$string['allowcontinueattempts_help'] = 'Si activé, les étudiants peuvent reprendre une tentative incomplète là où ils se sont arrêtés.';
$string['attemptreusequestion'] = "Voulez-vous continuer votre tentative précédente (oui) ?<br>
Si vous choisissez non, votre tentative précédente sera supprimée.";
$string['azureapikey'] = 'Clé API Azure';
$string['azureapikey_details'] = 'Clé API Azure. Elle permet d’utiliser votre compte Azure pour les services vocaux avec MiniLesson. Optionnel. Principalement pour les utilisateurs en Chine continentale (région Ningxia). Voir <a href= "https://learn.microsoft.com/en-us/azure/cognitive-services/speech-service/overview">ici</a> pour plus de détails.';
$string['azureapiregion'] = 'Région Azure';
$string['azureapiregion_details'] = 'Région Azure pour laquelle la clé API a été générée.';
$string['otherapikeys'] = 'Autres clés API (BYOK)';
$string['itemtypes'] = 'Types d’éléments';
$string['fiction'] = 'Fiction';
$string['enablevkeyboard'] = 'Activer le clavier virtuel';
$string['fiction_instructions1'] = 'Suivez les instructions et faites les bons choix. Bonne chance.';
$string['nativelangchooser'] = 'Sélecteur de langue maternelle';
$string['nativelangchooser_instructions'] = 'Permet aux utilisateurs de choisir leur langue maternelle dans une liste déroulante.';
$string['choosenativelanguage'] = 'Choisir la langue maternelle';
$string['tts_notts'] = 'Sans synthèse vocale';
$string['customaudio'] = 'Audio personnalisé';
$string['importdotranslate'] = 'Traduire les éléments lors de l’import';
$string['importdotranslate_help'] = 'Si activé, les éléments JSON sont traduits de la langue source vers la langue cible avant l’import.';
$string['importfromlang'] = 'Traduire depuis la langue';
$string['importtolang'] = 'Traduire vers la langue';
$string["exportandtranslateinstructions"] = 'Traduisez et exportez les éléments de cette activité MiniLesson en JSON. Cela permet de traduire le texte de la langue A vers la langue B afin d’importer les éléments dans une nouvelle activité avec un profil linguistique différent. Sélectionnez d’abord la langue source (la page se rechargera), puis la langue cible (la page se rechargera). Un bouton de traduction apparaîtra : cliquez dessus pour traduire et exporter l’activité.';
$string["exportandtranslateitems"] = 'Traduire et exporter les éléments';
$string["exportandtranslateheading"] = 'Traduire et exporter les éléments en JSON';
$string["fromlang"] = 'Langue source';
$string["tolang"] = 'Langue cible';
$string["importheading"] = "Importer des éléments JSON ou CSV";
$string['aigentemplatename:wordpractice2'] = 'Pratique de mots 2';
$string['aigentemplatename:fiction_withpics'] = 'Fiction interactive (avec images)';
$string['aigentemplatename:fiction_nopics'] = 'Fiction interactive (sans images)';
$string['aigentemplatedescription:wordpractice2'] = 'À partir d’une liste de mots, de phrases exemples et de la langue maternelle de l’étudiant, ce modèle génère des activités avec images et indices dans la langue maternelle. Jusqu’à 9 mots.';
$string['aigentemplatedescription:fiction_withpics'] = 'Saisissez un scénario détaillé pour obtenir une fiction interactive avec images.';
$string['aigentemplatedescription:fiction_nopics'] = 'Saisissez un scénario détaillé pour obtenir une fiction interactive textuelle.';
$string['audiochat_instructions_instructions'] = 'Les instructions sont un modèle combiné avec le rôle IA, la voix IA, le sujet, les données IA 1 et 2, la production de l’étudiant et sa langue maternelle pour créer le prompt. Variables disponibles : {ai role}, {ai voice}, {target language}, {native language}, {ai data1}, {ai data2}, {student submission}, {topic}.';
$string['openaikey_details'] = 'La clé OpenAI est utilisée pour AudioChat. Non requise pour les autres types.';
$string['audiochatsessionend'] = "Terminer le chat";
$string['audiochat_gradeinstructions_instructions'] = 'Les instructions doivent expliquer comment générer un feedback et un score de 0 à 100. Variables disponibles : {ai role}, {ai voice}, {target language}, {native language}, {ai data1}, {ai data2}, {student submission}, {topic}.';
$string['item_fiction_desc'] = 'Le type Fiction permet de créer une histoire interactive avec des choix. Utilise le format Yarn (avancé).';
$string['fiction:preview'] = 'Aperçu';
$string['fiction:previewmodaltitle'] = 'Aperçu';
$string['fiction:attachments'] = 'Pièces jointes';
$string['shownonoptions'] = 'Afficher les options indisponibles';
$string['shownonoptions_desc'] = 'Si "oui", les options non disponibles seront affichées désactivées. Sinon, elles seront masquées.';
$string['flowthroughmessages'] = 'Défilement automatique';
$string['flowthroughmessages_desc'] = 'Si "oui", les messages s’enchaînent automatiquement jusqu’au choix de l’utilisateur.';
$string['enterfictionyarn'] = 'Saisissez l’histoire au format Yarn. Voir le <a href="https://docs.yarnspinner.dev/beginners-guide" target="_blank">guide Yarn</a> et la <a href="https://support.poodll.com/en/support/solutions/articles/19000171644-item-type-fiction" target="_blank">documentation Poodll</a>.';
$string['fictionyarn'] = 'Yarn (fiction)';
$string['correctfeedback'] = "Feedback correct";
$string['correctfeedbackinstructions'] = "Feedback expliquant la bonne réponse (optionnel).";
$string['audiochat_student_submission'] = 'Production de l’étudiant';
$string['translatetoimport'] = 'Traduire pour l’import';
$string['nativelang'] = 'Langue maternelle de l’apprenant';
$string['audioavatar'] = 'Avatar audio';
$string['audiochat_student_submission_instructions'] = 'Utilisez une production libre précédente. Le texte peut être injecté via {student submission}.';
$string['aicontextheading'] = 'Données de contexte IA';
$string['aicontext_instructions'] = 'Champs optionnels pour enrichir le prompt IA via {ai data1}, {ai data2}.';
$string['perpage'] = 'Par page';
$string['foundlessons'] = '{$a} leçons trouvées';
$string['targetlang'] = 'Langue cible';
$string['nolessonitemfound'] = 'Aucun élément trouvé';
$string['presentationmode'] = 'Mode de présentation';
$string['presentationmode_plain'] = 'Simple';
$string['presentationmode_mobile_chat'] = 'Chat mobile';
$string['presentationmode_storymode'] = 'Mode histoire';
$string['fiction:syntaxcheckbutton'] = 'Vérifier la syntaxe';
$string['yarnsyntaxok'] = 'Syntaxe Yarn correcte';
$string['yarnsyntaxnotok'] = 'Syntaxe Yarn incorrecte';
$string['aimanager'] = 'Gestionnaire IA';
$string['aimanager_desc'] = 'Permet de choisir le fournisseur IA.';
$string['grade_student_submission'] = 'Évaluer la production de l’étudiant';
$string['grade_student_submission_desc'] = 'Les corrections IA utilisent ce fournisseur.';
$string['cloudpoodll'] = 'Cloud Poodll';
$string['choosenativelanguageinstructions'] = 'Définissez votre langue maternelle.';
$string['downloadtranslationmodel'] = 'Télécharger le modèle de traduction';
$string['downloadtranslationmodel_desc'] = 'Cette activité utilise la traduction. Télécharger le modèle ? (une seule fois)';
$string['download'] = 'Télécharger';
$string['downloadingtranslator'] = 'Téléchargement du traducteur : {$a}%';
$string['customlayout'] = 'Mise en page personnalisée';
$string['customkeys'] = 'Touches personnalisées';
$string['customkeys_help'] = 'Ajoutez des touches séparées par des espaces. Ex : "à â æ ç"';
$string['compact'] = 'Compact';
$string['confirmactionmessage'] = '<p>Votre tentative précédente sera supprimée. OK ?</p>';
$string['attemptquestioncountmessage'] = 'Vous avez complété {$a->completed} sur {$a->totallessonitem} étapes.';
$string['fullscreen_toggle'] = 'Mode plein écran';
$string['hidedetailedresults'] = 'Masquer les résultats détaillés';
$string['defaultitemlayout'] = 'Mise en page par défaut';


$string['fluencyresponses'] = 'Saisissez une liste de phrases dans la zone de texte ci-dessous. Chaque élément doit être sur une nouvelle ligne. Vous pouvez ajouter une traduction ou un indice pour chaque phrase en utilisant le format : phrase | <i>indice</i> <br>ex. Hello|<i>Hola</i>';
$string['hint'] = 'Indice';
$string['hintrtl'] = 'Indice RTL';
$string['hintrtl_desc'] = 'Si cette option est cochée, l’indice sera affiché de droite à gauche.';
$string['scatterdefrtl'] = 'Définition RTL';
$string['scatterdefrtl_desc'] = 'Si cette option est cochée, la définition sera affichée de droite à gauche.';
$string['aigentemplatename:narrativefiction_withpics'] = 'Fiction interactive narrative (avec images)';
$string['aigentemplatedescription:fiction_nopics'] = 'Saisissez un plan détaillé de l’histoire pour générer une activité de fiction interactive uniquement textuelle (avec une seule image principale).';
$string['aigentemplatedescription:narrativefiction_withpics'] = 'Saisissez un plan détaillé de l’histoire pour générer une activité de fiction interactive avec moins de choix, plus de narration et des images.';
$string['noitemyet'] = 'Il n’y a encore aucun élément dans cette mini-leçon. Cliquez sur le bouton « Ajouter un élément » et choisissez un type pour commencer.';
$string['slides:fontsmallest'] = 'Le plus petit';
$string['slides:fontlargest'] = 'Le plus grand';

$string['addlessonitem_desc'] = 'Ajoutez manuellement des éléments pour construire votre leçon';
$string['chooselessonitem_desc'] = 'Choisissez une leçon prête à l’emploi depuis la banque de leçons';
$string['generatelessonitem_desc'] = 'Générez une leçon ou des éléments de leçon à l’aide de l’IA';
$string['generate'] = 'Générer';

$string['audiochatstudent'] = 'Étudiant';
//$string['audiochataiassistant'] = 'Assistant IA';
$string['audiochataiassistant'] = 'Gustave';


$string['shadow'] = 'Shadowing vidéo';
$string['cards'] = 'Cartes';
$string['error:invalidyoutubeurl'] = 'L’URL ou l’identifiant de la vidéo YouTube n’est pas valide.';
$string['error:noyoutubetranscript'] = 'Aucun sous-titre n’est disponible pour cette vidéo YouTube.';
$string['error:youtubefetchfailed'] = 'Impossible de récupérer les données depuis YouTube. Veuillez réessayer plus tard.';
$string['details'] = 'Détails';
$string['aigentemplate_itemtypes'] = 'Types d’activités';
$string['aigentemplate_tags'] = 'Étiquettes';
$string['gapfill_shuffleorder'] = 'Mélanger l’ordre des phrases';
$string['gapfill_shuffleorder_desc'] = 'Si cette option est activée, l’ordre des phrases présenté à l’étudiant est aléatoire. Chaque phrase conserve son image et son audio associés.';
$string['listeninggapfillitemsdesc'] = 'Saisissez la liste des éléments dans la zone de texte ci-dessous. Chaque élément doit être sur une nouvelle ligne. Les lettres manquantes doivent être entourées de crochets : [ ]. Le format est :<br>Texte | indice<br>Exemple : This is my d[og].| Un animal de compagnie courant.';
$string['aigrade_feedback_language_help'] = 'Définit la langue dans laquelle les commentaires seront générés. Par défaut, la langue maternelle et la langue cible sont celles définies dans les paramètres de cette mini-leçon. Ce choix peut être remplacé si l’utilisateur a défini sa propre langue maternelle.';
$string['enablenativelanguage_details'] = 'Si cette option est activée, l’étudiant peut choisir sa langue maternelle. Celle-ci remplacera la langue de retour par défaut utilisée par l’IA pour les commentaires.';
$string['generatingitemdata'] = 'Génération de l’activité : {$a}';
$string['aigentemplatename:passagereading'] = 'Lecture et expression orale (générer une histoire)';
$string['aigentemplatename:youtubefinalelesson'] = 'Leçon finale YouTube (écouter et parler)';
$string['aigentemplatename:wordpractice'] = 'Pratique du vocabulaire v1';
$string['aigentemplatename:wordpractice2'] = 'Pratique du vocabulaire v2';
$string['aigentemplatename:audiostory'] = 'Écouter une histoire (générer une histoire)';
$string['aigentemplatename:reading_aic_passageupload'] = 'Lecture + Chat IA (texte importé)';
$string['aigentemplatename:keywords_to_ws_sc_sg'] = 'Pratique du vocabulaire v3';
$string['aigentemplatename:dialog_multichoice'] = 'Écouter 4 dialogues';
$string['aigentemplatename:image_slides'] = 'Diaporama d’images (5)';
$string['aigentemplatename:choose_best_reply'] = 'Choisir la bonne réponse';
$string['aigentemplatename:fiction_withpics'] = 'Histoire interactive (avec images)';
$string['aigentemplatename:fiction_nopics'] = 'Histoire interactive (sans images)';
$string['aigentemplatename:narrativefiction_withpics'] = 'Histoire narrative (avec images)';
$string['aigentemplatename:vocabcards'] = 'Cartes de vocabulaire';
$string['aigentemplatedescription:youtubefinalelesson'] = 'Cette leçon est basée sur une vidéo YouTube. Une histoire simplifiée mais liée à la vidéo est générée, puis des activités de compréhension et d’expression orale sont créées. Enfin, l’étudiant visionne la vidéo originale étudiée.';
$string['aigentemplatedescription:wordpractice'] = 'Saisissez une liste de 5 mots et un thème. Poodll créera une activité comprenant des cartes de vocabulaire, des exercices d’écoute avec saisie, un jeu spatial et une activité d’expression écrite libre.';
$string['aigentemplatedescription:set_of_slides'] = 'Ce modèle crée un diaporama pédagogique à partir d’une description de ce que vous souhaitez enseigner.';
$string['aigentemplatedescription:reading_aic_passagegen'] = 'Saisissez un thème et un niveau de langue. L’IA générera un texte de lecture qui sera utilisé pour une activité de lecture suivie d’une conversation audio basée sur ce texte.';
$string['aigentemplatedescription:reading_aic_passageupload'] = 'Collez un texte de lecture ainsi que 4 questions à réponse courte. Ils seront utilisés pour une activité de lecture puis une conversation audio basée sur ce texte.';
$string['aigentemplatedescription:keywords_to_ws_sc_sg'] = 'Saisissez entre 4 et 10 mots-clés. Ils serviront à générer des cartes de vocabulaire, une activité de remise en ordre des mots, une activité Scatter et un jeu spatial.';
$string['aigentemplatedescription:dialog_multichoice'] = 'Saisissez 4 dialogues. Ils seront transformés en 4 questions à choix multiples où chaque réponse est représentée par une image correspondant au dialogue en cours de lecture. Les dialogues doivent être au format TTS, par exemple : A) J’aime les tartines. [nouvelle ligne] B) Moi aussi.';
$string['aigentemplatedescription:image_slides'] = 'Saisissez 5 descriptions d’images ainsi qu’un style graphique pour générer un diaporama de 5 images.';
$string['aigentemplatedescription:choose_best_reply'] = 'L’étudiant écoute 10 courtes conversations et doit choisir la meilleure réplique parmi 3 propositions. Chaque question est accompagnée d’une image. L’activité est inspirée de la partie compréhension orale du test Eiken niveau 3. Vous devez indiquer le lieu et la situation (généralement un problème) de chaque conversation. Exemple : « deux amis à la plage et l’un d’eux a perdu son portefeuille ».';
$string['aigentemplatedescription:fiction_withpics'] = 'Une histoire interactive avec des images.';
$string['aigentemplatedescription:fiction_nopics'] = 'Une histoire interactive sans images, à l’exception de l’écran d’introduction.';
$string['aigentemplatedescription:narrativefiction_withpics'] = 'Une histoire interactive avec moins de choix, davantage de narration et des illustrations.';
$string['aigentemplatedescription:vocabcards'] = 'Un ensemble de cartes de vocabulaire. Saisissez une liste de mots ou d’expressions séparés par des virgules ainsi que la langue maternelle des étudiants. Des cartes contenant le mot ou l’expression, sa traduction, un exemple de phrase et une image seront créées.';
$string['aigentemplatename:youtubefinale_freewrite'] = 'Leçon finale YouTube (écouter et écrire librement)';
$string['aigentemplatedescription:youtubefinale_freewrite'] = 'Cette leçon est basée sur une vidéo YouTube. Une histoire simplifiée liée à la vidéo est générée, accompagnée d’activités de compréhension et d’expression écrite.';
$string['aigentemplatename:youtubefinale_freespeak'] = 'Leçon finale YouTube (écouter et parler librement)';
$string['aigentemplatedescription:youtubefinale_freespeak'] = 'Cette leçon est basée sur une vidéo YouTube. Une histoire simplifiée liée à la vidéo est générée, accompagnée d’activités de compréhension et d’expression orale.';
$string['aigentemplatename:passagegapfill_generate'] = 'Texte à trous (générer un texte)';
$string['aigentemplatedescription:passagegapfill_generate'] = 'Saisissez une liste de mots-clés, un niveau de langue et un thème. L’IA générera un texte ainsi que plusieurs trous à compléter.';
$string['aigentemplatename:fluency_upload'] = 'Fluidité (importer des phrases)';
$string['aigentemplatedescription:fluency_upload'] = 'Créez une activité de fluidité en fournissant jusqu’à 10 phrases que les étudiants s’entraîneront à prononcer à voix haute. Une image sera générée pour accompagner chaque phrase.';
$string['aigentemplatename:passagereading_generate'] = 'Lecture de texte (générer un texte)';
$string['aigentemplatedescription:passagereading_generate'] = 'Saisissez une liste de mots-clés, un niveau de langue et un thème. L’IA générera un texte de lecture pour une activité de lecture à voix haute.';
$string['aigentemplatename:freespeaking'] = 'Expression orale libre';
$string['aigentemplatedescription:freespeaking'] = 'Une activité d’expression orale libre sur le thème de votre choix.';
$string['aigentemplatename:wordshuffle_generate'] = 'Remettre les mots en ordre (générer des phrases)';
$string['aigentemplatedescription:wordshuffle_generate'] = 'Créez une activité de remise en ordre des mots en saisissant entre 4 et 10 mots-clés. Ils serviront également à générer des phrases ainsi qu’une image pour chacune.';
$string['aigentemplatename:wordshuffle_keywords_generate'] = 'Choisir le bon mot (générer des phrases)';
$string['aigentemplatedescription:wordshuffle_keywords_generate'] = 'Créez une activité « choisir le bon mot » basée sur une activité de remise en ordre des mots. Saisissez entre 4 et 10 mots-clés. Des phrases avec un mot manquant, 4 propositions, une traduction dans la langue maternelle et une image seront générées.';
$string['aigentemplatename:scatter'] = 'Scatter';
$string['aigentemplatedescription:scatter'] = 'Saisissez entre 4 et 10 mots ou expressions. Ils serviront à générer une activité Scatter avec des cartes associées traduites dans la langue maternelle de l’utilisateur.';
$string['aigentemplatename:spacegame'] = 'Jeu spatial';
$string['aigentemplatedescription:spacegame'] = 'Saisissez entre 4 et 10 mots ou expressions. Ils serviront à générer un jeu spatial avec les définitions dans la langue maternelle de l’utilisateur.';
$string['aigentemplatename:multichoice_image'] = 'Question à choix multiples (avec images)';
$string['aigentemplatedescription:multichoice_image'] = 'Une question à choix multiples illustrée, conçue pour vérifier la compréhension d’un texte. Vous devez fournir le texte, le niveau de langue, une courte description de la scène ou du contexte, ainsi qu’un style d’image.';
$string['aigentemplatename:freewriting'] = 'Expression écrite libre';
$string['aigentemplatedescription:freewriting'] = 'Une activité d’expression écrite libre sur le thème indiqué.';
$string['aigentemplatename:listeninggapfill_generate'] = 'Texte à trous - Écoute (générer des phrases)';
$string['aigentemplatedescription:listeninggapfill_generate'] = 'Crée une activité de texte à trous en compréhension orale à partir d’une liste de mots-clés (30 maximum).';
$string['aigentemplatename:speakinggapfill_generate'] = 'Texte à trous - Expression orale (générer des phrases)';
$string['aigentemplatedescription:speakinggapfill_generate'] = 'Crée une activité de texte à trous en expression orale à partir d’une liste de mots-clés (30 maximum).';
$string['aigentemplatename:typinggapfill_generate'] = 'Texte à trous - Saisie (générer des phrases)';
$string['aigentemplatedescription:typinggapfill_generate'] = 'Crée une activité de texte à trous à compléter au clavier à partir d’une liste de mots-clés (30 maximum).';
$string['aigentemplatename:gapfill_set'] = 'Ensemble de textes à trous';
$string['aigentemplatedescription:gapfill_set'] = 'Crée un ensemble complet d’activités de texte à trous (écoute, expression orale et saisie) à partir d’une liste de mots (30 maximum).';
$string['aigentemplatename:audiostory_generate'] = 'Histoire audio (générer une histoire)';
$string['aigentemplatedescription:audiostory_generate'] = 'Saisissez un thème, le niveau de langue des apprenants et un type d’histoire. L’IA préparera une histoire illustrée sous forme de diaporama narré grâce à la synthèse vocale.';
$string['aigentemplatename:shadow'] = 'Shadowing';
$string['aigentemplatedescription:shadow'] = 'Saisissez une URL ou un identifiant YouTube. Il sera transformé en exercice interactif de shadowing.';
$string['aigentemplatename:listenandspeak_generate'] = 'Écouter et parler (générer des phrases)';
$string['aigentemplatedescription:listenandspeak_generate'] = 'Saisissez un thème et un niveau de langue. Une activité « Écouter et parler » de 5 phrases sera créée.';
$string['aigentemplatename:dictation_generate'] = 'Dictée (générer des phrases)';
$string['aigentemplatedescription:dictation_generate'] = 'Saisissez un thème et un niveau de langue. Une dictée de 5 phrases sera créée.';
$string['aigentemplatename:dictationchat_generate'] = 'Dictée avec chat (générer des phrases)';
$string['aigentemplatedescription:dictationchat_generate'] = 'Saisissez un thème et un niveau de langue. Une activité de dictée avec chat de 5 phrases sera créée.';
$string['aigentemplatename:shortanswer'] = 'Réponse courte';
$string['aigentemplatedescription:shortanswer'] = 'Saisissez un thème et un niveau de langue. Une question à réponse courte sera créée.';
$string['aigentemplatename:passagegapfill_upload'] = 'Texte à trous (importer un texte)';
$string['aigentemplatedescription:passagegapfill_upload'] = 'Saisissez un texte. L’IA y ajoutera plusieurs trous à compléter.';
$string['aigentemplatename:passagegapfill_upload_keywords'] = 'Texte à trous (importer un texte et des mots-clés)';
$string['aigentemplatedescription:passagegapfill_upload_keywords'] = 'Saisissez un texte ainsi qu’une liste de mots-clés. L’IA ajoutera plusieurs trous à compléter.';
$string['aigentemplatename:passagereading_upload'] = 'Lecture de texte (importer un texte)';
$string['aigentemplatedescription:passagereading_upload'] = 'Importez un texte. L’IA préparera une activité de lecture à partir de celui-ci.';
$string['aigentemplatename:wordshuffle_upload'] = 'Remettre les mots en ordre (importer des phrases)';
$string['aigentemplatedescription:wordshuffle_upload'] = 'Créez une activité de remise en ordre des mots en fournissant entre 4 et 10 phrases. Une image sera générée pour accompagner chaque phrase.';
$string['aigentemplatename:listenandspeak_upload'] = 'Écouter et parler (importer des phrases)';
$string['aigentemplatedescription:listenandspeak_upload'] = 'Créez une activité « Écouter et parler » en fournissant jusqu’à 5 phrases. Une image sera générée pour accompagner chaque phrase.';
$string['aigentemplatename:dictation_upload'] = 'Dictée (importer des phrases)';
$string['aigentemplatedescription:dictation_upload'] = 'Créez une activité de dictée en fournissant jusqu’à 5 phrases.';
$string['aigentemplatename:dictationchat_upload'] = 'Dictée avec chat (importer des phrases)';
$string['aigentemplatedescription:dictationchat_upload'] = 'Créez une activité de dictée avec chat en fournissant jusqu’à 5 phrases. Une image sera générée pour accompagner chaque phrase.';
$string['aigentemplatename:audiostory_upload'] = 'Histoire audio (importer une histoire)';
$string['aigentemplatedescription:audiostory_upload'] = 'Saisissez un texte comme histoire. L’IA préparera un court diaporama narré avec des images, en utilisant la synthèse vocale pour raconter l’histoire.';

$string['aigentemplatename:fluency_generate'] = 'Fluidité (générer des phrases)';
$string['aigentemplatedescription:fluency_generate'] = 'Crée une activité de fluidité pouvant contenir jusqu’à 10 phrases à partir de mots-clés et du niveau de langue de l’utilisateur. Les étudiants s’entraîneront à prononcer les phrases à voix haute. Une image sera générée pour accompagner chaque phrase.';

$string['aigentemplatename:typinggapfill_upload'] = 'Texte à trous - Saisie (importer des phrases)';
$string['aigentemplatedescription:typinggapfill_upload'] = 'Crée une activité de texte à trous à compléter au clavier à partir de 30 phrases maximum. Une image sera générée pour accompagner chaque phrase.';

$string['aigentemplatename:speakinggapfill_upload'] = 'Texte à trous - Expression orale (importer des phrases)';
$string['aigentemplatedescription:speakinggapfill_upload'] = 'Crée une activité de texte à trous en expression orale à partir de 30 phrases maximum. Une image sera générée pour accompagner chaque phrase.';

$string['aigentemplatename:listeninggapfill_upload'] = 'Texte à trous - Écoute (importer des phrases)';
$string['aigentemplatedescription:listeninggapfill_upload'] = 'Crée une activité de texte à trous en compréhension orale à partir de 30 phrases maximum. Une image sera générée pour accompagner chaque phrase.';

$string['aigentemplatename:reading_speaking_upload'] = 'Lecture et expression orale (importer une histoire et des mots-clés)';
$string['aigentemplatedescription:reading_speaking_upload'] = 'Fournissez une courte histoire et quelques mots-clés tirés de cette histoire. L’IA préparera une narration simple en synthèse vocale, ainsi que des activités de lecture et d’expression orale basées sur l’histoire.';

$string['aigentemplatename:listentothestory_upload'] = 'Écouter l’histoire (importer une histoire)';
$string['aigentemplatedescription:listentothestory_upload'] = 'Importez une courte histoire et indiquez le niveau de langue des apprenants. L’IA préparera une histoire audio narrée avec des images, ainsi que des activités d’écoute et d’expression orale.';

$string['aigentemplatename:wordshuffle_upload_markup'] = 'Remettre les mots en ordre (import avec balisage)';
$string['aigentemplatedescription:wordshuffle_upload_markup'] = 'Crée une activité de remise en ordre des mots en fournissant :
 i) 4 à 10 phrases déjà balisées avec les mots à remettre en ordre, les indices et les distracteurs.
ii) Les mêmes phrases sans balisage, indices ni distracteurs.
 Une image sera générée pour accompagner chaque phrase.';

$string['aigentemplatename:listeninggapfill_upload_markup'] = 'Texte à trous - Écoute (importer des phrases avec balisage)';
$string['aigentemplatedescription:listeninggapfill_upload_markup'] = 'Crée une activité de texte à trous en compréhension orale en fournissant :
 i) 1 à 30 phrases déjà balisées avec les mots à compléter et des indices facultatifs.
ii) Les mêmes phrases sans balisage ni indices.
 Une image sera générée pour accompagner chaque phrase.';

$string['aigentemplatename:speakinggapfill_upload_markup'] = 'Texte à trous - Expression orale (importer des phrases avec balisage)';
$string['aigentemplatedescription:speakinggapfill_upload_markup'] = 'Crée une activité de texte à trous en expression orale en fournissant :
 i) 1 à 30 phrases déjà balisées avec les mots à compléter et des indices facultatifs.
ii) Les mêmes phrases sans balisage ni indices.
 Une image sera générée pour accompagner chaque phrase.';

$string['aigentemplatename:typinggapfill_upload_markup'] = 'Texte à trous - Saisie (importer des phrases avec balisage)';
$string['aigentemplatedescription:typinggapfill_upload_markup'] = 'Crée une activité de texte à trous à compléter au clavier en fournissant :
 i) 1 à 30 phrases déjà balisées avec les mots à compléter et des indices facultatifs.
ii) Les mêmes phrases sans balisage ni indices.
 Une image sera générée pour accompagner chaque phrase.';

$string['aigentemplatename:listenandspeak_upload_markup'] = 'Écouter et parler (importer des phrases avec balisage)';
$string['aigentemplatedescription:listenandspeak_upload_markup'] = 'Crée une activité « Écouter et parler » en fournissant jusqu’à 5 phrases. Vous pouvez également fournir des « réponses correctes » et des « phrases affichées » lorsque l’audio entendu par l’utilisateur diffère de ce qu’il voit ou de ce qu’il doit répondre. Une image sera générée pour accompagner chaque phrase.';

$string['aigentemplatename:spacegame_upload'] = 'Jeu spatial (import)';
$string['aigentemplatedescription:spacegame_upload'] = 'Saisissez jusqu’à 10 paires à associer. Elles seront utilisées pour créer un jeu spatial dans lequel les étudiants associent les paires en tirant sur le bon extraterrestre.';

$string['aigentemplatename:scatter_upload'] = 'Scatter (import)';
$string['aigentemplatedescription:scatter_upload'] = 'Saisissez jusqu’à 10 paires à associer. Elles seront utilisées pour créer une activité Scatter dans laquelle les étudiants associent les paires en touchant les bonnes cartes.';

$string['aigentemplatename:vocabcards_upload_markup'] = 'Cartes de vocabulaire (import avec balisage)';
$string['aigentemplatedescription:vocabcards_upload_markup'] = 'Un ensemble de cartes de vocabulaire. Chaque carte peut contenir jusqu’à 3 lignes de texte : généralement mot-clé | traduction du mot-clé | phrase d’exemple avec le mot-clé.
Une image sera générée pour chaque carte.';

$string['aigentemplatename:fluency_upload_markup'] = 'Fluidité (importer des phrases avec balisage)';
$string['aigentemplatedescription:fluency_upload_markup'] = 'Crée une activité de fluidité en fournissant jusqu’à 10 phrases que les étudiants s’entraîneront à prononcer à voix haute. Une image sera générée pour accompagner chaque phrase.';

$string['aigentemplatename:freewriting_upload'] = 'Expression écrite libre (importer une question)';
$string['aigentemplatedescription:freewriting_upload'] = 'Saisissez une question ou un thème sur lequel les étudiants devront écrire.';

$string['aigentemplatename:freespeaking_upload'] = 'Expression orale libre (importer une question)';
$string['aigentemplatedescription:freespeaking_upload'] = 'Saisissez une question ou un thème sur lequel les étudiants devront parler.';

$string['aigentemplatename:audiochat_generate'] = 'Chat audio (générer une question)';
$string['aigentemplatedescription:audiochat_generate'] = 'Saisissez un thème et le niveau de langue de l’utilisateur. L’IA générera une question de discussion adaptée à l’apprenant.';

$string['aigentemplatename:audiochat_upload'] = 'Chat audio (importer une question de discussion)';
$string['aigentemplatedescription:audiochat_upload'] = 'Saisissez une question de discussion qui sera utilisée dans un chat audio entre l’utilisateur et l’IA.';

$string['aigentemplatename:grammar_slides'] = 'Diaporama d’explication grammaticale';
$string['aigentemplatedescription:grammar_slides'] = 'Ce modèle crée un diaporama pour enseigner le point de grammaire indiqué. La langue cible de la MiniLesson sera utilisée pour les exemples, et la langue d’instruction choisie sera utilisée pour les explications.';

$string['aigentemplatename:grammar_choosewords_v1'] = 'Leçon de grammaire (diaporama + choisir les mots V1)';
$string['aigentemplatedescription:grammar_choosewords_v1'] = 'Fournissez le point de grammaire, jusqu’à 6 mots-clés ou expressions clés, ainsi que la langue d’instruction. Une leçon sera créée avec un diaporama explicatif, une activité « choisir le mot » et une activité « écrire le mot ». Utilisez ce modèle pour les points de grammaire où le choix du bon mot ou de la bonne expression est important (par exemple « by » ou « until »). Pour ce modèle, les choix proposés doivent être les mêmes pour chaque phrase.';

$string['aigentemplatename:grammar_shufflewords'] = 'Leçon de grammaire (diaporama + remettre les mots en ordre)';
$string['aigentemplatedescription:grammar_shufflewords'] = 'Fournissez le point de grammaire, les mots-clés et la langue d’instruction. Une leçon sera créée avec un diaporama explicatif, une activité de remise en ordre des mots et une activité « écrire le mot ». Utilisez ce modèle pour les points de grammaire où l’ordre des mots dans la phrase est important. C’est aussi un modèle généralement utile, car il peut être utilisé avec la plupart des séries de phrases.';

$string['aigentemplatename:grammar_choosewords_v2'] = 'Leçon de grammaire (diaporama + choisir les mots V2)';
$string['aigentemplatedescription:grammar_choosewords_v2'] = 'Fournissez le point de grammaire, le niveau de langue et la langue d’instruction. Une leçon sera créée avec un diaporama explicatif, une activité « choisir le mot » et une activité « écrire le mot » (6 phrases chacune). Utilisez ce modèle pour les points de grammaire où la composition de l’expression ou la forme du mot est importante (par exemple les comparatifs : « more X » ou « Xer »). Pour ce modèle, les choix proposés doivent être différents pour chaque phrase.';

$string['processaigentask'] = 'Traiter la tâche de génération du modèle IA';

$string['wordshuffle_shuffleorder'] = 'Mélanger l’ordre des phrases';
$string['wordshuffle_shuffleorder_desc'] = 'Si cette option est activée, l’ordre des séries de phrases présenté à l’étudiant est aléatoire. Chaque phrase conserve son image et son audio associés.';

$string['cantchat'] = 'Le chat n’est PAS disponible. Pour utiliser ce type d’activité, vous devez avoir défini une clé {$a} valide dans les paramètres d’administration de l’activité MiniLesson.';

$string['freewriting:gradingprompt4'] = 'Raconter à nouveau l’histoire - nécessite un résumé de l’histoire (ai data1)';
$string['freewriting:gradingprompt_dec4'] = 'L’étudiant raconte à nouveau une histoire. L’histoire est : [{topic}]
Attribuez 2 points pour chaque phrase grammaticalement correcte utilisée par l’étudiant dans sa réponse. Attribuez 1 point si la phrase est presque correcte.';

$string['freewriting:feedbackprompt2'] = 'Décrire l’image - nécessite une description de l’image (ai data1)';
$string['freewriting:feedbackprompt3'] = 'Raconter à nouveau l’histoire - nécessite un résumé de l’histoire (ai data1)';
$string['freewriting:feedbackprompt_dec3'] = 'Expliquez simplement les principales erreurs de grammaire. Suggérez les éléments de l’histoire que l’étudiant n’a pas inclus.';

$string['freespeaking:gradingprompt3'] = 'Décrire l’image - nécessite une description de l’image (topic)';
$string['freespeaking:gradingprompt4'] = 'Raconter à nouveau l’histoire - nécessite un résumé de l’histoire (topic)';
$string['freespeaking:gradingprompt_dec4'] = 'L’étudiant raconte à nouveau une histoire. L’histoire qu’il raconte est : [{topic}]
Attribuez 2 points pour chaque phrase grammaticalement correcte utilisée par l’étudiant dans sa réponse. Attribuez 1 point si la phrase est presque correcte.';

$string['freespeaking:feedbackprompt2'] = 'Décrire l’image - nécessite une description de l’image (topic)';
$string['freespeaking:feedbackprompt3'] = 'Raconter à nouveau l’histoire - nécessite un résumé de l’histoire (topic)';
$string['freespeaking:feedbackprompt_dec3'] = 'Expliquez simplement les erreurs de grammaire importantes. Ne commentez pas l’orthographe ni la ponctuation. Suggérez les éléments de l’histoire que l’étudiant n’a pas décrits.';

$string['defaulttargetlanguage'] = 'Langue cible par défaut';
$string['defaultnativelanguage'] = 'Langue maternelle par défaut';

$string['templateagentonly'] = 'Agent uniquement (masqué)';
$string['templateagentonly_help'] = 'Si cette option est définie sur Oui, ce modèle est masqué sur la page de génération IA visible par les utilisateurs. Il reste disponible pour les agents et pour toute personne l’utilisant directement. Utilisez cette option pour les modèles destinés à un usage automatisé/par agent ou qui ne seraient généralement pas nécessaires.';

$string['fiction:taptotranslate'] = 'Activer la traduction au toucher';
$string['fiction:taptotranslate_desc'] = 'Si cette option est activée, une icône de traduction apparaît en bas à droite de chaque bloc de texte de l’histoire. En appuyant dessus, le texte est traduit dans la langue maternelle de l’apprenant et la traduction s’affiche directement sous le texte. Nécessite un navigateur prenant en charge la traduction sur l’appareil, par exemple Chrome.';
$string['fiction:taptranslatearia'] = 'Traduire ce texte';

$string['targetwordcount_instructions'] = 'Si le nombre cible de mots est supérieur à 0, les étudiants seront pénalisés dans la mesure où leur nombre total de mots est inférieur au nombre cible.';

$string['geminiapikey_details'] = 'La clé Gemini est utilisée pour AudioChat. Elle n’est actuellement ni requise ni utilisée pour les autres types d’activités.';
$string['geminiapikey'] = 'Clé API Gemini';

$string['aihelper_modal_title'] = 'Assistant IA';
$string['aihelper_modal_instruction'] = 'Créez ou modifiez du contenu dans l’éditeur en demandant à l’assistant IA.';
$string['aihelper_prompt_label'] = 'Comment puis-je vous aider ?';
$string['aihelper_prompt_placeholder'] = 'Ex. : Créer une présentation de 5 diapositives sur le cycle de l’eau...';
$string['aihelper_placeholder_slides'] = 'Ex. : Créer une présentation de 5 diapositives sur le cycle de l’eau...';
$string['aihelper_placeholder_fiction'] = 'Ex. : Créer une histoire de 15 nœuds sur un dauphin élevé par des requins, qui apprend à accepter sa différence.';
$string['aihelper_response_label'] = 'Réponse de l’IA';
$string['aihelper_generating'] = 'Génération du contenu...';
$string['aihelper_generate_btn'] = 'Générer';
$string['aihelper_apply_btn'] = 'Appliquer dans l’éditeur';
$string['completed'] = 'Terminé';
$string['progress'] = 'Progression';
$string['notfound'] = 'Introuvable';
$string['jobnotfound'] = 'La tâche avec cet identifiant est introuvable.';
$string['notaccess'] = 'Vous n’avez pas l’autorisation de consulter ce statut.';

// Tableau de bord du compte.
$string['accountdashboard'] = 'Tableau de bord du compte';
$string['audio'] = 'Audio';
$string['end'] = 'Expiration';
$string['failedfetchsubreport'] = 'Impossible de récupérer le rapport des abonnements.';
$string['maxmonth'] = 'Meilleur mois';
$string['ninety_days'] = '90 jours';
$string['no_subscriptions'] = 'Aucun abonnement.';
$string['oneeighty_days'] = '180 jours';
$string['per_plugin'] = 'Par plugin (12 derniers mois)';
$string['per_recording_type'] = 'Par type d’enregistrement';
$string['poodll_users'] = 'Utilisateurs Poodll';
$string['recording_min'] = 'Minutes d’enregistrement';
$string['recordings'] = 'Enregistrements';
$string['start'] = 'Début';
$string['subscription'] = 'Abonnement';
$string['thirty_days'] = '30 jours';
$string['threehundredsixtyfive_days'] = '365 jours';
$string['video'] = 'Vidéo';