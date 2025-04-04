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
 * Helper classes and functions for speechace question type plugin
 *
 * @package    qtype
 * @subpackage speechace
 * @copyright  2017 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//define("NEW_SERVER_URL_PREFIX", "https://api.speechace.co");
define("NEW_SERVER_URL_PREFIX", "https://api4.speechace.com");

define('QTYPE_SPEECHACE_CORRECT_SCORE_THRESHOLD', 80);


define('QTYPE_SPEECHACE_FORM_TEXT', 'text');
define('QTYPE_SPEECHACE_FORM_SOURCETYPE', 'sourcetype');
// NOTE: we need to use itemid because file_get_submitted_draft_itemid assumes that
define('QTYPE_SPEECHACE_FORM_MOODLEITEMID', 'itemid');
define('QTYPE_SPEECHACE_FORM_SPEECHACEKEY', 'speechacekey');
define('QTYPE_SPEECHACE_FROM_MOODLEKEY', 'moodlekey');

define('QTYPE_SPEECHACE_MOODLE_KEY_FILE_PREFIX', 'qtype_speechace_q_');
define('QTYPE_SPEECHACE_SPEECHACE_KEY_FILE_PREFIX', 'qtype_speechace_key_');
define('QTYPE_SPEECHACE_QUESTION_ATTEMPT_FILE_PREFIX', 'qtype_speechace_qa_');

define('QTYPE_SPEECHACE_PREFER_SWF', false);
define('QTYPE_SPEECHACE_SWF_PATH', '/question/type/speechace/js/speechace.swf?v=23');

define('QTYPE_SPEECHACE_JS_PATH', '/question/type/speechace/js/moodle.js?v=46');

define('QTYPE_SPEECHACE_SCORE_FILE_EXTENSION', '.json');

define('QTYPE_SPEECHACE_SHOW_ANSWER_ALWAYS', 'always');
define('QTYPE_SPEECHACE_SHOW_ANSWER_RESULT', 'result');

define('QTYPE_SPEECHACE_SHOW_RESULT_IMMEDIATELY', 'immediately');
define('QTYPE_SPEECHACE_SHOW_RESULT_REVIEW', 'review');



/**require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');*/
require_once('constant.php');

class qtype_speechace_multipart_form {
    private $boundary;
    private $internal_data;
    private $completed;

    public function __construct() {
        $this->completed = false;
        $this->internal_data = "";
        $this->boundary = "----SPEECHACE" . uniqid();
    }

    private function append_boundary() {
        $this->internal_data .= "--{$this->boundary}\r\n";
    }

    private function check_append_state() {
        assert(!$this->completed);
        $this->append_boundary();
    }

    public function append_name_value($name, $value) {
        $this->check_append_state();
        $this->internal_data .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
        $this->internal_data .= "{$value}\r\n";
    }

    public function append_file_data($name, $value, $type, $file_name) {
        $this->check_append_state();
        $this->internal_data .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$file_name}\"\r\n";
        $this->internal_data .= "Content-Type: {$type}\r\n\r\n";
        $this->internal_data .= "{$value}\r\n";
    }

    public function get_data() {
        if (!$this->completed) {
            $this->internal_data .= "--{$this->boundary}--\r\n";
            $this->completed = true;
        }
        return $this->internal_data;
    }

    public function get_boundary() {
        return $this->boundary;
    }
}

class qtype_speechace_scoringinfo {

    private $inner_scoringinfo;

    public function __construct($inner_scoringinfo) {
        $this->inner_scoringinfo = $inner_scoringinfo;
    }

    public function getText() {
        return $this->inner_scoringinfo->text;
    }

    public function getSpeechaceKey() {
        return $this->inner_scoringinfo->speechace_key;
    }

    public function getMoodleKey() {
        if (object_property_exists($this->inner_scoringinfo, 'moodle_key')) {
            return $this->inner_scoringinfo->moodle_key;
        } else {
            return null;
        }
    }

    public function getExpertHash() {
        // we introduce this hash to invalidate browser cache
        $hash_input = $this->getSourceType();
        if ($this->getSourceType() === 'moodle_key') {
            $value = $this->getMoodleKey();
            if ($value) {
                $hash_input .= ': ' . $value;
            }
        } else {
            $value = $this->getSpeechaceKey();
            if ($value) {
                $hash_input .= ': ' . $value;
            }
        }
        return sha1($hash_input);
    }

    public function getSourceType() {
        if (object_property_exists($this->inner_scoringinfo, 'source_type')) {
            return $this->inner_scoringinfo->source_type;
        } else {
            return 'speechace_key';
        }
    }

    public function getMoodleItemId() {
        if (object_property_exists($this->inner_scoringinfo, 'moodle_item_id')) {
            return $this->inner_scoringinfo->moodle_item_id;
        } else {
            return null;
        }
    }

    public function applyFormData($formdata) {
        // TODO: validate $formdata
        if (is_array($formdata)) {
            if (array_key_exists(QTYPE_SPEECHACE_FORM_TEXT, $formdata)) {
                $text = qtype_speechace_normalize_scoringinfo_text($formdata[QTYPE_SPEECHACE_FORM_TEXT]);
                if ($this->inner_scoringinfo->text !== $text) {
                    $this->inner_scoringinfo->text = $text;
                    if ($this->inner_scoringinfo->score_type !== 'scorefreetext') {
                        if ($this->inner_scoringinfo->score_type === 'scoretext') {
                            unset($this->inner_scoringinfo->letter_list);
                            unset($this->inner_scoringinfo->phone_list);
                            unset($this->inner_scoringinfo->syllable_list);
                        } elseif ($this->inner_scoringinfo->score_type === 'scoreword') {
                            unset($this->inner_scoringinfo->word_number);
                            unset($this->inner_scoringinfo->pos);
                        }
                    } else {
                        if (object_property_exists($this->inner_scoringinfo, 'tokenized_text')) {
                            unset($this->inner_scoringinfo->tokenized_text);
                        }
                    }
                    $this->inner_scoringinfo->score_type = 'scorefreetext';
                }
            }

            if (array_key_exists(QTYPE_SPEECHACE_FORM_SOURCETYPE, $formdata)) {
                $this->inner_scoringinfo->source_type = $formdata[QTYPE_SPEECHACE_FORM_SOURCETYPE];
            }

            if (array_key_exists(QTYPE_SPEECHACE_FORM_MOODLEITEMID, $formdata)) {
                $this->inner_scoringinfo->moodle_item_id = $formdata[QTYPE_SPEECHACE_FORM_MOODLEITEMID];
            }

            if (array_key_exists(QTYPE_SPEECHACE_FORM_SPEECHACEKEY, $formdata)) {
                if ($this->inner_scoringinfo->speechace_key !== $formdata[QTYPE_SPEECHACE_FORM_SPEECHACEKEY]) {
                    $this->inner_scoringinfo->speechace_key = $formdata[QTYPE_SPEECHACE_FORM_SPEECHACEKEY];
                    if (object_property_exists($this->inner_scoringinfo, 'has_image')) {
                        unset($this->inner_scoringinfo->has_image);
                    }
                }
            }

            if (array_key_exists(QTYPE_SPEECHACE_FROM_MOODLEKEY, $formdata)) {
                $this->inner_scoringinfo->moodle_key = $formdata[QTYPE_SPEECHACE_FROM_MOODLEKEY];
            }
        }
    }

    public function serialize() {
        return json_encode($this->inner_scoringinfo);
    }

    public function getInner() {
        return $this->inner_scoringinfo;
    }

    public static function deserialize($value) {
        return new qtype_speechace_scoringinfo(json_decode($value));
    }

    public static function createFromFormData($formdata) {
        // TODO: validate $formdata
        $inner_scoringinfo = new stdClass();
        if (is_array($formdata)) {
            if (array_key_exists(QTYPE_SPEECHACE_FORM_TEXT, $formdata)) {
                $inner_scoringinfo->text = qtype_speechace_normalize_scoringinfo_text(
                    $formdata[QTYPE_SPEECHACE_FORM_TEXT]);
                $inner_scoringinfo->score_type = 'scorefreetext';
            }

            if (array_key_exists(QTYPE_SPEECHACE_FORM_SOURCETYPE, $formdata)) {
                $inner_scoringinfo->source_type = $formdata[QTYPE_SPEECHACE_FORM_SOURCETYPE];
            }

            if (array_key_exists(QTYPE_SPEECHACE_FORM_MOODLEITEMID, $formdata)) {
                $inner_scoringinfo->moodle_item_id = $formdata[QTYPE_SPEECHACE_FORM_MOODLEITEMID];
            }

            if (array_key_exists(QTYPE_SPEECHACE_FORM_SPEECHACEKEY, $formdata)) {
                $inner_scoringinfo->speechace_key = $formdata[QTYPE_SPEECHACE_FORM_SPEECHACEKEY];
            }

            if (array_key_exists(QTYPE_SPEECHACE_FROM_MOODLEKEY, $formdata)) {
                $inner_scoringinfo->moodle_key = $formdata[QTYPE_SPEECHACE_FROM_MOODLEKEY];
            }
        }
        return new qtype_speechace_scoringinfo($inner_scoringinfo);
    }
}

class qtype_speechace_exception extends Exception {

    private $_short_message;
    private $_http_status_message;

    public function __construct($short_message, $http_status_message='403 Forbidden', $code=0, $previous=null) {
        parent::__construct(get_string($short_message, 'qtype_speechace'), $code, $previous);
        $this->_short_message = $short_message;
        $this->_http_status_message = $http_status_message;
    }

    public function getShortMessage() {
        return $this->_short_message;
    }

    public function createStdClassErrorResponse() {
        $response = new stdClass();
        $response->status = 'error';
        $response->short_message = $this->_short_message;
        $response->detail_message = $this->getMessage();
        $response->http_status_message = $this->_http_status_message;
        return $response;
    }

    public function getHttpErrorStatusMessage() {
        return $this->_http_status_message;
    }
}

function qtype_speechace_get_server_url_prefix() {
    global $CFG;
    if (isset($CFG->speechace_server_url_prefix)) {
        return $CFG->speechace_server_url_prefix;
    } else {
        return NEW_SERVER_URL_PREFIX;
    }
}

function qtype_speechace_get_product_key() {
    $config_value = get_config('qtype_speechace', 'productkey');
    $trimmeddata = trim($config_value);
    if (empty($trimmeddata)) {
        throw new qtype_speechace_exception('error_productkey_missing');
    }

    // Make sure that the data is url encoded
    $decoded_value = rawurldecode($trimmeddata);
    $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
    $encoded_value = strtr(rawurlencode($decoded_value), $revert);
    if ($encoded_value !== $trimmeddata) {
        throw new qtype_speechace_exception('error_productkey_missing');
    }

    return $encoded_value;
}

function qtype_speechace_get_show_numeric_score(){
    return(get_config('qtype_speechace','numericscore'));
}

function qtype_speechace_get_user_id() {
    global $USER, $CFG;

    $host = parse_url($CFG->wwwroot, PHP_URL_HOST);
    if (!$host) {
        $host = 'UNKNOWN_HOST';
    }
    $user_id = null;
    if ($USER && object_property_exists($USER, 'id')) {
        $user_id = $USER->id;
    }
    if (!$user_id) {
        $user_id = 0;
    }
    return $host . '/' . $user_id;
}



function qtype_speechace_format_url($op,$dialect=null) {
    $operation = 'scoring';
    $type = 'word';
    $output_format = 'json';
    if ($dialect === QTYPE_SPEECHACE_DIALECT_UK_ENGLISH) {
        $version = 'v9';
    } else {
        $version = 'v9';
    }
    if ($op === 'scorefreetext') {
        $type = 'text';
    } else if ($op === 'scoretext') {
        $type = 'custom';
    } else if ($op === 'serveaudio') {
        $operation = 'serving';
        $type = 'audio';
        $output_format = 'wav';
        $version = 'v0.1';
    } else if ($op === 'serveimage') {
        $operation = 'serving';
        $type = 'image';
        $output_format = 'any';
        $version = 'v0.1';
    } else if ($op === 'ttsing') {
        $operation = 'ttsing';
        $type = 'text';
        $output_format = 'wav';
        $version = 'v0.1';
    } else if ($op === 'validating') {
        $operation = 'validating';
        $type = 'text';
        $version = 'v0.1';
    }


    $prefix_url = qtype_speechace_get_server_url_prefix();
    $key = qtype_speechace_get_product_key();
    $user_id = urlencode(qtype_speechace_get_user_id());
    $dialect_url = $dialect;

    return "{$prefix_url}/api/{$operation}/{$type}/{$version}/{$output_format}?key={$key}&dialect={$dialect_url}&user_id={$user_id}";

}

/**
 * @param $args
 * @param null $audio_file
 * @param null $dialect
 * @return array
 */
function qtype_speechace_grade_audio($args, $audio_file=null, $dialect=null) {
    $op = $args->score_type;
    $succeeded = true;

    $word = null;
    $word_number = null;
    $pos = null;
    $expert = null;
    $expertid = null;
    $selfscore = false;
    $text = null;
    $letter_list = null;
    $phone_list = null;
    $syllable_list = null;
    $tokenized = false;
    $speechace_key = null;

    if ($audio_file === null) {
        $selfscore = true;
    }
   
    if ($op === "scoretext") {
        $text = $args->text;
        $letter_list = $args->letter_list;
        $phone_list = $args->phone_list;
        $syllable_list = $args->syllable_list;
        $expert_args = explode(',', $args->speechace_key);
        $expert = $expert_args[0];
        $expertid = $expert_args[1];
    } elseif ($op === "scorefreetext") {
        if (object_property_exists($args, 'tokenized_text')) {
            $text = $args->tokenized_text;
            $tokenized = true;
        } else {
            $text = $args->text;
        }
        if ($selfscore) {
            // speechace_key is no longer necessarily available as we may use moodle_key
            // we don't expect caller to call this function for $selfscore if speechace_key
            // is not available. If this happens, it is a bug.
            if (qtype_speechace_is_scoring_server_file_key($args->speechace_key)) {
                $speechace_key = $args->speechace_key;
            } else {
                $expert_args = explode(',', $args->speechace_key);
                $expert = $expert_args[0];
                $expertid = $expert_args[1];
            }
        }
    } elseif ($op === "scoreword") {
        $word = $args->text;
        $word_number = $args->word_number;
        $pos = $args->pos;
        $expert_args = explode(',', $args->speechace_key);
        $expert = $expert_args[0];
        if (count($expert_args) >= 2) {
            $expertid = $expert_args[1];
        }
    } else {
        $succeeded = false;
    }

    $file_content = null;
    if ($succeeded && !$selfscore) {
        $file_content = $audio_file->get_content();
        if ($file_content === false) {
            $succeeded = false;
        }
    }

    try {
        $form_body = null;
        $response = null;
        if ($succeeded) {
            $url = qtype_speechace_format_url($op,$dialect);
            $form_body = new qtype_speechace_multipart_form();
            if ($text !== null) {
                if ($op === "scoretext") {
                    $form_body->append_name_value("text", $text);
                    $form_body->append_name_value("letter_list", $letter_list);
                    $form_body->append_name_value("phone_list", $phone_list);
                    $form_body->append_name_value("syllable_list", $syllable_list);
                } else {
                    $form_body->append_name_value("text", $text);
                    if ($tokenized) {
                        $form_body->append_name_value("tokenized", "1");
                    }
                }
            } else {
                $form_body->append_name_value("alg", "nnet");
                $form_body->append_name_value("word", $word);
                $form_body->append_name_value("wordnumber", $word_number);
            }

            if (!$selfscore) {
                $form_body->append_file_data("user_audio_file", $file_content, "audio/x-wav", "{$word}.wav");
            } else {
                if ($expert) {
                    $form_body->append_name_value("expert", $expert);
                    if ($expertid) {
                        $form_body->append_name_value("expertid", $expertid);
                    }
                } elseif ($speechace_key) {
                    $form_body->append_name_value("speechace_key", $speechace_key);
                }
                if ($pos) {
                    $form_body->append_name_value("pos", $pos);
                }
                $form_body->append_name_value("selfscore", 1);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: multipart/form-data; boundary={$form_body->get_boundary()}"));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $form_body->get_data());
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            if ($response === false || strlen($response) == 0) {
                $succeeded = false;
                if (curl_errno($ch)) {
                    $error_message = curl_error($ch);
                    throw new qtype_speechace_exception('error_http_api_call', $error_message);
                }
            }
        }

        $result = null;
        if ($succeeded) {
            $result = json_decode($response);
            if (!$result) {
                $succeeded = false;
            }
        }

    } catch (qtype_speechace_exception $ex) {

        $succeeded = false;
        $result = $ex->createStdClassErrorResponse();
    }

    return array($succeeded, $result);
}

function qtype_speechace_create_speech_from_text($text, $dialect) {

    try {
        $url = qtype_speechace_format_url('ttsing',$dialect);
        $text = trim($text);
        $url .= '&text=' . urlencode($text);
        list($headers, $result) = qtype_speechace_get_url_headers_and_contents($url);
        $responseHeaders = qtype_speechace_parse_http_response_headers($headers);
        $succeeded = false;
        $result_json = null;
        if (array_key_exists('response_code', $responseHeaders)) {
            $response_code = $responseHeaders['response_code'];
            if (array_key_exists('content-type', $responseHeaders)) {
                $content_type = $responseHeaders['content-type'];
                if ($response_code === 200 && $content_type === 'audio/x-wav') {
                    if (array_key_exists('content-disposition', $responseHeaders)) {
                        $content_disposition = $responseHeaders['content-disposition'];
                        $speechace_key = qtype_speechace_parse_content_disposition($content_disposition);
                        if ($speechace_key) {
                            $result_json = new stdClass();
                            $result_json->status = 'success';
                            $result_json->speechace_key = $speechace_key;
                            $succeeded = true;
                        }
                    }
                } else if ($content_type === 'application/json') {
                    $result_json = json_decode($result);
                    if ($result_json) {
                        if (object_property_exists($result_json, 'status')) {
                            if ($result_json->status !== 'error') {
                                $result_json = null;
                            }
                        }
                    }
                }
            }
        }
        if (!$succeeded && !$result_json) {
            $result_json = new stdClass();
            $result_json->status = 'error';
            $result_json->short_message = 'error_invalid_response_headers';
            $result_json->detail_message = json_encode($responseHeaders);
        }
    } catch (qtype_speechace_exception $ex) {
        $succeeded = false;
        $result = null;
        $result_json = $ex->createStdClassErrorResponse();
    }

    return array($succeeded, $result, $result_json);
}

function qtype_speechace_normalize_scoringinfo_text($text) {
    // NOTE: we replace newline with space because scoring free text
    // uses newline for delimiter.
    return strtr(trim($text), "\r\n", '  ');
}

function qtype_speechace_validate_scoringinfo_text($text,$dialect=null) {
    try {
        $url = qtype_speechace_format_url('validating',$dialect);
        $text = qtype_speechace_normalize_scoringinfo_text($text);
        $url .= '&text=' . urlencode($text);
        $result = qtype_speechace_get_url_contents($url);
        $result_json = null;
        if ($result) {
            $result_json = json_decode($result);
        }
        if (!$result_json) {
            $result_json = new stdClass();
            $result_json->status = 'error';
            $result_json->short_message = 'error_unknown';
        }
    } catch (qtype_speechace_exception $ex) {
        $result_json = $ex->createStdClassErrorResponse();
    }
    return $result_json;
}

function qtype_speechace_create_results_file_record($audio_file) {
    // make our filerecord
    $record = new stdClass();
    $record->filearea = $audio_file->get_filearea();
    $record->component = $audio_file->get_component();
    $record->filepath = $audio_file->get_filepath();
    $record->itemid = $audio_file->get_itemid();
    $record->license = $audio_file->get_license();
    $record->author = 'SpeechAce';
    $record->contextid = $audio_file->get_contextid();
    $record->userid = $audio_file->get_userid();
    $record->source = $audio_file->get_source();
    $record->filename = $audio_file->get_filename() . QTYPE_SPEECHACE_SCORE_FILE_EXTENSION;
    return $record;    
}

function qtype_speechace_save_score_results($speechace_results, $audio_file) {
    if (is_object($speechace_results) &&
        object_property_exists($speechace_results, 'status') &&
        ($speechace_results->status === 'success')) {

        $score_file = null;
        $fs = get_file_storage();
        $record = qtype_speechace_create_results_file_record($audio_file);
        if (!$fs->file_exists($record->contextid, $record->component, $record->filearea, $record->itemid, $record->filepath, $record->filename)) {

            $speechace_results_string = json_encode($speechace_results);
            $score_file = $fs->create_file_from_string($record, $speechace_results_string);
        }
        return $score_file;
    } else {
        return null;
    }
}

function qtype_speechace_load_score_results($audio_file) {
    $fs = get_file_storage();
    $record = qtype_speechace_create_results_file_record($audio_file);
    $score_file = $fs->get_file($record->contextid, $record->component, $record->filearea, $record->itemid, $record->filepath, $record->filename);
    if ($score_file === false) { 
        return '';
    }
    $content = $score_file->get_content();
    $speechace_results = json_decode($content);
    return $speechace_results;
}

function qtype_speechace_form_expert_audio_url($args) {

	$op = $args->score_type;
	$word = "";
	$wordnumber = "";
	$sense = "";
	$maleorfemale = "";
	$expertid = "";
    $expert = "";
    $speechace_key = "";

	if ($op === "scoretext") {
        $word = $args->text;
        $wordnumber = "1";
        $sense = "text";
        $expert_args = explode(',', $args->speechace_key);
        $maleorfemale = trim($expert_args[0]);
        $expertid = trim($expert_args[1]);
    } elseif ($op === "scorefreetext") {
	    if (qtype_speechace_is_scoring_server_file_key($args->speechace_key)) {
	        $speechace_key = $args->speechace_key;
        } else {
            $expert_args = explode(',', $args->speechace_key);
            $expert = trim($expert_args[0]);
            $expertid = trim($expert_args[1]);
        }
	} elseif ($op === "scoreword") {
	    $word = $args->text;
	    $wordnumber = $args->word_number;
        $expert_args = explode(',', $args->speechace_key);
        $maleorfemale = trim($expert_args[0]);
        if (count($expert_args) >= 2) {
            $expertid = trim($expert_args[1]);
        }
	}
	
	$url = qtype_speechace_format_url('serveaudio');
    if ($word != "") {
        $url .= "&word=".urlencode($word);
    }
    if ($wordnumber != "") {
        $url .= "&wordNumber=".$wordnumber;
    }
    if ($sense != "") {
        $url .= "&sense=".$sense;
    }
    if ($maleorfemale != "") {
        $url .= "&speaker=".$maleorfemale;
    }
    if ($expert != "") {
        $url .= "&expert=".$expert;
    }
    if ($expertid != "") {
    	$url .= "&expertid=".$expertid;
    }
    if ($speechace_key != "") {
        $url .= "&speechace_key=".urlencode($speechace_key);
    }
    
    return $url;
}

function qtype_speechace_form_expert_audio_url_by_key($speechace_key)
{
    $url = qtype_speechace_format_url('serveaudio');
    if (qtype_speechace_is_scoring_server_file_key($speechace_key)) {
        $url .= "&speechace_key=" . urlencode($speechace_key);
    } else {
        $expert_args = explode(',', $speechace_key);
        $expert = trim($expert_args[0]);
        $expertid = trim($expert_args[1]);
        if ($expert != "") {
            $url .= "&expert=".$expert;
        }
        if ($expertid != "") {
            $url .= "&expertid=".$expertid;
        }
    }
    return $url;
}

function qtype_speechace_get_question_img_src($args)
{
    global $CFG;

    $prefix_url = $CFG->wwwroot."/question/type/speechace/serveimage.php?";

    $url = null;
    if ($args->score_type === "scorefreetext") {
        if (object_property_exists($args, 'has_image') && $args->has_image) {
            $expert_args = explode(',', $args->speechace_key);
            $expert = $expert_args[0];
            $expertid = $expert_args[1];
            $url = $prefix_url . "expert=" . $expert . "&expertid=" . $expertid;

        }
    }
    
    return $url;    
}

function qtype_speechace_form_expert_image_url($expert, $expertid)
{
    $url = null;

    if ($expert != "" && $expertid != "") {
        $url = qtype_speechace_format_url('serveimage');
        $url .= "&expert=".$expert;
        $url .= "&expertid=".$expertid;
    }

    return $url;
}

function qtype_speechace_get_fallback_image_info()
{
    $url = dirname(__FILE__) . "/pix/transparent.png";
    $contenttype = "image/png";
    $filename = "transparent.png";

    return array($url, $contenttype, $filename);
}


function qtype_speechace_post_process_score(&$raw_result, $filename, $show_score, $answer_text) {
    $overall_score = 0;
    $raw_result->question_attempt_id = $filename;
    $raw_result->moodle_filename = $filename;

    if (!property_exists($raw_result, 'text_score') && !property_exists($raw_result, 'word_score')) {
        if (property_exists($raw_result, 'text')) {
            $raw_result = qtype_speechace_transform_root_text_score_to_public_v1($raw_result);
        } else if (property_exists($raw_result, 'word')) {
            $raw_result = qtype_speechace_transform_root_word_score_to_public_v1($raw_result);
        }
    }

    $scoreMessagesData = qtype_speechace_deserialize_score_message_setting(get_config("qtype_speechace","scoremessages"));
    $scoreMessagesData_SelectOne = (int)$scoreMessagesData[QTYPE_SPEECHACE_SCOREMESSAGES_SELECTONE];
    $scoreMessagesData_SelectTwo = (int)($scoreMessagesData[QTYPE_SPEECHACE_SCOREMESSAGES_SELECTTWO]);
    // NOTE: $answer_text is necessary because the text in text_score may be tokenized

    if (property_exists($raw_result, 'text_score')) {
        if (!$show_score) {
            $annotation = new stdClass();
            $annotation->stripped = true;
            $text_score = new stdClass();
            $text_score->annotation = $annotation;
            $raw_result->text_score = $text_score;
        } else {
            $text_score = $raw_result->text_score;
            if (property_exists($text_score, 'word_score_list')) {
                $word_score_list = $text_score->word_score_list;
                $all_correct = false;
                if (is_array($word_score_list)) {
                    $total_score = 0;
                    $total_element_count = 0;
                    $all_correct = true;
                    foreach ($word_score_list as $word_score) {
                        list($score, $phone_count) = qtype_speechace_annotate_word_score($word_score);
                        $total_score += $score * $phone_count;
                        $total_element_count += $phone_count;
                        if ($score < $scoreMessagesData_SelectOne){
                            $all_correct = false;
                        }
                        $annotation = new stdClass();
                        $annotation->score = $score;
                        $annotation->correct = $score >= $scoreMessagesData_SelectOne;
                        $word_score->annotation = $annotation;
                    }
                    if ($total_element_count > 0) {
                        $overall_score = round($total_score * 1.0 / $total_element_count);
                    }
                }
                $annotation = new stdClass();
                $annotation->score = $overall_score;
                $annotation->correct = $overall_score >=$scoreMessagesData_SelectOne;
                $annotation->all_correct = $all_correct;
                $annotation->fair = ($overall_score < $scoreMessagesData_SelectOne && $overall_score >= $scoreMessagesData_SelectTwo);
                $annotation->answer_text = $answer_text;
                $text_score->annotation = $annotation;
            }
        }
    } else if (property_exists($raw_result, 'word_score')) {
        if (!$show_score) {
            $annotation = new stdClass();
            $annotation->stripped = true;
            $word_score = new stdClass();
            $word_score->annotation = $annotation;
            $raw_result->word_score = $word_score;
        } else {
            $word_score = $raw_result->word_score;
            list($score, $phone_count) = qtype_speechace_annotate_word_score($word_score);
            $annotation = new stdClass();
            $annotation->score = $score;
            $annotation->correct = $score >= $scoreMessagesData_SelectOne;
            $annotation->all_correct = $annotation->correct;
            $annotation->fair = ($score < $scoreMessagesData_SelectOne &&$score >= $scoreMessagesData_SelectTwo);
            $annotation->answer_text = $answer_text;
            $word_score->annotation = $annotation;
            $overall_score = $score;
        }
    }

    return $overall_score;
}


function qtype_speechace_annotate_word_score($word_score) {
    $overall_score = 0;
    $phone_count = 0;
    if (is_object($word_score)) {
        if (property_exists($word_score, 'phone_score_list')) {
            $phone_score_list = $word_score->phone_score_list;
            if (is_array($phone_score_list)) {
                $phone_count = count($phone_score_list);
                foreach ($phone_score_list as $phone_score) {
                    $score = qtype_speechace_annotate_phone_score($phone_score);
                    $overall_score += $score;
                }
            }
        }
    }
    $average_score = 0;
    if ($phone_count > 0) {
        $average_score = round($overall_score * 1.0 / $phone_count);
    }

    return array($average_score, $phone_count);
}


function qtype_speechace_annotate_phone_score($phone_score) {
    $correct = false;
    $comment = 'Unknown';
    $score = 0;
    if (is_object($phone_score)) {
        $quality_score = 0;
        if (property_exists($phone_score, 'quality_score')) {
            if (is_int($phone_score->quality_score) || is_float($phone_score->quality_score)) {
                $quality_score = $phone_score->quality_score;
            }
        }
        if ($quality_score > QTYPE_SPEECHACE_CORRECT_SCORE_THRESHOLD) {
            if (!qtype_speechace_has_stress_score($phone_score) ||
                ($phone_score->stress_score >= QTYPE_SPEECHACE_CORRECT_SCORE_THRESHOLD)) {

                $correct = true;
                $comment = get_string('scorecomment_good', 'qtype_speechace');
                if (!qtype_speechace_has_stress_score($phone_score)) {
                    $score = $quality_score;
                } else {
                    $score = max($quality_score, $phone_score->stress_score);
                }
            } else {
                if ($phone_score->stress_level > 0) {
                    $comment = get_string('scorecomment_stressmore', 'qtype_speechace');
                } else {
                    $comment = get_string('scorecomment_stressless', 'qtype_speechace');
                }
                $score = max(QTYPE_SPEECHACE_CORRECT_SCORE_THRESHOLD, $phone_score->stress_score);
            }
        } else {
            if (property_exists($phone_score, 'extent') &&
                is_array($phone_score->extent) &&
                (count($phone_score->extent) == 2)) {

                if ($phone_score->extent[0] === $phone_score->extent[1]) {
                    $comment = get_string('scorecomment_missing', 'qtype_speechace');
                    $score = $quality_score;
                } else if (property_exists($phone_score, 'sound_most_like')) {
                    $sound_most_like = $phone_score->sound_most_like;
                    if (($sound_most_like === 'sil') || ($sound_most_like === 'spn')) {
                        $comment = get_string('scorecomment_silent', 'qtype_speechace');
                    } else {
                        $comment = 'Substitution';
                    }
                    $score = $quality_score;
                }
            }
        }
        $annotation = new stdClass();
        $annotation->correct = $correct;
        $annotation->comment = $comment;
        $annotation->score = round($score);
        $phone_score->annotation = $annotation;
    }

    return $score;
}


function qtype_speechace_has_stress_score($phone_score) {

    if (!property_exists($phone_score, 'stress_level') ||
        !is_int($phone_score->stress_level) ||
        !property_exists($phone_score, 'stress_score') ||
        !(is_int($phone_score->stress_score) || is_float($phone_score->stress_score))) {

        return false;

    } else {

        return true;
    }
}


function qtype_speechace_grade_user_audio($stored_file, $question, $inreview) {
    $succeeded = true;
    $overall_score = 0;
    $speechace_results = qtype_speechace_load_score_results($stored_file);
    $score_args = json_decode($question->scoringinfo);
    $dialect = $question->dialect;

    if (empty($speechace_results)) {
        list($succeeded, $speechace_results) = qtype_speechace_grade_audio($score_args, $stored_file, $dialect);
        if ($succeeded) {
            qtype_speechace_save_score_results($speechace_results, $stored_file);
        }
    }

    if ($succeeded) {
        $filename = $stored_file->get_filename();
        $overall_score = qtype_speechace_post_process_score(
            $speechace_results,
                $filename,
                $question->determine_show_score($inreview),
                $score_args->text);
    }

    return array($succeeded, $speechace_results, $overall_score);
}


function qtype_speechace_get_submitted_file($name, $qa, $filename, $contextid) {

    //fetch file from storage and figure out URL
    $storedfiles=$qa->get_last_qt_files($name, $contextid);
    foreach ($storedfiles as $sf){
        //when we find the file that matches the filename in $step, use that
        $storedfilename = strip_tags($sf->get_filename());
        if($filename === $storedfilename){
            return $sf;
        }
    }

    return null;
}


function qtype_speechace_transform_scoringinfo_to_json($scoringinfo) {

    $newScoringInfo = json_decode($scoringinfo);
    if ($newScoringInfo &&
        is_object($newScoringInfo) &&
        object_property_exists($newScoringInfo, 'version') &&
        object_property_exists($newScoringInfo, 'score_type') &&
        object_property_exists($newScoringInfo, 'text')) {

        return $newScoringInfo;
    }

    $score_args = explode(',', $scoringinfo);
    $op = 'scoreword';
    if (count($score_args) >= 1 and preg_match('/##(.+)##/', $score_args[0], $matches)) {
        $op = $matches[1];
    }

    if ($op === 'scoretext') {
        if (count($score_args) >= 7) {
            $newScoringInfo = new stdClass();
            $newScoringInfo->version = '2017-03-15';
            $newScoringInfo->score_type = $op;
            $newScoringInfo->text = trim($score_args[1]);
            $newScoringInfo->letter_list = trim($score_args[2]);
            $newScoringInfo->phone_list = trim($score_args[3]);
            $newScoringInfo->syllable_list = trim($score_args[4]);
            $expert = trim($score_args[5]);
            $expertid = trim($score_args[6]);
            $newScoringInfo->speechace_key = "$expert,$expertid";
            return $newScoringInfo;
        }
    } elseif ($op === "scorefreetext") {
        if (count($score_args) >= 5) {
            $newScoringInfo = new stdClass();
            $newScoringInfo->version = '2017-03-15';
            $newScoringInfo->score_type = $op;
            $tokenized = (int)trim($score_args[4]);
            if ($tokenized) {
                $newScoringInfo->tokenized_text = trim($score_args[1]);
                $newScoringInfo->text = strtr($newScoringInfo->tokenized_text, '|', ' ');
            } else {
                $newScoringInfo->text = trim($score_args[1]);
            }
            $expert = trim($score_args[2]);
            $expertid = trim($score_args[3]);
            $newScoringInfo->speechace_key = "$expert,$expertid";
            if (count($score_args) >= 6) {
                if (trim($score_args[5])) {
                    $newScoringInfo->has_image = true;
                }
            }
            return $newScoringInfo;
        }
    } else {
        if (count($score_args) >= 4) {
            $newScoringInfo = new stdClass();
            $newScoringInfo->version = '2017-03-15';
            $newScoringInfo->score_type = $op;
            $newScoringInfo->text = trim($score_args[0]);
            $newScoringInfo->word_number = trim($score_args[1]);
            $newScoringInfo->pos = trim($score_args[2]);
            $expert = trim($score_args[3]);
            if (count($score_args) >= 5) {
                $expertid = trim($score_args[4]);
                $newScoringInfo->speechace_key = "$expert,$expertid";
            } else {
                $newScoringInfo->speechace_key = $expert;
            }
            return $newScoringInfo;
        }
    }

    return null;
}

function qtype_speechace_extract_scoring_text_from_question_text($questiontext) {
    $text = null;
    $extra = null;
    if (preg_match('/^Say \<span class=["\']qword["\']\>(.+)\<\/span\>(.*)$/', $questiontext, $matches)) {
        $text = strip_tags($matches[1]);
        $extra = $matches[2];
    } elseif (preg_match('/^\<p\>[^:]*: Pronounce (.+) \/(.+)\/ within 10 seconds\<\/p\>(.*)$/', $questiontext, $matches)) {
        $text = strip_tags($matches[1]);
        $extra = $matches[2];
    }

    if ($extra) {
        $extra = trim($extra);
        $newline = '<br>';
        while (substr($extra, 0, strlen($newline)) == $newline) {
            $extra = substr($extra, strlen($newline));
            if ($extra === false) {
                $extra = '';
            }
        }
    }

    return array($text, $extra);
}

function qtype_speechace_trim_draft_area_files($draftitemid, $filelist) {
    global $USER;
    $count = 0;
    $usercontext = context_user::instance($USER->id);
    $fs = get_file_storage();
    $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id');
    if (count($draftfiles) > 1) {
        foreach ($draftfiles as $file) {
            if (!$file->is_directory()) {
                $found = false;
                foreach ($filelist as $filename) {
                    if (($file->get_filename() === $filename) ||
                        ($file->get_filename() === ($filename . QTYPE_SPEECHACE_SCORE_FILE_EXTENSION))) {
                        $found = true;
                        $count = $count + 1;
                        break;
                    }
                }
                if (!$found) {
                    $file->delete();
                }
            }
        }
    }
    return $count;
}

function qtype_speechace_parse_http_response_headers($headers) {
    $head = array();
    foreach( $headers as $k=>$v )
    {
        $t = explode( ':', $v, 2 );
        if( isset( $t[1] ) )
            $head[ strtolower(trim($t[0])) ] = trim( $t[1] );
        else
        {
            $head[] = $v;
            if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
                $head['response_code'] = intval($out[1]);
        }
    }
    return $head;
}

function qtype_speechace_parse_content_disposition($value) {
    $items = explode(';', $value);
    foreach ($items as $item) {
        $parts = explode('=', $item, 2);
        if (isset($parts[1])) {
            $name = trim($parts[0]);
            if ($name === 'speechace_key') {
                $value = trim($parts[1]);
                $value = trim($value, '"');
                if (qtype_speechace_is_scoring_server_file_key($value)) {
                    return $value;
                }
            }
        }
    }
    return null;
}

function qtype_speechace_is_scoring_server_file_key($value) {
    return strpos($value, '/') !== false;
}

function qtype_speechace_filename_from_speechace_key($value) {
    return QTYPE_SPEECHACE_SPEECHACE_KEY_FILE_PREFIX . urlencode($value) . '.wav';
}

function qtype_speechace_determine_audio_mime_type($stored_file) {
    $extension = pathinfo(strip_tags($stored_file->get_filename()), PATHINFO_EXTENSION);
    if ($extension && strtolower($extension) === 'mp3') {
        return 'audio/mp3';
    } else {
        return 'audio/x-wav';
    }
}

function qtype_speechace_transform_root_text_score_to_public_v1($old_text_score) {
    if (object_property_exists($old_text_score, 'word_score_list') &&
        is_array($old_text_score->word_score_list)) {

        foreach ($old_text_score->word_score_list as $word_score) {
            qtype_speechace_transform_word_score_to_public_v1($word_score);
        }
    }
    $new_root = new stdClass();
    $new_root->status = "success";
    $new_root->quota_remaining = -1;
    $new_root->text_score = $old_text_score;
    return $new_root;
}

function qtype_speechace_transform_root_word_score_to_public_v1($old_word_score) {
    qtype_speechace_transform_word_score_to_public_v1($old_word_score);

    $new_root = new stdClass();
    $new_root->status = "success";
    $new_root->quota_remaining = -1;
    $new_root->word_score = $old_word_score;
    return $new_root;
}

function qtype_speechace_transform_word_score_to_public_v1($old_word_score) {
    if (object_property_exists($old_word_score, 'duration_score')) {
        unset($old_word_score->duration_score);
    }
    if (object_property_exists($old_word_score, 'phone_score_list') &&
        is_array($old_word_score->phone_score_list)) {
        foreach ($old_word_score->phone_score_list as $phone_score) {
            qtype_speechace_transform_phone_score_to_public_v1($phone_score);
        }
    }
    if (object_property_exists($old_word_score, 'syllable_score_list') &&
        is_array($old_word_score->syllable_score_list)) {
        foreach ($old_word_score->syllable_score_list as $syllable_score) {
            qtype_speechace_transform_syllable_score_to_public_v1($syllable_score);
        }
    }
}

function qtype_speechace_transform_phone_score_to_public_v1($old_phone_score) {
    if (object_property_exists($old_phone_score, 'substitute_phone')) {
        $old_phone_score->sound_most_like = $old_phone_score->substitute_phone;
        unset($old_phone_score->substitute_phone);
    }
    unset($old_phone_score->duration_score);
    unset($old_phone_score->ref_extent);
    if (object_property_exists($old_phone_score, 'child_phones') &&
        is_array($old_phone_score->child_phones)) {
        $new_child_phones = [];
        foreach ($old_phone_score->child_phones as $old_child_phone) {
            if (object_property_exists($old_child_phone, 'extent') &&
                object_property_exists($old_child_phone, 'quality_score') &&
                object_property_exists($old_child_phone, 'symbol_list') &&
                is_array($old_child_phone->symbol_list) &&
                (count($old_child_phone->symbol_list) >= 1)) {

                $new_item = new stdClass();
                $new_item->extent = $old_child_phone->extent;
                $new_item->quality_score = $old_child_phone->quality_score;
                $new_item->sound_most_like = $old_child_phone->symbol_list[0];

                $new_child_phones[] = $new_item;
            }
        }
        if (count($new_child_phones)) {
            $old_phone_score->child_phones = $new_child_phones;
        }
    }
}

function qtype_speechace_transform_syllable_score_to_public_v1($old_syllable_score) {
    unset($old_syllable_score->duration_score);
    unset($old_syllable_score->ref_extent);
}

function qtype_speechace_rewrite_json_error($result) {
    if ($result &&
        is_object($result) &&
        object_property_exists($result, 'status') &&
        ($result->status === 'error')) {

        if (object_property_exists($result, 'short_message')) {
            if ($result->short_message === 'error_key_invalid') {
                $result->short_message = 'error_productkey_invalid';
                $result->detail_message = get_string('error_productkey_invalid', 'qtype_speechace');
            } else if ($result->short_message === 'error_key_expired') {
                $result->short_message = 'error_productkey_expired';
                $result->detail_message = get_string('error_productkey_expired', 'qtype_speechace');
            } else if ($result->short_message === 'error_no_speech') {
                $result->detail_message = get_string('error_no_speech', 'qtype_speechace');
            }
        }
    }
}

function qtype_speechace_get_url_contents($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

function qtype_speechace_get_url_headers_and_contents($url) {
    $ch = curl_init();
    $headers = [];
    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    // this function is called by curl for each header received
    curl_setopt($ch, CURLOPT_HEADERFUNCTION,
        function($curl, $header) use (&$headers)
        {
            $headers[] = $header;
            $len = strlen($header);

            return $len;
        }
    );

    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        $error_message = curl_error($ch);
        throw new qtype_speechace_exception('error_http_api_call', $error_message);
    }


    curl_close($ch);

    return array($headers, $data);
}

function qtype_speechace_deserialize_score_message_setting($raw_value) {
    $json_obj = json_decode($raw_value, true);
    if ($json_obj) {
        $migration_map = array(
            'text1' => QTYPE_SPEECHACE_SCOREMESSAGES_TEXTONE,
            'text2' => QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTWO,
            'text3' => QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTHREE,
            'select1' => QTYPE_SPEECHACE_SCOREMESSAGES_SELECTONE,
            'select2' => QTYPE_SPEECHACE_SCOREMESSAGES_SELECTTWO,
        );
        foreach ($migration_map as $old_key => $new_key) {
            if (array_key_exists($old_key, $json_obj)) {
                $json_obj[$new_key] = $json_obj[$old_key];
                unset($json_obj[$old_key]);
            }
        }
    }
    return $json_obj;
}

