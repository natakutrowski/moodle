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
$string['pluginname_help'] = 'In response to a question the respondent speaks the text. SpeechAce automatically assigns a grade based on how well the speech is.';
$string['pluginname_link'] = 'question/type/speechace';
$string['pluginnameadding'] = 'Adding a SpeechAce scoring question';
$string['pluginnameediting'] = 'Editing a SpeechAce scoring question';
$string['pluginnamesummary'] = 'Allows a speech response for a piece of English text. SpeechAce grades the speech automatically based on how close the speech is to General American pronunciation and accent.';
$string['scoringinfo'] = 'Answer Text';
$string['scoringinfo_help'] = 'Specify the word, sentence or paragraph text that the student is expected to speak. You may include any necessary punctuation. 
Furthermore, you may provide a reference audio by either recording your own voice or automatically generating an audio by pressing the refresh button. The reference audio will have to be regenerated each time the specified text is modified.';

$string['dialect_default'] = 'Default Dialect';
$string['dialect'] = 'Dialect';
$string['dialect_description'] = 'Default Dialect for Speechace scoring and reference audio in new questions.';
$string['dialect_us_english'] = 'US English';
$string['dialect_gb_english'] = 'UK English';
$string['dialect_fr_french'] = 'French';
$string['dialectinfomissing'] = 'Dialect info is missing';
$string['dialectinfoinvalid'] = 'Dialect value is invalid';
$string['dialect_default_temp'] = 'Dialect temp';
$string['yesoption'] = 'Yes';
$string['nooption'] = 'No';
$string['numericscore']= 'Numeric Score';
$string['numericscore_default']= 'Yes';
$string['numericscore_description']= 'Default is to show the percentage score';
$string['numericscore_descritptivename'] = 'Show percentage score';
$string['scoremessages'] = 'Scoring Messages';
$string['scoremessages_description'] = 'To Write and Customize the text upon question evaluation';
$string['scoremessages_defaultinfo'] = 'Click Reset Messages for defaults';
$string['scoremessages_default_textOne'] = "You got it! Are you a native speaker?";
$string['scoremessages_default_textTwo']= "You are not bad.";
$string['scoremessages_default_textThree']= "That doesn't sound good. You can try again.";
$string['showanswer'] = 'Show Answer Text';
$string['showanswer_always'] = 'Always';
$string['showanswer_result'] = 'When SpeechAce score is shown';
$string['showresult'] = 'Show SpeechAce Score';
$string['showresult_immediately'] = 'Immediately after recording';
$string['showresult_review'] = 'During quiz review';
$string['showexpertaudio'] = 'Show Reference Audio';
$string['showexpertaudio_always'] = 'Always';
$string['showexpertaudio_result'] = 'When SpeechAce score is shown';
$string['showexpertaudio_missing']= 'Reference Audio is missing';
$string['showexpertaudio_infoinvalid']= 'Reference Value is invalid';
$string['pleaserecordaudio'] = 'Please record your audio.';
$string['productkey'] = 'Product Key';
$string['productkey_description'] = 'Product key from SpeechAce to access SpeechAce web service.';
$string['productkeyempty'] = 'Product key cannot be empty.';
$string['productkeyinvalid'] = 'Product key is invalid.';
$string['scoringinfomissing'] = 'Missing input for information for computer scoring';
$string['scoringinfotextmissing'] = 'Please provide the text for scoring';
$string['scoringinfotextunknownerror'] = 'Unable to validate the text for scoring';
$string['scoringinfotextoutofvocab'] = 'Some of the words in the text are not allowed: {$a->detail_message}';
$string['scoringinfotexttoolong'] = 'The text is too long. Make sure that it is less than {$a->detail_message} characters.';
$string['scoringinfosourcetypeinvalid'] = 'Invalid selection. Please select "Use your audio" or "Use SpeechAce audio"';
$string['scoringinfomoodlekeygenericerror'] = 'Unable to validate your audio. Please try saving again, recording another audio, or using SpeechAce audio.';
$string['scoringinfomoodlekeydetailmessage'] = 'Encountered error validing your audio: {$a->detail_message} Please try saving again, recording another audio, or using SpeechAce audio.';
$string['error_productkey_missing'] = 'SpeechAce product key is not set. If you are an administrator, please specify it at Site administration -> Plugins -> Question types -> SpeechAce';
$string['error_productkey_invalid'] = 'SpeechAce product key is not valid. If you are an administrator, please provide a valid product key at Site administration -> Plugins -> Question types -> SpeechAce';
$string['error_productkey_expired'] = 'SpeechAce product key has expired. If you are an administrator, please contact SpeechAce for renewal';
$string['error_http_api_call'] = 'SpeechAce was unable to access remote service. If you are an administrator, please contact SpeechAce for help';
$string['error_no_speech'] = 'No speech is detected';

$string['scorecomment_good'] = "Good";
$string['scorecomment_stressmore'] = "Stress more";
$string['scorecomment_stressless'] = "Stress less";
$string['scorecomment_missing'] = "Missing";
$string['scorecomment_silent'] = "Silent";

$string['moodlejs_NoAudioError'] = "Your browser doesn't have full audio support.";
$string['moodlejs_NoAudioError_custom'] = "Please use the latest version Google Chrome or Mozilla Firefox instead.";
$string['moodlejs_NoRecordingError'] = "Your browser doesn't support audio recording.";
$string['moodlejs_NoRecordingError_custom'] = "Please use the latest version Google Chrome or Mozilla Firefox instead.";
$string['moodlejs_AssertionError'] = "Assertion failure:";
$string['moodlejs_NoAnswerError'] = "Couldn't hear what you say.";
$string['moodlejs_NoAnswerError_custom'] = "Please check your microphone or try speaking louder.";
$string['moodlejs_WrongAnswerError'] = "The answer is not correct.";
$string['moodlejs_WrongAnswerError_custom'] = "Please try again.";
$string['moodlejs_InvalidResponseData'] = "The response is invalid.";
$string['moodlejs_InvalidResponseData_custom'] = "Please reload the page and try again.";
$string['moodlejs_MicrophoneDeniedError'] = "Unable to use your microphone.";
$string['moodlejs_MicrophoneDeniedError_custom'] = "We need to use your microphone to score your pronunciation.";
$string['moodlejs_MicrophoneNotConnectedError'] = "Unable to find a microphone.";
$string['moodlejs_MicrophoneNotConnectedError_custom'] = "Please check your microphone and try again.";
$string['moodlejs_TTSError'] = "Unable to fetch speech from text.";
$string['moodlejs_TTSError_custom'] = "Please try again.";
$string['moodlejs_FlashPendingError'] = "Initializing Flash.";
$string['moodlejs_FlashPendingError_custom'] = "Please wait for a few seconds and try again.";
$string['moodlejs_ScoringError'] = "Unable to score the speech.";
$string['moodlejs_ScoringError_custom'] = "Please try again.";
$string['moodlejs_ScoringFormatError'] = "Unknown scoring results.";
$string['moodlejs_ScoringFormatError_custom'] = "Please refresh the page and try again. If it doesn't help, contact SpeechAce.";
$string['moodlejs_UnknownPageError'] = "Unknown page.";
$string['moodlejs_UnknownPageError_custom'] = "Please check the URL and correct any typos. If it looks correct, contact SpeechAce.";
$string['moodlejs_UnknownMathXLPageError'] = "Unknown page.";
$string['moodlejs_UnknownMathXLPageError_custom'] = "Please relaunch the page to try again. If the problem persists, contact course administrator.";
$string['moodlejs_UnauthorizedPageError'] = "Unauthorized page.";
$string['moodlejs_UnauthorizedPageError_custom'] = "Please relaunch the page again.";
$string['moodlejs_SignInError'] = "Sign In Error.";
$string['moodlejs_MissingAnswerError'] = "Answer is missing";
$string['moodlejs_MissingAnswerError_custom'] = "Please answer the question. If you did, contact SpeechAce.";
$string['moodlejs_UnknownQuestionTypeError'] = "Unknown question type.";
$string['moodlejs_UnknownQuestionTypeError_custom'] = "We encountered some internal error. Please contact SpeechAce.";
$string['moodlejs_QuizNotFoundError_1'] = "Page (";
$string['moodlejs_QuizNotFoundError_2'] = ") not found.";
$string['moodlejs_QuizNotFoundError_custom'] = "Please check the URL and correct any typos. If it looks correct, contact SpeechAce.";
$string['moodlejs_QuestionNotFoundError_1'] = "Question (";
$string['moodlejs_QuestionNotFoundError_2'] = ") not found.";
$string['moodlejs_QuestionNotFoundError_custom'] = "Please check the URL and correct any typos. If it looks correct, contact SpeechAce.";
$string['moodlejs_UnknownWordsError'] = "Out of SpeechAce vocabulary.";
$string['moodlejs_UnknownWordsError_custom'] = "These words are unknown to SpeechAce: ";
$string['moodlejs_TextTooLongError'] = "Text too long.";
$string['moodlejs_TextTooLongError_custom'] = "The number of characters cannot exceed ";
$string['moodlejs_TextSpeechValidationError'] = "Unable to validate the text.";
$string['moodlejs_TextSpeechValidationError_custom_1'] = "Please try again. If the problem persists, contact SpeechAce. (Error code: ";
$string['moodlejs_TextSpeechValidationError_custom_2'] = "Please try again. If the problem persists, contact SpeechAce.";
$string['moodlejs_CourseNotFoundError_1'] = "Page (";
$string['moodlejs_CourseNotFoundError_2'] = ") not found.";
$string['moodlejs_CourseNotFoundError_custom'] = "Please check the URL and correct any typos. If it looks correct, contact SpeechAce.";
$string['moodlejs_AjaxError'] = "Please try again.";
$string['moodlejs_CourseAttemptNotFoundError_1'] = "Course attempt (";
$string['moodlejs_CourseAttemptNotFoundError_2'] = ") not found.";
$string['moodlejs_CourseAttemptNotFoundError_custom'] = "Please retry. If it happens again, contact SpeechAce.";
$string['moodlejs_NotImplementedError'] = "Not implemented.";
$string['moodlejs_extractErrorMessages_short'] = "Oops! Something went wrong.";
$string['moodlejs_extractErrorMessages_detailed'] = "Please try again.";
$string['moodlejs_SoundLike'] = "Sound like ";
$string['moodlejs_PlayStopExampleAudio'] = "Play or Stop example audio";
$string['moodlejs_StopAudio'] = "Stop playing audio";
$string['moodlejs_StartPlayingYourAudio'] = "Start playing your audio";
$string['moodlejs_StopPlayingYourAudio'] = "Stop playing your audio";
$string['moodlejs_StartPlayingExampleAudio'] = "Start playing example audio";
$string['moodlejs_StopPlayingExampleAudio'] = "Stop playing example audio";
$string['moodlejs_HideScoreDetail'] = "Hide score detail: ";
$string['moodlejs_ShowScoreDetail'] = "Show score detail: ";
$string['moodlejs_Select'] = "Select";
$string['moodlejs_Syllable'] = "Syllable";
$string['moodlejs_Phone'] = "Phone";
$string['moodlejs_Score'] = "Score";
$string['moodlejs_StartRecordingAudio'] = "Start recording audio";
$string['moodlejs_StopRecordingAudio'] = "Stop recording audio";
$string['moodlejs_HideInfo'] = "Hide Information";
$string['moodlejs_ShowInfo'] = "Show Information";
$string['moodlejs_UnableStartRecording'] = "Unable to start recording: ";
$string['moodlejs_UnableStopRecording'] = "Unable to stop recording: ";
$string['moodlejs_UnableUnmarshalBlob'] = "Unable to unmarshal blob from recording: ";
$string['moodlejs_UnableStartPlayback'] = "Unable to start playback: ";
$string['moodlejs_UnableStopPlayback'] = "Unable to stop playback: ";
$string['moodlejs_FlashError_1'] = "Your version of flash is outdated. Please follow https://helpx.adobe.com/flash-player.html to update";
$string['moodlejs_FlashError_2'] = "Flash is not supported on mobile or tablet.";
$string['moodlejs_FlashError_3'] = "Your browser comes with Flash support, but it is disabled. Please enable it.";
$string['moodlejs_FlashError_4'] = "Flash is not available. Please follow https://helpx.adobe.com/flash-player.html to install";
$string['moodlejs_TapRedWords'] = "Tap/Click the red colored words to see how to improve.";
$string['moodlejs_AnswerSaved'] = "Answer saved";
$string['moodlejs_Say'] = "Say: ";
$string['moodlejs_Review'] = "Review";
$string['moodlejs_FetchingAudio'] = "Fetching audio: ";
$string['moodlejs_AudioSaved'] = "Audio saved";
$string['moodlejs_UnableSaveAudio'] = "Unable to save the audio.";
$string['moodlejs_TryAgain'] = "Please try again.";
$string['moodlejs_RecordYourAudio'] = "Record your audio";
$string['moodlejs_UseSpeechAceAudio'] = "Use SpeechAce audio of answer text";
$string['moodlejs_PlaceHolder'] = "Type text to score here...";
$string['moodlejs_MessageScoreGreaterThan'] = "Message when the score is greater than or equal to ";
$string['moodlejs_MessageButLessThan'] = "but less than ";
$string['moodlejs_MessageScoreLessThan'] = "Message when the score is less than ";
$string['moodlejs_MessageReset'] = "Reset messages";