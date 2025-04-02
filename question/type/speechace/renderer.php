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
 * SpeechAce question renderer class.
 *
 * @package    qtype
 * @subpackage speechace
 * @copyright  2017 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
require_once("constant.php");
require_once("speechacelib.php");
global $PAGE;

$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/question/type/speechace/module.js'));

/**
 * Generates the output for speechace questions.
 */
class qtype_speechace_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
        question_display_options $options) {

        $question = $qa->get_question();
        $responseoutput = $question->get_format_renderer($this->page);

        // Answer field.
        $answer = $responseoutput->response_area_input(
                    'answer',
                    $qa,
                    $options->context,
                    !empty($options->readonly));
        
        $result = '';
        $result .= $question->format_questiontext($qa);

        $result .= html_writer::start_tag('div');
        $result .= html_writer::tag('div', $answer);
        $result .= html_writer::end_tag('div');

        return $result;
    }
    
    public function manual_comment(question_attempt $qa, question_display_options $options) {

        $result = "";

        return $result;
    }
}

/**
 * An speechace format renderer for speechace for audio
 *
 * @copyright  2017 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_speechace_format_audio_renderer extends plugin_renderer_base {

    protected function class_name() {
        return 'qtype_speechace_audio';
    }

    protected function prepare_response_for_editing($name,
        question_attempt_step $step, $context) {
        return $step->prepare_response_files_draft_itemid_with_text(
            $name, $context->id, $step->get_qt_var($name));

    }

    public function response_area_input($name, $qa, $context, $read_only) {
        $step = $qa->get_last_step_with_qt_var($name);
        //check of we already have a submitted answer. If so we need to set the filename
        //in our input field.
        $submittedfilename = strip_tags($step->get_qt_var($name));
        $submittedfile = qtype_speechace_get_submitted_file($name, $qa, $submittedfilename, $context->id);
        if (!$submittedfile) {
            $submittedfilename = '';
        }

        $ret = "";
        $draftitemid = null;
        $inputid = null;

        if (!$read_only) {
            //prepare a draft file id for use
            list($draftitemid, $response) = $this->prepare_response_for_editing( $name, $step, $context);

            //prepare the tags for our hidden( or shown ) input
            $inputname = $qa->get_qt_field_name($name);
            $inputid =  $inputname . '_id';

            //our answerfield
            $ret .= html_writer::empty_tag('input', array('type' => 'hidden','id'=>$inputid,
                'name' => $inputname, 'value' => $submittedfilename));

            //our answerfield draft id key
            $ret .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $inputname . ':itemid', 'value'=> $draftitemid));

            //our answerformat
            $ret .= html_writer::empty_tag('input', array('type' => 'hidden','name' => $inputname . 'format', 'value' => 1));
        }

        //the context id is the user context for a student submission
        return $ret . $this->render_moodle_view_controller(
                        $inputid,
                        $context->id,
                        'user',
                        'draft',
                        $draftitemid,
                        $qa,
                        $submittedfile,
                        $read_only);
    }

    protected function render_moodle_view_controller(
        $updatecontrol,
        $contextid,
        $component,
        $filearea,
        $itemid,
        $qa,
        $submittedfile,
        $read_only)
    {
        global $PAGE, $CFG;

        $PAGE->requires->js(new moodle_url($CFG->wwwroot . QTYPE_SPEECHACE_JS_PATH));

        $baseurl = $CFG->wwwroot . '/question/type/speechace/jsapi.php';
        $domid = $qa->get_qt_field_name('speechace-recording-view-controller');
        $opts = array();
        $opts['updatecontrol'] = $updatecontrol;
        $opts['contextid'] = $contextid;
        $opts['component'] = $component;
        $opts['filearea'] = $filearea;
        $opts['itemid'] = $itemid;
        $opts['baseurl'] = $baseurl;
        $opts['requestid'] = 'audiorecorder_' . time() .  rand(10000,999999);
        $opts['domid'] = $domid;
        $opts['slot'] = $qa->get_slot();
        $opts['usageid'] = $qa->get_usage_id();
        $opts['sesskey'] = sesskey();

        $opts['workerPath'] = $CFG->wwwroot . '/question/type/speechace/js/recorderWorker.js';
        $opts['swfPath'] = $CFG->wwwroot . QTYPE_SPEECHACE_SWF_PATH;
        $opts['preferSwf'] = QTYPE_SPEECHACE_PREFER_SWF;
        $opts['fillColor'] = '#fff';
        $opts['color'] = '#1ccff6';
        $opts['volumeFillColor'] = '#bbb';
        $opts['readOnly'] = $read_only;
        $opts['phonemeSymbolType'] = 'ipa';

        if ($submittedfile) {
            $opts['answerId']=strip_tags($submittedfile->get_filename());
        }

        $opts['id'] =  $qa->get_qt_field_name('id');

        $question = $qa->get_question();
        $question_args = json_decode($question->scoringinfo);
        if ($question_args->score_type === "scoreword") {
            $opts['word'] = $question->get_answer_to_show($question_args->text, $read_only);
        } else {
            $opts['text'] = $question->get_answer_to_show($question_args->text, $read_only);
        }
        if ($question->showanswer == QTYPE_SPEECHACE_SHOW_ANSWER_ALWAYS) {
            $opts['expertAudioPos'] = 'top';
        } else {
            $opts['expertAudioPos'] = 'middle';
        }
        $scoringinfo_obj = new qtype_speechace_scoringinfo($question_args);
        $opts['experthash'] = $scoringinfo_obj->getExpertHash();
        $opts['dialect']= $question->dialect;
        $opts['showExpertAudio']= $question->get_expertaudio_to_show($read_only);
        $questionimg = qtype_speechace_get_question_img_src($question_args);
        if ($questionimg) {
            $opts['image'] = $questionimg;
        }

        $showNumericScore =  qtype_speechace_get_show_numeric_score();
        if ($showNumericScore =="1")
             $opts['showNumericScore'] = true;
        else
            $opts['showNumericScore'] = false;

        $scoreMessagesData = qtype_speechace_deserialize_score_message_setting(get_config('qtype_speechace','scoremessages'));
        $opts['scoremessagesTextOne'] = $scoreMessagesData[QTYPE_SPEECHACE_SCOREMESSAGES_TEXTONE];
        $opts['scoremessagesTextTwo'] = $scoreMessagesData[QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTWO];
        $opts['scoremessagesTextThree'] = $scoreMessagesData[QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTHREE];
        
        // private strings
		$PAGE->requires->string_for_js('moodlejs_NoAudioError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_NoAudioError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_NoRecordingError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_NoRecordingError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_AssertionError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_NoAnswerError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_NoAnswerError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_WrongAnswerError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_WrongAnswerError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_InvalidResponseData', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_InvalidResponseData_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_MicrophoneDeniedError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_MicrophoneDeniedError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_MicrophoneNotConnectedError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_MicrophoneNotConnectedError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_TTSError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_TTSError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_FlashPendingError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_FlashPendingError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_ScoringError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_ScoringError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_ScoringFormatError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_ScoringFormatError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnknownPageError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnknownPageError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnknownMathXLPageError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnknownMathXLPageError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnauthorizedPageError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnauthorizedPageError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_SignInError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_MissingAnswerError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_MissingAnswerError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnknownQuestionTypeError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnknownQuestionTypeError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_QuizNotFoundError_1', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_QuizNotFoundError_2', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_QuizNotFoundError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_QuestionNotFoundError_1', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_QuestionNotFoundError_2', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_QuestionNotFoundError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnknownWordsError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnknownWordsError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_TextTooLongError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_TextTooLongError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_TextSpeechValidationError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_TextSpeechValidationError_custom_1', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_TextSpeechValidationError_custom_2', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_CourseNotFoundError_1', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_CourseNotFoundError_2', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_CourseNotFoundError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_AjaxError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_CourseAttemptNotFoundError_1', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_CourseAttemptNotFoundError_2', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_CourseAttemptNotFoundError_custom', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_NotImplementedError', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_extractErrorMessages_short', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_extractErrorMessages_detailed', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_SoundLike', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_PlayStopExampleAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_StopAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_StartPlayingYourAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_StopPlayingYourAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_StartPlayingExampleAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_StopPlayingExampleAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_HideScoreDetail', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_ShowScoreDetail', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_Select', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_Syllable', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_Phone', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_Score', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_StartRecordingAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_StopRecordingAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_HideInfo', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_ShowInfo', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnableStartRecording', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnableStopRecording', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnableUnmarshalBlob', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnableStartPlayback', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnableStopPlayback', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_FlashError_1', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_FlashError_2', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_FlashError_3', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_FlashError_4', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_TapRedWords', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_AnswerSaved', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_Say', 'qtype_speechace');
		/*$PAGE->requires->string_for_js('moodlejs_Review', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_FetchingAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_AudioSaved', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnableSaveAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_TryAgain', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_RecordYourAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UseSpeechAceAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_PlaceHolder', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_MessageScoreGreaterThan', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_MessageButLessThan', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_MessageScoreLessThan', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_MessageReset', 'qtype_speechace');*/


        
        
        
        $PAGE->requires->js_init_call("M.qtype_speechace.attachMoodleViewController", array($opts), false);

        $output = "";
        $output .= "<div id=\"" . $domid . "\"></div>";

        return $output;
    }
}
