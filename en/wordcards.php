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
 * Displays information about the wordcards in the course.
 *
 * @package mod_wordcards
 * @author  Frédéric Massart - FMCorz.net
 */

defined('MOODLE_INTERNAL') || die();

$string['activitycompleted'] = 'Activity completed';
// $string['completedmsg'] = 'Completed message';
// $string['completedmsg_help'] = 'This is the message displayed on the final screen of the activity when the student complete the last practice.';
$string['completionwhenfinish'] = 'The student has finished the activity.';
$string['completionwhenfinishdesc'] = 'Complete all the activity steps.';
$string['completionwhenlearned'] = 'The student has learned all the words.';
$string['completionwhenlearneddesc'] = 'Learn all the words.';
$string['completiondetail:finish'] = 'Finish all steps in the activity';
$string['completiondetail:learned'] = 'Learn all {$a} words';
$string['congrats'] = 'Congratulations!';
$string['congratsitsover'] = '<div  style="text-align: center;">You have finished this activity. Thank you!</div>';
$string['definition'] = 'Definition';
$string['shortdefinition'] = 'Def:';
$string['definition_help'] = 'Enter definition of the term(word/phrase) here. It should be short but still tell the student what the term means.';
// $string['definitions'] = 'Definitions';
$string['deleteallentries'] = 'Delete all user attempts and stats (keep the terms/definitions)';
$string['deleteterm'] = 'Delete term \'{$a}\'';
$string['delimiter'] = 'Delimiter Character';
$string['delim_tab'] = 'tab';
$string['delim_comma'] = 'comma';
$string['delim_pipe'] = 'pipe';
$string['description'] = 'Description';
$string['editterm'] = 'Edit term \'{$a}\'';
$string['finishscatterin'] = '<h4 style="text-align: center;">Congratulations!</h4><br><br><p style="text-align: center;">Your score is [[totalgrade]]%</p>';
$string['wordcards:addinstance'] = 'Add an instance';
$string['wordcards:view'] = 'View the module';
$string['wordcards:viewreports'] = 'View reports';
$string['wordcards:manageattempts'] = 'Manage Attempts';
$string['wordcards:manage'] = 'Manage';
$string['wordcards:export'] = 'Export Wordcards';
$string['wordcards:push'] = 'Push settings from one instance to others';
$string['reviewactivity'] = 'Review';

$string['privacy:metadata:attemptid'] = 'The unique identifier of a users Wordcards attempt.';
$string['privacy:metadata:wordcardsid'] = 'The unique identifier of a Wordcards activity instance.';
$string['privacy:metadata:userid'] = 'The user id for the Wordcards user';
$string['privacy:metadata:grade1'] = 'The score for the attempt on step 1 ';
$string['privacy:metadata:grade2'] = 'The score for the attempt on step 2 ';
$string['privacy:metadata:grade3'] = 'The score for the attempt on step 3 ';
$string['privacy:metadata:grade4'] = 'The score for the attempt on step 4 ';
$string['privacy:metadata:grade5'] = 'The score for the attempt on step 5 ';
$string['privacy:metadata:totalgrade'] = 'The total score for the attempt';
$string['privacy:metadata:timecreated'] = 'The time that the record was created';
$string['privacy:metadata:timemodified'] = 'The last time the record was modified';
$string['privacy:metadata:attempttable'] = 'Stores the scores and other user data associated with a WordCards attempt.';
$string['privacy:metadata:seentable'] = 'Stores a record of WordCards words the user has commenced learning.';
$string['privacy:metadata:associationstable'] = 'Stores a record of failed or successful attempts to associate words with their definitions.';
$string['privacy:metadata:mywordstable'] = 'Stores a record of WordCards words the user has selected for their personal learning list.';
$string['privacy:metadata'] = 'The Poodll Wordcards plugin does store personal data.';
$string['privacy:metadata:modid'] = 'The unique identifier of a Poodll Wordcards activity instance.';
$string['privacy:metadata:termid'] = 'The unique identifier of a Poodll Wordcards word.';
$string['privacy:metadata:lastfail'] = 'The last time the user failed to associate a specific Poodll Wordcards word correctly.';
$string['privacy:metadata:lastsuccess'] = 'The last time the user succcessfully associated a specific Poodll Wordcards word correctly.';
$string['privacy:metadata:failcount'] = 'The total number of times the user failed to associate a specific Poodll Wordcards word correctly.';
$string['privacy:metadata:successcount'] = 'The total number of times the user successfully associated a specific Poodll Wordcards word correctly.';
$string['privacy:metadata:deflangpref'] = 'The users selected definitions language for Poodll WordCards (probably their native language).';


// $string['reviewactivityfinished'] = 'You finished the review session in {$a->seconds} seconds.';

$string['gotit'] = 'Got it';
$string['import'] = 'Import';
$string['importdata'] = 'Import Data';
$string['importresults'] = 'Successfully imported {$a->imported} rows. {$a->failed} rows failed.';
$string['introduction'] = 'Introduction';
// $string['learnactivityfinished'] = 'You finished the practice session in {$a->seconds} seconds.';
// $string['finishedstepmsg'] = 'Finished message';
// $string['finishedstepmsg_help'] = 'This is the message displayed when you end a practice session.';
$string['step1termcount'] = 'Step 1 word set size';
$string['step2termcount'] = 'Step 2 word set size';
$string['step3termcount'] = 'Step 3 word set size';
$string['step4termcount'] = 'Step 4 word set size';
$string['step5termcount'] = 'Step 5 word set size';
$string['loading'] = 'Loading';
$string['learnactivity'] = 'New Words';
$string['markasseen'] = 'Mark as seen';
$string['modulename'] = 'Poodll Wordcards';
$string['modulename_help'] = 'The wordcards activity module enables a teacher to create custom wordcards games for encouraging students learning new words.';
$string['modulenameplural'] = 'Poodll Wordcards';
$string['name'] = 'Name';
$string['nodefinitions'] = 'No words were added yet.';
$string['noteaboutseenforteachers'] = 'Note: Teachers\' seen status are not saved.';
$string['pluginadministration'] = 'Wordcards administration';
$string['pluginname'] = 'Poodll Wordcards';
$string['reallydeleteterm'] = 'Are you sure you want to delete the term \'{$a}\'?';
$string['removeuserdata'] = 'Remove Wordcards user data';
// $string['setup'] = 'Setup';
$string['managewords'] = 'Manage Words';
// $string['skipreview'] = 'Hide first review session';
// $string['skipreview_help'] = 'Hide the review session of this specific activity if no wordcards activities have been completed in this course.';
$string['tabdefinitions'] = 'Start';
$string['tabmanagewords'] = 'Words Admin';
$string['tabimport'] = 'Import';
$string['term'] = 'Term';
$string['term_help'] = 'Enter the word or phrase to be learned here.';
$string['termadded'] = 'The term \'{$a}\' has been added.';
$string['termdeleted'] = 'The term has been deleted.';
$string['termnotseen'] = 'Term not seen';
$string['termsaved'] = 'The term \'{$a}\' has been saved.';
$string['termseen'] = 'Term seen';

$string['step1practicetype'] = 'Step 1 activity';
$string['step2practicetype'] = 'Step 2 activity';
$string['step3practicetype'] = 'Step 3 activity';
$string['step4practicetype'] = 'Step 4 activity';
$string['step5practicetype'] = 'Step 5 activity';
$string['matchselect'] = 'Choose match';
$string['matchtype'] = 'Type match';
$string['dictation'] = 'Dictation';
$string['scatter'] = 'Scatter';
$string['speechcards'] = 'Speech Cards';


$string['apiuser'] = 'Poodll API User ';
$string['apiuser_details'] = 'The Poodll account username that authorises Poodll on this site.';
$string['apisecret'] = 'Poodll API Secret ';
$string['apisecret_details'] = 'The Poodll API secret. See <a href= "https://support.poodll.com/support/solutions/articles/19000083076-cloud-poodll-api-secret">here</a> for more details';
$string['useast1'] = 'US East';
$string['tokyo'] = 'Tokyo, Japan';
$string['sydney'] = 'Sydney, Australia';
$string['dublin'] = 'Dublin, Ireland';
$string['ottawa'] = 'Ottawa, Canada';
$string['frankfurt'] = 'Frankfurt, Germany';
$string['london'] = 'London, U.K';
$string['saopaulo'] = 'Sao Paulo, Brazil';
$string['mumbai'] = 'Mumbai, India';
$string['singapore'] = 'Singapore';
$string['bahrain'] = 'Bahrain';
$string['capetown'] = 'Capetown, South Africa';
$string['forever'] = 'Never expire';

$string['en-us'] = 'English (US)';
$string['en-gb'] = 'English (GB)';
$string['en-au'] = 'English (AU)';
$string['en-in'] = 'English (IN)';
$string['en-nz'] = 'English (NZ)';
$string['en-za'] = 'English (ZA)';
$string['es-es'] = 'Spanish (ES)';
$string['es-us'] = 'Spanish (US)';
$string['fr-fr'] = 'French (FR.)';
$string['fr-ca'] = 'French (CA)';
$string['fil-ph'] = 'Filipino';
$string['ko-kr'] = 'Korean(KR)';
$string['pt-br'] = 'Portuguese (BR)';
$string['it-it'] = 'Italian(IT)';
$string['da-dk'] = 'Danish';
$string['de-de'] = 'German (DE)';
$string['de-at'] = 'German (AT)';
$string['de-ch'] = 'German (CH)';
$string['hi-in'] = 'Hindi (IN)';
$string['ko-kr'] = 'Korean';
$string['ar-ae'] = 'Arabic (Gulf)';
$string['ar-sa'] = 'Arabic (Modern Standard)';
$string['zh-cn'] = 'Chinese (Mandarin-Mainland)';
$string['nl-nl'] = 'Dutch (NL)';
$string['nl-be'] = 'Dutch (BE)';
$string['en-ie'] = 'English (Ireland)';
$string['en-wl'] = 'English (Wales)';
$string['en-ab'] = 'English (Scotland)';
$string['fa-ir'] = 'Farsi';
$string['he-il'] = 'Hebrew';
$string['id-id'] = 'Indonesian';
$string['ja-jp'] = 'Japanese';
$string['ms-my'] = 'Malay';
$string['pt-pt'] = 'Portuguese (PT)';
$string['ru-ru'] = 'Russian';
$string['ta-in'] = 'Tamil';
$string['te-in'] = 'Telugu';
$string['tr-tr'] = 'Turkish';
$string['mi-nz'] = 'Maori';
$string['bg-bg'] = 'Bulgarian'; // Bulgarian
$string['cs-cz'] = 'Czech'; // Czech
$string['el-gr'] = 'Greek'; // Greek
$string['hr-hr'] = 'Croatian'; // Croatian
$string['hu-hu'] = 'Hungarian'; // Hungarian
$string['lt-lt'] = 'Lithuanian'; // Lithuanian
$string['lv-lv'] = 'Latvian'; // Latvian
$string['sk-sk'] = 'Slovak'; // Slovak
$string['sl-si'] = 'Slovenian'; // Slovenian
$string['is-is'] = 'Icelandic'; // Icelandic
$string['mk-mk'] = 'Macedonian'; // Macedonian
$string['no-no'] = 'Norwegian'; // Norwegian
$string['sr-rs'] = 'Serbian'; // Serbian
$string['vi-vn'] = 'Vietnamese'; // Vietnamese

$string['uk-ua'] = 'Ukranian';
$string['eu-es'] = 'Basque';
$string['fi-fi'] = 'Finnish';
$string['hu-hu'] = 'Hungarian';

$string['sv-se'] = 'Swedish';
$string['no-no'] = 'Norwegian';
$string['nb-no'] = 'Norwegian'; // unused
$string['pl-pl'] = 'Polish';
$string['ro-ro'] = 'Romanian';
$string['xx-xx'] = 'Other'; // Other

$string['awsregion'] = 'AWS Region';
// $string['region']='AWS Region';
$string['expiredays'] = 'Days to keep file';
$string['displaysubs'] = '{$a->subscriptionname} : expires {$a->expiredate}';
$string['noapiuser'] = "No API user entered. Word Cards will not work correctly.";
$string['noapisecret'] = "No API secret entered. Word Cards will not work correctly.";
$string['credentialsinvalid'] = "The API user and secret entered could not be used to get access. Please check them.";
$string['appauthorised'] = "Poodll Word Cards is authorised for this site.";
$string['appnotauthorised'] = "Poodll Word Cards is NOT authorised for this site.";
$string['refreshtoken'] = "Refresh license information";
$string['notokenincache'] = "Refresh to see license information. Contact Poodll support if there is a problem.";
// these errors are displayed on activity page
$string['nocredentials'] = 'API user and secret not entered. Please enter them on <a href="{$a}">the settings page.</a> You can get them from <a href="https://poodll.com/member">Poodll.com.</a>';
$string['novalidcredentials'] = 'API user and secret were rejected and could not gain access. Please check them on <a href="{$a}">the settings page.</a> You can get them from <a href="https://poodll.com/member">Poodll.com.</a>';
$string['nosubscriptions'] = "There is no current subscription for this site/plugin.";

$string['transcriber'] = 'Transcriber';
$string['transcriber_details'] = 'The transcription engine to use';
$string['transcriber_auto'] = 'Open STT (Strict)';
$string['transcriber_poodll'] = 'Guided STT (Poodll)';
$string['enabletts_details'] = 'TTS is currently not implemented';
$string['ttslanguage'] = 'Target Language';
$string['ttsvoice'] = 'TTS Voice';
$string['ttsvoice_help'] = 'These are the machine voices that will read your words when users press the listen icons or do dictation activity. TTS is not used if you have uploaded an audio file for the word. The voices are limited to those for the language and dialect specified in the \'target language\' setting for the activity.';
$string['alternates'] = 'Acceptable mistranscribes';
$string['alternates_help'] = 'Enter a comma separated list of acceptable speech recognition mistranscriptions here. eg For the word \'seventy\' , \'70\' and \'seven tea\' would be ok so you might enter <i>\'70, seven tea\'</i>. Only use this if recognition is failing for the term.';

$string['audiofile'] = 'Audio file';
$string['audiofile_help'] = 'Upload an audio file illustrating the pronunciation of the word/phrase. Ths will be used in place of machine voices in dication and when students use the audio player icons for the word.';
$string['imagefile'] = 'Image file';
$string['imagefile_help'] = 'Upload an image file to be displayed on the cards.';
$string['starttest'] = 'Begin';
// $string['quit'] = 'Quit';
$string['next'] = 'Next';
$string['previous'] = 'Prev';
// $string['ok'] = 'OK';
$string['listen'] = 'Listen';
// $string['delete'] = 'Delete';
// $string['submit'] = 'Submit';
$string['flip'] = 'Flip';
$string['word'] = 'Word';
$string['meaning'] = 'Meaning';
$string['correct'] = 'Correct';
// $string['backtostart'] = 'Back to Start';
$string['loading'] = 'Loading';
$string['title_matchselect'] = 'Choose the Answer';
$string['title_matchtype'] = 'Type the Answer';
$string['title_dictation'] = 'Listen and Type';
// $string['title_scatter'] = 'Match the Words';
$string['title_speechcards'] = 'Say the Words';
$string['title_listenchoose'] = 'Listen and Choose';
$string['title_spacegame'] = 'Space Game';

$string['review'] = 'Review';
$string['practice'] = 'Practice';

$string['title_noactivity'] = 'None';
$string['title_matchselect_rev'] = 'Choose the Answer (Review)';
$string['title_matchtype_rev'] = 'Type the Answer (Review)';
$string['title_dictation_rev'] = 'Listen and Type (Review)';
// $string['title_scatter_rev'] = 'Match the Words (Review)';
$string['title_speechcards_rev'] = 'Say the Words (Review)';
$string['title_listenchoose_rev'] = 'Listen and Choose (Review)';
$string['title_spacegame_rev'] = 'Space Game (Review)';

$string['title_vocablist'] = 'Get Ready';
$string['instructions_matchselect'] = 'Tap the best match from the choices below for the highlighted word.';
$string['instructions_matchtype'] = 'Type the best match for the highlighted word.';
$string['instructions_dictation'] = 'Listen and type the word(s) that you hear. Tap the blue button to hear the word(s).';
// $string['instructions_scatter'] = 'Match the cards with the same meaning, by tapping them,';
$string['instructions_speechcards'] = 'Tap the blue button and speak the word(s) shown on the card. Speak slowly and clearly.';
$string['instructions_vocablist'] = 'Review the words that will be used in this activity. Tap the word card or the \'Flip\' button to show the other side of the cards. When you are ready, tap \'Begin\' to test your knowledge of these words.';
// $string['pushtospeak'] = 'Tap to Speak';

// Reports
$string['itemsperpage'] = "Items per Page";
$string['itemsperpage_details'] = "";
$string['tabreports'] = "Reports";
$string['reports'] = "Reports";
$string['deleteattemptconfirm'] = "Really delete this attempt?";
$string['delete'] = "Delete";
$string['attemptsreport'] = "All Attempts Report";
$string['attemptsheading'] = "All Attempts Report";
$string['basicheading'] = "Basic Report";
$string['id'] = "ID";
$string['name'] = "Name";
$string['username'] = "Username";
$string['grade'] = "Grade";
$string['grade_p'] = "Total Grade(%)";
$string['timecreated'] = "Created";
$string['deletenow'] = "Delete";

$string['returntoreports'] = "Return to Reports";
$string['exportexcel'] = "Export to Excel";
$string['nodataavailable'] = "No  data available";

$string['maxattempts'] = "Maximum Attempts";
$string['maxattempts_details'] = "The maximum number of allowed attempts";
$string['unlimited'] = "Unlimited";

// grades report
$string['grades'] = "Grades";
$string['userattemptsheading'] = "User Attempts Report";
$string['gradesheadinglatest'] = "Grades Report (latest attempt)";
$string['gradesheadinghighest'] = "Grades Report (highest scoring attempt)";
$string['gradesreport'] = "Grades Report";
$string['grade1_p'] = "Step1(%)";
$string['grade2_p'] = "Step2(%)";
$string['grade3_p'] = "Step3(%)";
$string['grade4_p'] = "Step4(%)";
$string['grade5_p'] = "Step5(%)";
$string['attempts'] = "Attempts";
$string['reportsmenutop'] = "Choose from the reports available below. You can export the data to CSV using the button on the lower right of the report when displayed.";
$string['try_again'] = "Try again";
$string['next_step'] = "Next";
$string['done'] = 'Next';
$string['skip'] = 'Skip';
$string['reattempt'] = 'Try Again';
$string['continue'] = 'Continue';
$string['reattempttitle'] = 'Really Try Again?';
$string['reattemptbody'] = 'If you continue your previous attempt will be replaced with this one. OK?';
$string['importinstructions'] = 'You can import lists of words using the \'import data\' text area below. Each line should contain one term(word/phrase) and it\'s definition separated by a delimiter. Optionally specify 3rd and 4th fields for TTS voice and model sentence. You can choose a delimiter from the dropdown box below. The format of each line should be:<br><br> new-word | definition | TTS Voice | Model Sentence<br><br> Each line therefore should look something like this:<br> <i>Bonjour | Hello| Celine | Bonjour Monsieur</i>';
$string['managewordsinstructions'] = "Use the 'Add New' button to add new words for the activity. You can view, edit and delete previously added words from the table at the bottom of the page. Only the term and definition are required.";
$string['model_sentence'] = 'Model sentence';
$string['model_sentence_audio'] = 'Model sentence audio';
$string['model_sentence_help'] = 'Enter model sentence of the term(word/phrase) here. It should be short but still tell the student what the term means.';
$string['audioandimages'] = 'Audio and Images';
$string['addnewterm'] = "Add New";
$string['enablesetuptab'] = "Enable setup tab";
$string['enablesetuptab_details'] = "Show a tab containing the activity instance settings to admins. Not super useful in most cases.";
$string['setup'] = "Setup";
$string['tabsetup'] = "Setup";

$string['showimageflip'] = "Show images on flip cards";
$string['showimageflip_details'] = "If the wordcards set has images, and this property is true, the image will be displayed under the word on the flip cards screen before a practice activity.";
$string['frontfaceflip'] = "Front face on flip cards";
$string['frontfaceflip_details'] = "Show the term or definition on the front face of the wordcards.";
$string['pushalltogradebook'] = "Re-push grades to gradebook";
$string['gradespushed'] = "Grades pushed to gradebook";
$string['gradesadmin'] = "Grades Administration";
$string['cancelbuttontext'] = "Quit Attempt";
$string['cancelattempttitle'] = "Quit Attempt?";
$string['cancelattemptbody'] = "This will quit your attempt and take you back to the start. Really quit?";
$string['cancelattempt'] = "Quit Attempt";

$string['gradelatest'] = "most recent attempt";
$string['gradehighest'] = "highest scoring attempt";
$string['gradeoptions'] = 'Grade Options';
$string['gradeoptions_help'] =
    'When there are multiple attempts by a user, this setting determines which attempt to use when grading';
$string['gradeoptions_details'] =
    'NB This determines the gradebook entry. The activity grading report will display the attempt selected here.';
$string['letsaddwords'] = "Lets add some words..";
$string['addwords'] = "Add Words";

$string['freetrial'] = "Get Cloud Poodll API Credentials and a Free Trial";
$string['freetrial_desc'] = "A dialog should appear that allows you to register for a free trial with Poodll. After registering you should login to the members dashboard to get your API user and secret. And to register your site URL.";
$string['fillcredentials'] = "Set API user and secret with existing credentials";
$string['ww_words_instructions'] = " Wordcards will fetch a list of definitions for the words you entered.";
$string['ww_words'] = "Enter Words separated by commas, e.g cat,dog";
$string['ww_words_fetch'] = "Fetch Definitions";
$string['word_wizard'] = "Word Wizard";
$string['wordwizard'] = "Word Wizard";
$string['ww_langdef'] = "Definitions Language";
$string['model'] = "Model";
$string['use'] = "Use";
$string['dismiss'] = "Dismiss";
$string['deflanguage'] = "Definitions Language";
$string['deflanguage_help'] = "This preselects the correct language when using the dictionary in the Word Wizard, and the default language in the language chooser. (Not all langs are available ..sorry)";
$string['wizardinstructions'] = 'Word Wizard will search the {$a} dictionary for the words you enter. <ol><li>Enter a comma separated list of {$a} words.</li><li>Press the \'Fetch Definitions\' button.</li><li>Select the best entry per term, edit the entry content and press the \'use\' button to add the word to the wordcards activity.</li></ol>';
$string['nodefinitionfound'] = "No definition found";
$string['viewstart'] = "Activity open";
$string['viewend'] = "Activity close";
$string['viewstart_help'] = "If set, prevents a student from entering the activity before the start date/time.";
$string['viewend_help'] = "If set, prevents a student from entering the activity after the closing date/time.";
$string['activitydate:submissionsdue'] = 'Due:';
$string['activitydate:submissionsopen'] = 'Opens:';
$string['activitydate:submissionsopened'] = 'Opened:';
$string['activityisnotopenyet'] = "This activity is not open yet.";
$string['activityisclosed'] = "This activity is closed.";
$string['open'] = "Open: ";
$string['until'] = "Until: ";
$string['activityopenscloses'] = "Activity open/close dates";
$string['wordcards:preview'] = "Can preview Wordcards activities";
$string['mode_steps'] = "Steps mode";
$string['mode_free'] = "Free mode";
$string['mode_freeaftersteps'] = "Steps mode then Free mode";
$string['mode_session'] = "Session mode";
$string['mode_freeaftersession'] = "Session mode then Free mode";
$string['journeymode'] = "Mode";
$string['journeymode_details'] = "How students move through the activity: steps, or free mode";
$string['m'] = "m";
$string['addtomywords'] = "Click to add to My Words";
$string['removefrommywords'] = "Click to remove from My Words";
$string['saving'] = "Saving";
$string['freemode'] = "Free mode";
$string['stepsmode'] = "Steps mode";
$string['exitfreemode'] = "Exit free mode";
$string['mywords'] = "My words";
$string['empty'] = "empty";
$string['words'] = "Words";
$string['practicetype'] = "Practice type";
$string['freemodeintropara1'] = "Choose the words and practice type from the menu. Practice as often as you wish, your scores are not recorded. Tap the + button to add a word to your 'my words' set.";
$string['seenwords'] = "Review Words";
$string['selectedpoolhasnowords'] = "The selected set of words is empty. Please add words, or choose another set of words";
$string['startintropara1'] = "Review the words below. When you are ready press the continue button to practice the words. You must finish each practice step to complete the activity. Tap the + button to add a word to your 'my words' set.";
$string['flashcards'] = "Flashcards";
$string['grid'] = "Grid";
$string['selectwordstolearn'] = "Select words to learn";
$string['freemodenotavailable'] = "The Site Administrator has not enabled free mode.";
$string['sessionmodenotavailable'] = "The Site Administrator has not enabled session mode.";
$string['def_wordstoshow'] = "Max words to show (free mode)";
$string['def_wordstoshow_details'] = "How many words to show the user from the wordpool when in free mode.";
$string['reportstable'] = "Reports Style";
$string['reportstable_details'] = "Ajax tables are faster to use and can sort data. Paged tables load faster but are harder to navigate with.";
$string['reporttableajax'] = "Ajax Tables";
$string['reporttablepaged'] = "Paged Tables";
$string['totalscore'] = "Total Score";
$string['backtocourse'] = "Back to Course";
$string['morepractice'] = "More Practice";
$string['lc_termterm'] = "Audio: term, Options: terms";
$string['lc_termdef'] = "Audio: term, Options: definitions";
$string['lcoptions'] = "Listen and Choose options";
$string['lcoptions_details'] = "Display terms as options, or word definitions";
$string['msoptions'] = "Choose the Answer options";
$string['msoptions_details'] = "Display terms as options, or word definitions";
$string['sgoptions'] = "Space Game options";
$string['sgoptions_details'] = "Display terms as aliens, or definitions as aliens";
$string['scoptions'] = "Speechcards options";
$string['scoptions_details'] = "Display new word, or model sentence on the speechcard";

$string['animations'] = "Animations";
$string['animations_details'] = "Transitions between item subtypes are animated. If fancy animation causes trouble, choose plain.";
$string['anim_fancy'] = "Fancy animation";
$string['anim_plain'] = "Plain animation";
$string['eventwordcardsstepsubmitted'] = 'Wordcards step submitted';
$string['eventwordcardsattemptsubmitted'] = 'Wordcards attempt submitted';
$string['eventwordcardswordlearned'] = 'Wordcards word learned';
$string['listofwords'] = "List of words";

// spacegame
$string['removescores'] = 'Remove all user scores';
$string['score'] = 'Score: {$a->score} Lives: {$a->lives}';
$string['scoreheader'] = 'Score';
$string['points'] = 'Points';
$string['scoreslink'] = 'View all attempts';
$string['scoreslinkhelp'] = 'View all player attempts and scores';
$string['spacetostart'] = 'Press space or click to start';
$string['shootthepairs'] = 'shoot the pairs';
$string['sound'] = 'Sound';
$string['playerscores'] = 'Player scores';
$string['notyetplayed'] = 'Not yet played';
$string['achievedhighscoreof'] = 'Achieved a high score of {$a}';
$string['playedxtimeswithhighscore'] = 'Played {$a->times} times. The last game ended with a high score of {$a->score}';
$string['fullscreen'] = 'Fullscreen';
$string['howtoplay'] = 'How to play';
$string['endofgame'] = 'Your score was: {$a}. Press space or click to restart.';
$string['emptyquiz'] = 'There are no multiple choice questions in the selected category.';
$string['howtoplay_help'] = 'You can move the ship by using the arrow keys, or by dragging it with the mouse.


Press the spacebar or click the mouse button to shoot, or tap with two fingers anywhere on the game.

Clear as many questions as possible by shooting the correct answer.  Good Luck!';

// Video Examples
$string['videoexamples'] = 'Video Examples';
$string['videoexamples_details'] = 'In definitions/start mode automatically generated video examples can be shown on the back face of the card.';
$string['videoexamples_help'] = 'In definitions/start mode automatically generated video examples can be shown on the back face of the card.';

$string['learnpoint'] = 'Learned Point';
$string['learnpoint_details'] = 'The number of correct associations during practice or review at which to consider the word as learned.';
$string['learnpoint_help'] = 'The number of correct associations during practice or review at which to consider the word as learned.';
$string['learned'] = 'Learned';
$string['notlearned'] = 'Not yet learned';
$string['termslearned'] = 'Terms Learned';
$string['learnedheading'] = 'Terms Learned';
$string['learnedreport'] = 'Terms Learned';
$string['learned_p'] = "Learned(%)";
$string['learned_progress'] = "Learned(%)";
$string['userlearned'] = "User Learned Terms";
$string['userlearnedheading'] = 'User Learned Terms: {$a}';
$string['reportsmenutoptext'] = "Review attempts on WordCards activities using the reports below.";
$string['learnedreport_explanation'] = 'A list of the number of terms learned by each user in this activity.';
$string['gradesreport_explanation'] = 'The grades for each user in this activity.';
$string['attemptsreport_explanation'] = 'A summary of WordCards attempts per user in this activity.';
$string['deflang_other'] = "Other";
$string['bulkdelete'] = 'Delete selected';
$string['reallybulkdelete'] = 'Are you sure you want to delete the selected term ?';
$string['notts'] = "No TTS";
$string['invalidvoice'] = "Invalid Voice";
$string['developer'] = "Developer";
$string['exportallterms'] = "Export Terms";
$string['exportterms_expl'] = "Export all the terms in the current wordcards activity to a simple CSV file. This file can be used to import the terms into another wordcards activity. No image or audio files are exported. You can choose the delimiter for the exported file rows. Tabs will work best if you plan to edit with Excel or Google Sheets.";
$string['glossary'] = "Glossary";
$string['importfromtext'] = "Import From Text";
$string['importfromglossary'] = "Import From Glossary";
$string['importedglossaryentries'] = 'Imported {$a} glossary entries';
$string['loadthensave'] = 'Confirm before importing';
$string['loadedglossaryentries'] = 'Load {$a} glossary entries';
$string['glossaryimportinstructions'] = "Select a glossary to import terms from. The terms will be imported into the current wordcards activity.";
$string['importwords'] = 'Import Words';
$string['ms_termattop'] = 'Term at top, Definitions as choices';
$string['ms_defattop'] = 'Definition at top, Terms as choices';
$string['sg_termasalien'] = 'Terms as aliens (enemies)';
$string['sg_defasalien'] = 'Definition as aliens (enemies)';
$string['wc_termasreadable'] = 'Term on speech card';
$string['wc_modelsentenceasreadable'] = 'Model sentence on speech card';
$string['learningactivityoptions'] = 'Learning Activity Options';
$string['learningactivityoptions_details'] = 'Configure how the different learning activity types behave in WordCards.';
$string['stepsmodeoptions'] = 'Steps Mode Options';
$string['stepsmodeoptions_details'] = 'Choose the learning activity for each step when in steps mode. Note that you have the choice of using the new words pool, or review words pool in the learning activity.';
$string['freemodesettings'] = 'Free Mode Defaults';
$string['freemodesettings_details'] = 'Choose which activity types are available in WordCards free mode by default. These defaults can be overriden in the activity instance.';
$string['freemodeoptions'] = 'Free Mode Options';
$string['freemodeoptions_details'] = 'Enable the learning activities that should be available to students in free mode.';
$string['never'] = 'Never';
$string['choose_deflang'] = "change"; // "Choose definitions language";
$string['showlangchooser'] = "Show language chooser";
$string['showlangchooser_help'] = "The language chooser allows the user to select the language that the wordcard definition is displayed in. Multiple language definitions will only be available if the word as added via the Word Wizard. The language chooser is available from Moodle 4.3 and newer.";
$string['showlangchooser_details'] = "The language chooser allows the user to select the language that the wordcard definition is displayed in. Multiple language definitions will only be available if the word as added via the Word Wizard. The language chooser is available from Moodle 4.3 and newer.";
$string['advancedheader'] = "Advanced";
$string['notmasterinstance'] = 'You can not push settings from this WordCards activity unless master instance is selected in activity settings (advanced).';
$string['masterinstance'] = 'Master Instance';
$string['masterinstance_details'] = 'Master instance allows the author to push the individual settings of one Wordcards activity to other WordCards activities. When enabled a \'Push\' tab will appear in the activity tabset. Use this with caution, and not at all unless you are sure of your intention.';
$string['push'] = 'Push';
$string['pushpage'] = 'Push Page';
$string['pushpage_explanation'] = "Use the buttons on this page to push settings from this WordCards instance to other WordCards activities. Be careful. There is no going back. So be sure of your intention before using.";
$string['pushpage_mastercount'] = 'Pushing settings from this activity will not affect <b>{$a}</b> other activities in the same scope, because they are master instances.';
$string['pushpage_clonecount'] = 'Pushing settings from this activity will affect <b>{$a}</b> other activities.';
$string['pushpage_noclones'] = 'This activity IS a master instance, but there are no other activities are in the affected scope. So there is nothing to push settings to. Check that your target WordCards are not set as Master instances<br><br>';
$string['pushpage_done'] = 'Settings pushed to {$a} WordCards activities';
$string['pushmode_none'] = 'NOT a master instance';
$string['pushmode_modulename'] = 'for WordCard activities with this activity name';
$string['pushmode_course'] = 'for WordCard activities in this course';
$string['pushmode_site'] = 'for all WordCard activities sitewide';
$string['pushpage_scopemodule'] = 'Scope: WordCard activities (sitewide) with this activity name: {$a}';
$string['pushpage_scopecourse'] = 'Scope: WordCard activities in this course: {$a}';
$string['pushpage_scopesite'] = 'Scope: All WordCard activities SITEWIDE';
$string['pushpage_scopenone'] = 'Scope: NO WordCard activities';
$string['wordpreview'] = 'Words Preview';
$string['bigtitle_wordpreview'] = 'Select the words you already know.';
$string['title_wordpreview'] = 'Words Preview';
$string['title_wordpreview_rev'] = 'Words Preview (Review)';
$string['instructions_wordpreview'] = 'Select the words that you already know from the list of words below. Then press start. We will test you on those words and then mark them as learned.';
$string['selectedwordstest'] = 'We will test you on the words that you selected. If you know them, then we will mark them as "learned." OK?';
$string['nowordsselected'] = 'Continue on to practice words the words you don\'t know?';
$string['selfselect-selected'] = 'I know this word already';

