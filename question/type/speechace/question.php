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
 * SpeechAce question definition class.
 *
 * @package    qtype
 * @subpackage speechace
 * @copyright  2017 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once("speechacelib.php");

/**
 * Represents a speechace question.
 *
 * @copyright  2014 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_speechace_question extends question_graded_automatically {
    public $scoringinfo;
    public $showanswer;
    public $showresult;
    public $dialect;
    public $showexpertaudio;
    /**
     * @param moodle_page the page we are outputting to.
     * @return qtype_speechace_format_renderer_base the response-format-specific renderer.
     */
    public function get_format_renderer(moodle_page $page) {
        return $page->get_renderer('qtype_speechace', 'format_audio');
    }
	
	/**
	*	This tells Moodle what fields to expect, in particular it tells it 
	*   to look for uploaded file URLs in the answer field
	*/
    public function get_expected_data() {
		global $CFG;
			//The API for this changed on this date 20120214 (possibly the previous release)
			//checked it with version numbers. then used defined(const)
			if(!defined('question_attempt::PARAM_CLEANHTML_FILES')) {
				$expecteddata = array('answer' => question_attempt::PARAM_RAW_FILES);
			}else{
				$expecteddata = array('answer' => question_attempt::PARAM_CLEANHTML_FILES);
			}
			$expecteddata['answerformat'] = PARAM_FORMAT;
			
			//base64 data and data for whiteboard
			$expecteddata['answervectordata'] = PARAM_TEXT;
			$expecteddata['answerbase64data'] = PARAM_TEXT;

        return $expecteddata;
    }

    public function summarise_response(array $response) {
	
        if (isset($response['answer'])) {
            $formatoptions = new stdClass();
            $formatoptions->para = false;
            return html_to_text(format_text(
                    $response['answer'], FORMAT_HTML, $formatoptions), 0, false);
        } else {
            return null;
        }
    }

    public function get_correct_response() {
        return null;
    }

    public function is_complete_response(array $response) {
        return !empty($response['answer']);
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
     	//print_object($qa);
        if ($component == 'question' && $filearea == 'response_answer') {
		   //since we will put files in respnse_answer, this is likely to be always true.
		   return true;

        } else if ($component == 'qtype_speechace' && $filearea == 'scoringinfo') {
            return true;

        } else {
            return parent::check_file_access($qa, $options, $component,
                    $filearea, $args, $forcedownload);
					
        }
    }

    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('pleaserecordaudio', 'qtype_speechace');
    }

    public function grade_response(array $response) {
        $fraction = 0;

        // Get the audio file path
        $files = $response['answer']->get_files();
        $file = "";
        if (count($files) == 1) {
            $file = array_values($files)[0];
        } else if (count($files) > 1) {
            $value = (string)$response['answer'];
            if (preg_match('/\s*<!-- File hash: [0-9a-zA-Z]{32} -->\s*$/', $value)) {
                $value = preg_replace('/\s*<!-- File hash: [0-9a-zA-Z]{32} -->\s*$/', '', $value);
                foreach ($files as $hash => $candidate_file) {
                    if ($candidate_file->get_filename() === $value) {
                        $file = $candidate_file;
                        break;
                    }
                }
            }
            if (empty($file)) {
                $file = array_values($files)[count($files) - 1];
            }
        }

        if ($file) {
            list($succeeded, $speechace_results, $score) = qtype_speechace_grade_user_audio($file, $this, true);
            if ($succeeded) {
                $fraction = $score / 100.0;
            }
        }

        //todo should that be changed too ? according to user of select1 ??
        $threshold = QTYPE_SPEECHACE_CORRECT_SCORE_THRESHOLD / 100.0;

        return array($fraction, $this->graded_state_for_fraction2($fraction, $threshold));
    }

    public function get_answer_to_show($answer_text, $inreview) {
        if (!$inreview && $this->showanswer === QTYPE_SPEECHACE_SHOW_ANSWER_RESULT) {
            return '';
        } else {
            return $answer_text;
        }
    }

    public function determine_show_score($inreview) {
        if ($this->showresult === QTYPE_SPEECHACE_SHOW_RESULT_REVIEW) {
            return $inreview;
        } else {
            return true;
        }
    }

    public function get_expertaudio_to_show($inreview)
    {
        if (!$inreview && $this->showexpertaudio === QTYPE_SPEECHACE_SHOW_EXPERT_AUDIO_RESULT) {
            return QTYPE_SPEECHACE_SHOW_EXPERT_AUDIO_RESULT;
        } else {
            return QTYPE_SPEECHACE_SHOW_EXPERT_AUDIO_ALWAYS;
        }
    }

    //check!

    /**
     * Return the appropriate graded state based on a fraction. That is 0 or less
     * is $graded_incorrect, 1 is $graded_correct, otherwise it is $graded_partcorrect.
     * Appropriate allowance is made for rounding float values.
     *
     * @param number $fraction the grade, on the fraction scale.
     * @param number $right_score the score that constitutes the rigth grade.
     * @return question_state one of the state constants.
     */
    public static function graded_state_for_fraction2($fraction, $right_score) {
        if ($fraction < 0.000001) {
            return question_state::$gradedwrong;
        } else if ($fraction >= (float)$right_score) {
            return question_state::$gradedright;
        } else {
            return question_state::$gradedpartial;
        }
    }
}
