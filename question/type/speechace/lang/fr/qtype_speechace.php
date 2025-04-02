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
 * Strings for component 'qtype_speechace', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    qtype
 * @subpackage speechace
 * @copyright  2017 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['speechace'] = 'SpeechAce';
$string['pluginname'] = 'SpeechAce';
$string['pluginname_help'] = "En réponse à une question, l'élève lit le texte à voix haute. SpeechAce attribue automatiquement une note selon la qualité de la prononciation.";
$string['pluginname_link'] = 'question/type/speechace';
$string['pluginnameadding'] = 'Ajout d’une question de type SpeechAce';
$string['pluginnameediting'] = 'Modification d’une question de type SpeechAce';
$string['pluginnamesummary'] = "Permet une réponse orale à un texte en anglais. SpeechAce évalue automatiquement la prononciation en fonction de sa proximité avec l'accent américain standard.";
$string['scoringinfo'] = 'Texte de la réponse';
$string['scoringinfo_help'] = "Indiquez le mot, la phrase ou le paragraphe que l’élève doit prononcer. Vous pouvez inclure la ponctuation.  
Vous pouvez également fournir un audio de référence en enregistrant votre voix ou en générant un audio automatiquement via le bouton de rafraîchissement.  
L'audio devra être régénéré à chaque modification du texte.";

$string['dialect_default'] = 'Dialecte par défaut';
$string['dialect'] = 'Dialecte';
$string['dialect_description'] = 'Dialecte par défaut utilisé pour l’évaluation SpeechAce et l’audio de référence dans les nouvelles questions.';
$string['dialect_us_english'] = 'Anglais américain';
$string['dialect_gb_english'] = 'Anglais britannique';
$string['dialect_fr_french'] = 'Français';
$string['dialectinfomissing'] = 'Information sur le dialecte manquante';
$string['dialectinfoinvalid'] = 'Valeur de dialecte invalide';
$string['dialect_default_temp'] = 'Dialecte temporaire';
$string['yesoption'] = 'Oui';
$string['nooption'] = 'Non';
$string['numericscore']= 'Score numérique';
$string['numericscore_default']= 'Oui';
$string['numericscore_description']= 'Par défaut, le score est affiché en pourcentage';
$string['numericscore_descritptivename'] = 'Afficher le score en pourcentage';
$string['scoremessages'] = 'Messages de score';
$string['scoremessages_description'] = 'Permet d’écrire et de personnaliser le texte affiché lors de l’évaluation';
$string['scoremessages_defaultinfo'] = 'Cliquez sur Réinitialiser les messages pour revenir aux valeurs par défaut';
$string['scoremessages_default_textOne'] = "Parfait ! Vous êtes natif ?";
$string['scoremessages_default_textTwo']= "Pas mal du tout.";
$string['scoremessages_default_textThree']= "Ce n’est pas très bon. Essayez encore une fois.";
$string['showanswer'] = 'Afficher le texte à prononcer';
$string['showanswer_always'] = 'Toujours';
$string['showanswer_result'] = 'Quand le score SpeechAce est affiché';
$string['showresult'] = 'Afficher le score SpeechAce';
$string['showresult_immediately'] = 'Immédiatement après l’enregistrement';
$string['showresult_review'] = 'Lors de la relecture du quiz';
$string['showexpertaudio'] = "Afficher l'audio de référence";
$string['showexpertaudio_always'] = 'Toujours';
$string['showexpertaudio_result'] = 'Quand le score SpeechAce est affiché';
$string['showexpertaudio_missing']= "L'audio de référence est manquant";
$string['showexpertaudio_infoinvalid']= "La valeur de référence est invalide";
$string['pleaserecordaudio'] = "Veuillez enregistrer votre audio.";
$string['productkey'] = 'Clé produit';
$string['productkey_description'] = 'Clé fournie par SpeechAce pour accéder à leur service web.';
$string['productkeyempty'] = 'La clé produit ne peut pas être vide.';
$string['productkeyinvalid'] = 'La clé produit est invalide.';
$string['scoringinfomissing'] = 'Texte requis pour l’évaluation manquant';
$string['scoringinfotextmissing'] = 'Veuillez fournir un texte pour l’évaluation';
$string['scoringinfotextunknownerror'] = "Impossible de valider le texte pour l’évaluation";
$string['scoringinfotextoutofvocab'] = 'Certains mots dans le texte ne sont pas autorisés : {$a->detail_message}';
$string['scoringinfotexttoolong'] = 'Le texte est trop long. Il doit contenir moins de {$a->detail_message} caractères.';
$string['scoringinfosourcetypeinvalid'] = 'Sélection invalide. Veuillez choisir "Utiliser votre audio" ou "Utiliser l’audio SpeechAce"';
$string['scoringinfomoodlekeygenericerror'] = "Impossible de valider votre audio. Essayez d’enregistrer à nouveau ou d’utiliser l’audio SpeechAce.";
$string['scoringinfomoodlekeydetailmessage'] = "Erreur rencontrée lors de la validation de votre audio : {$a->detail_message} Essayez d’enregistrer à nouveau ou d’utiliser l’audio SpeechAce.";
$string['error_productkey_missing'] = "La clé produit SpeechAce n'est pas renseignée. Si vous êtes administrateur, allez dans Administration du site → Plugins → Types de question → SpeechAce.";
$string['error_productkey_invalid'] = "La clé produit SpeechAce est invalide. Si vous êtes administrateur, fournissez une clé valide dans Administration du site → Plugins → Types de question → SpeechAce.";
$string['error_productkey_expired'] = "La clé produit SpeechAce a expiré. Si vous êtes administrateur, contactez SpeechAce pour un renouvellement.";
$string['error_http_api_call'] = "SpeechAce n’a pas pu accéder au service distant. Si vous êtes administrateur, contactez SpeechAce pour obtenir de l’aide.";
$string['error_no_speech'] = 'Aucune parole détectée';

$string['scorecomment_good'] = "Bien";
$string['scorecomment_stressmore'] = "Accentuez davantage";
$string['scorecomment_stressless'] = "Moins d'accentuation";
$string['scorecomment_missing'] = "Omission";
$string['scorecomment_silent'] = "Muet";

$string['moodlejs_NoAudioError'] = "Votre navigateur ne prend pas entièrement en charge l'audio.";
$string['moodlejs_NoAudioError_custom'] = "Veuillez utiliser la dernière version de Google Chrome ou Mozilla Firefox.";
$string['moodlejs_NoRecordingError'] = "Votre navigateur ne prend pas en charge l'enregistrement audio.";
$string['moodlejs_NoRecordingError_custom'] = "Veuillez utiliser la dernière version de Google Chrome ou Mozilla Firefox.";
$string['moodlejs_AssertionError'] = "Échec de l'assertion :";
$string['moodlejs_NoAnswerError'] = "Impossible d'entendre ce que vous dites.";
$string['moodlejs_NoAnswerError_custom'] = "Veuillez vérifier votre microphone ou essayer de parler plus fort.";
$string['moodlejs_WrongAnswerError'] = "La réponse est incorrecte.";
$string['moodlejs_WrongAnswerError_custom'] = "Veuillez réessayer.";
$string['moodlejs_InvalidResponseData'] = "La réponse est invalide.";
$string['moodlejs_InvalidResponseData_custom'] = "Veuillez recharger la page et réessayer.";
$string['moodlejs_MicrophoneDeniedError'] = "Impossible d'utiliser votre microphone.";
$string['moodlejs_MicrophoneDeniedError_custom'] = "Nous devons utiliser votre microphone pour évaluer votre prononciation.";
$string['moodlejs_MicrophoneNotConnectedError'] = "Impossible de trouver un microphone.";
$string['moodlejs_MicrophoneNotConnectedError_custom'] = "Veuillez vérifier votre microphone et réessayer.";
$string['moodlejs_TTSError'] = "Impossible de convertir le texte en parole.";
$string['moodlejs_TTSError_custom'] = "Veuillez réessayer.";
$string['moodlejs_FlashPendingError'] = "Initialisation de Flash.";
$string['moodlejs_FlashPendingError_custom'] = "Veuillez patienter quelques secondes et réessayer.";
$string['moodlejs_ScoringError'] = "Impossible d'évaluer la parole.";
$string['moodlejs_ScoringError_custom'] = "Veuillez réessayer.";
$string['moodlejs_ScoringFormatError'] = "Résultats d'évaluation inconnus.";
$string['moodlejs_ScoringFormatError_custom'] = "Veuillez actualiser la page et réessayer. Si cela ne fonctionne pas, contactez SpeechAce.";
$string['moodlejs_UnknownPageError'] = "Page inconnue.";
$string['moodlejs_UnknownPageError_custom'] = "Veuillez vérifier l'URL et corriger les erreurs. Si elle semble correcte, contactez SpeechAce.";
$string['moodlejs_UnauthorizedPageError'] = "Page non autorisée.";
$string['moodlejs_UnauthorizedPageError_custom'] = "Veuillez relancer la page.";
$string['moodlejs_SignInError'] = "Erreur de connexion.";
$string['moodlejs_MissingAnswerError'] = "Réponse manquante";
$string['moodlejs_MissingAnswerError_custom'] = "Veuillez répondre à la question. Si vous l'avez fait, contactez SpeechAce.";
$string['moodlejs_UnknownQuestionTypeError'] = "Type de question inconnu.";
$string['moodlejs_UnknownQuestionTypeError_custom'] = "Nous avons rencontré une erreur interne. Veuillez contacter SpeechAce.";
$string['moodlejs_QuizNotFoundError_1'] = "Page (";
$string['moodlejs_QuizNotFoundError_2'] = ") non trouvée.";
$string['moodlejs_QuizNotFoundError_custom'] = "Veuillez vérifier l'URL et corriger les fautes de frappe. Si elle semble correcte, contactez SpeechAce.";
$string['moodlejs_QuestionNotFoundError_1'] = "Question (";
$string['moodlejs_QuestionNotFoundError_2'] = ") non trouvée.";
$string['moodlejs_QuestionNotFoundError_custom'] = "Veuillez vérifier l'URL et corriger les fautes de frappe. Si elle semble correcte, contactez SpeechAce.";
$string['moodlejs_UnknownWordsError'] = "Hors du vocabulaire de SpeechAce.";
$string['moodlejs_UnknownWordsError_custom'] = "Ces mots sont inconnus de SpeechAce : ";
$string['moodlejs_TextTooLongError'] = "Texte trop long.";
$string['moodlejs_TextTooLongError_custom'] = "Le nombre de caractères ne peut pas dépasser ";
$string['moodlejs_TextSpeechValidationError'] = "Impossible de valider le texte.";
$string['moodlejs_TextSpeechValidationError_custom_1'] = "Veuillez réessayer. Si le problème persiste, contactez SpeechAce. (Code d'erreur : ";
$string['moodlejs_TextSpeechValidationError_custom_2'] = "Veuillez réessayer. Si le problème persiste, contactez SpeechAce.";
$string['moodlejs_CourseNotFoundError_1'] = "Page (";
$string['moodlejs_CourseNotFoundError_2'] = ") non trouvée.";
$string['moodlejs_CourseNotFoundError_custom'] = "Veuillez vérifier l'URL et corriger les fautes de frappe. Si elle semble correcte, contactez SpeechAce.";
$string['moodlejs_AjaxError'] = "Veuillez réessayer.";
$string['moodlejs_CourseAttemptNotFoundError_1'] = "Tentative de cours (";
$string['moodlejs_CourseAttemptNotFoundError_2'] = ") non trouvée.";
$string['moodlejs_CourseAttemptNotFoundError_custom'] = "Veuillez réessayer. Si le problème persiste, contactez SpeechAce.";
$string['moodlejs_NotImplementedError'] = "Non implémenté.";
$string['moodlejs_extractErrorMessages_short'] = "Oups ! Quelque chose s'est mal passé.";
$string['moodlejs_extractErrorMessages_detailed'] = "Veuillez réessayer.";
$string['moodlejs_SoundLike'] = "Ressemble à ";
$string['moodlejs_PlayStopExampleAudio'] = "Lire ou arrêter l'audio d'exemple";
$string['moodlejs_StopAudio'] = "Arrêter la lecture de l'audio";
$string['moodlejs_StartPlayingYourAudio'] = "Commencer la lecture de votre audio";
$string['moodlejs_StopPlayingYourAudio'] = "Arrêter la lecture de votre audio";
$string['moodlejs_StartPlayingExampleAudio'] = "Commencer la lecture de l'audio d'exemple";
$string['moodlejs_StopPlayingExampleAudio'] = "Arrêter la lecture de l'audio d'exemple";
$string['moodlejs_HideScoreDetail'] = "Masquer les détails de l'évaluation : ";
$string['moodlejs_ShowScoreDetail'] = "Afficher les détails de l'évaluation : ";
$string['moodlejs_Select'] = "Sélectionner";
$string['moodlejs_Syllable'] = "Syllabe";
$string['moodlejs_Phone'] = "Son";
$string['moodlejs_Score'] = "Score";
$string['moodlejs_StartRecordingAudio'] = "Commencer l'enregistrement audio";
$string['moodlejs_StopRecordingAudio'] = "Arrêter l'enregistrement audio";
$string['moodlejs_HideInfo'] = "Masquer les informations";
$string['moodlejs_ShowInfo'] = "Afficher les informations";
$string['moodlejs_UnableStartRecording'] = "Impossible de commencer l'enregistrement : ";
$string['moodlejs_UnableStopRecording'] = "Impossible d'arrêter l'enregistrement : ";
$string['moodlejs_UnableUnmarshalBlob'] = "Impossible d'analyser le fichier d'enregistrement : ";
$string['moodlejs_UnableStartPlayback'] = "Impossible de commencer la lecture : ";
$string['moodlejs_UnableStopPlayback'] = "Impossible d'arrêter la lecture : ";
$string['moodlejs_FlashError_1'] = "Votre version de Flash est obsolète. Veuillez suivre https://helpx.adobe.com/fr/flash-player.html pour mettre à jour";
$string['moodlejs_FlashError_2'] = "Flash n'est pas pris en charge sur mobile ou tablette.";
$string['moodlejs_FlashError_3'] = "Votre navigateur prend en charge Flash, mais il est désactivé. Veuillez l'activer.";
$string['moodlejs_FlashError_4'] = "Flash n'est pas disponible. Veuillez suivre https://helpx.adobe.com/fr/flash-player.html pour installer";
$string['moodlejs_TapRedWords'] = "Appuyez/Cliquez sur les mots rouges pour voir comment vous améliorer.";
$string['moodlejs_AnswerSaved'] = "Réponse enregistrée";
$string['moodlejs_Say'] = "Dites : ";
$string['moodlejs_Review'] = "Revoir";
$string['moodlejs_FetchingAudio'] = "Récupération de l'audio : ";
$string['moodlejs_AudioSaved'] = "Audio enregistré";
$string['moodlejs_UnableSaveAudio'] = "Impossible d'enregistrer l'audio.";
$string['moodlejs_TryAgain'] = "Veuillez réessayer.";
$string['moodlejs_RecordYourAudio'] = "Enregistrez votre audio";
$string['moodlejs_UseSpeechAceAudio'] = "Utiliser l'audio SpeechAce du texte de réponse";
$string['moodlejs_PlaceHolder'] = "Tapez le texte à évaluer ici...";
$string['moodlejs_MessageScoreGreaterThan'] = "Message quand le score est plus grand ou égal à ";
$string['moodlejs_MessageButLessThan'] = "mais moins que ";
$string['moodlejs_MessageScoreLessThan'] = "Message quand le score est moins que ";
$string['moodlejs_MessageReset'] = " Réinitialiser les messages";
