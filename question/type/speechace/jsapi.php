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
 * Javascript AJAX API for the speechace question type plugin.
 *
 * @package    qtype
 * @subpackage speechace
 * @copyright  2017 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/question/type/speechace/speechacelib.php');

// Ensure that getallheaders is defined.
// Note that we put this script here instead of near qtype_speechace_serve_file
// because qtype_speechace_serve_file would be called before this script is run.
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = '';
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Check login and get context.
$contextid = required_param('contextid', PARAM_INT);
list($context, $course, $cm) = get_context_info_array($contextid);
require_login($course, false, $cm, false, true);

require_sesskey();

$input_command = required_param('command', PARAM_TEXT);
if ($input_command === 'score') {
    $score_args = array();
    $score_args['audioblob'] = required_param('audioblob', PARAM_TEXT);
    $score_args['contextid'] = $contextid;
    $score_args['component'] = required_param('component', PARAM_TEXT);
    $score_args['filearea'] = required_param('filearea', PARAM_TEXT);
    $score_args['itemid'] = required_param('itemid', PARAM_INT);
    $score_args['usageid'] = required_param('usageid', PARAM_INT);
    $score_args['slot'] = required_param('slot', PARAM_INT);
    $score_args['filenameprefix'] = QTYPE_SPEECHACE_QUESTION_ATTEMPT_FILE_PREFIX;
    qtype_speechace_score_audio($score_args);

} else if ($input_command === 'fetch') {

    $fetch_args = array();
    $fetch_args['ownertype'] = required_param('ownertype', PARAM_TEXT);
    $fetch_args['contextid'] = $contextid;
    $fetch_args['usageid'] = required_param('usageid', PARAM_INT);
    $fetch_args['slot'] = required_param('slot', PARAM_INT);

    if ($fetch_args['ownertype'] === 'user') {
        $fetch_args['filename'] = required_param('filename', PARAM_TEXT);
        qtype_speechace_fetch_user_audio($fetch_args);
    } else if ($fetch_args['ownertype'] === 'expert') {
        qtype_speechace_fetch_expert_audio($fetch_args);
    } else {
        throw new invalid_parameter_exception("invalid ownertype {$fetch_args['ownertype']}");
    }

} else if ($input_command === 'fetchscore') {

    $fetch_args = array();
    $fetch_args['contextid'] = $contextid;
    $fetch_args['usageid'] = required_param('usageid', PARAM_INT);
    $fetch_args['slot'] = required_param('slot', PARAM_INT);
    $fetch_args['filename'] = required_param('filename', PARAM_TEXT);

    qtype_speechace_fetch_user_audio_score($fetch_args);

} else if ($input_command === 'fetchquestionaudio') {

    $fetch_args = array();
    $fetch_args['contextid'] = $contextid;
    $fetch_args['type'] = required_param('type', PARAM_TEXT);

    if ($fetch_args['type'] === 'speechace_key') {
        $fetch_args['moodleitemid'] = required_param('moodleitemid', PARAM_INT);
        $fetch_args['speechace_key'] = required_param('speechace_key', PARAM_TEXT);
        if (qtype_speechace_is_scoring_server_file_key($fetch_args['speechace_key'])) {
            $fetch_args['moodle_key'] = qtype_speechace_filename_from_speechace_key($fetch_args['speechace_key']);
            qtype_speechace_fetch_draft_moodle_audio($fetch_args);
        } else {
            qtype_speechace_fetch_speechace_audio_by_key($fetch_args);
        }
    } else if ($fetch_args['type'] === 'moodle_key') {
        $fetch_args['moodleitemid'] = required_param('moodleitemid', PARAM_INT);
        $fetch_args['moodle_key'] = required_param('moodle_key', PARAM_TEXT);
        qtype_speechace_fetch_draft_moodle_audio($fetch_args);
    } else {
        throw new invalid_parameter_exception('invalid type of fetchquestionaudio ' . $fetch_args['type']);
    }
} else if ($input_command === 'stagequestionaudio') {

    $stage_args = array();
    $stage_args['audioblob'] = required_param('audioblob', PARAM_TEXT);
    $stage_args['contextid'] = $contextid;
    $stage_args['itemid'] = required_param('moodleitemid', PARAM_INT);
    $stage_args['component'] = 'user';
    $stage_args['filearea'] = 'draft';
    $stage_args['filenameprefix'] = QTYPE_SPEECHACE_MOODLE_KEY_FILE_PREFIX;

    qtype_speechace_stage_audio_file($stage_args);

} else if ($input_command === 'fetchspeechfromtext') {

    $fetch_args = array();
    $fetch_args['text'] = required_param('text', PARAM_TEXT);
    $fetch_args['contextid'] = $contextid;
    $fetch_args['itemid'] = required_param('moodleitemid', PARAM_INT);
    $fetch_args['component'] = 'user';
    $fetch_args['filearea'] = 'draft';
    $fetch_args['dialect'] = required_param('dialect',PARAM_TEXT);

    qtype_speechace_fetch_speech_from_text($fetch_args);

} else {

    throw new invalid_parameter_exception("invalid command {$input_command}");
}


function qtype_speechace_score_audio($args) {
    $succeeded = true;
    $qa = qtype_speechace_get_question_attempt($args);
    $question = $qa->get_question();
    $score_args = json_decode($question->scoringinfo);
    $dialect = $question->dialect;

    $stored_file = null;
    $use_cache = false;
    if ($args['audioblob'] !== 'expert') {
        $stored_file = qtype_speechace_save_audio_file($args);
    } else {
        $scoringinfo_obj = new qtype_speechace_scoringinfo($score_args);
        if ($scoringinfo_obj->getSourceType() === 'moodle_key') {
            $fetch_args['contextid'] = $question->contextid;
            $fetch_args['questionid'] = $question->id;
            $fetch_args['fileprefix'] = QTYPE_SPEECHACE_MOODLE_KEY_FILE_PREFIX;
            $stored_file = qtype_speechace_find_current_moodle_file($fetch_args);
            if (!$stored_file) {
                $succeeded = false;
            } else {
                $use_cache = true;
            }
        }
    }

    $speechace_results = null;
    if ($succeeded) {
        if ($use_cache) {
            $speechace_results = qtype_speechace_load_score_results($stored_file);
        }
        if (empty($speechace_results)) {
            list($succeeded, $speechace_results) = qtype_speechace_grade_audio($score_args, $stored_file ,$dialect);
        }
    }
    if ($succeeded && ($args['audioblob'] !== 'expert')) {
        qtype_speechace_save_score_results($speechace_results, $stored_file);
        $filename = $stored_file->get_filename();
        qtype_speechace_post_process_score(
            $speechace_results,
                $filename,
                $question->determine_show_score(false),
                $score_args->text);
    }

    qtype_speechace_rewrite_json_error($speechace_results);

    if ($succeeded) {
        header('Content-Type: application/json');
        $output = json_encode($speechace_results);
        echo $output;
    } else {
        header('Content-Type: application/json');
        if ($speechace_results) {
            $output = json_encode($speechace_results);
        } else {
            $output = qtype_speechace_create_error_response(null, null);
        }
        echo $output;
    }
}


function qtype_speechace_fetch_user_audio($args) {

    $qa = qtype_speechace_get_question_attempt($args);

    $audio_file = qtype_speechace_get_submitted_file(
        'answer',
        $qa,
        $args['filename'],
        $args['contextid']);

    if (!$audio_file) {
        header('HTTP/1.1 404 Not Found');
    } else {

        qtype_speechace_serve_file(
            $audio_file,
            qtype_speechace_determine_audio_mime_type($audio_file));
    }
}


class qtype_speechace_simple_stored_file {
    private $content;
    private $filename;

    public function __construct($input_content, $input_filename) {
        $this->content = $input_content;
        $this->filename = $input_filename;
    }

    public function get_content() {
        return $this->content;
    }

    public function get_filename() {
        return $this->filename;
    }
}


function qtype_speechace_fetch_expert_audio($args) {

    $qa = qtype_speechace_get_question_attempt($args);
    $question = $qa->get_question();
    $score_args = json_decode($question->scoringinfo);
    $scoringinfo_obj = new qtype_speechace_scoringinfo($score_args);
    if ($scoringinfo_obj->getSourceType() === 'moodle_key') {
        $fetch_args = array();
        $fetch_args['contextid'] = $question->contextid;
        $fetch_args['questionid'] = $question->id;
        $fetch_args['fileprefix'] = QTYPE_SPEECHACE_MOODLE_KEY_FILE_PREFIX;
        qtype_speechace_fetch_current_moodle_audio($fetch_args);
    } else {
        $speechace_key = $scoringinfo_obj->getSpeechaceKey();
        if (qtype_speechace_is_scoring_server_file_key($speechace_key)) {
            $fetch_args = array();
            $fetch_args['contextid'] = $question->contextid;
            $fetch_args['questionid'] = $question->id;
            $fetch_args['fileprefix'] = QTYPE_SPEECHACE_SPEECHACE_KEY_FILE_PREFIX;
            qtype_speechace_fetch_current_moodle_audio($fetch_args);
        } else {
            qtype_speechace_fetch_speechace_audio_by_scoringinfo($score_args);
        }
    }
}


function qtype_speechace_find_current_moodle_file($args) {
    $fs = get_file_storage();
    $files = $fs->get_area_files($args['contextid'], 'qtype_speechace', 'scoringinfo', $args['questionid']);
    $moodle_audio_file = null;
    $audio_mimetype_prefix = 'audio/';
    if ($files && (count($files) > 0)) {
        $prefix = $args['fileprefix'];
        foreach ($files as $file) {
            if ((substr($file->get_filename(), 0, strlen($prefix)) === $prefix) &&
                (substr($file->get_mimetype(), 0, strlen($audio_mimetype_prefix)) === $audio_mimetype_prefix)) {
                if (!$moodle_audio_file) {
                    $moodle_audio_file = $file;
                } elseif ($file->get_timemodified() > $moodle_audio_file->get_timemodified()) {
                    $moodle_audio_file = $file;
                }
            }
        }
    }
    return $moodle_audio_file;
}


function qtype_speechace_fetch_current_moodle_audio($args) {
    $moodle_audio_file = qtype_speechace_find_current_moodle_file($args);
    if (!$moodle_audio_file) {
        header('HTTP/1.1 404 Not Found');
    } else {
        qtype_speechace_serve_file(
            $moodle_audio_file,
            qtype_speechace_determine_audio_mime_type($moodle_audio_file));
    }
}


function qtype_speechace_fetch_draft_moodle_audio($args) {
    global $USER;
    $fs = get_file_storage();
    $files = $fs->get_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $args['moodleitemid']);
    $moodle_audio_file = null;
    if ($files && (count($files) > 0)) {
        foreach ($files as $file) {
            if ($file->get_filename() === $args['moodle_key']) {
                $moodle_audio_file = $file;
                break;
            }
        }
    }
    if (!$moodle_audio_file) {
        header('HTTP/1.1 404 Not Found');
    } else {
        qtype_speechace_serve_file(
            $moodle_audio_file,
            qtype_speechace_determine_audio_mime_type($moodle_audio_file));
    }
}


function qtype_speechace_fetch_speechace_audio_by_key($args) {
    try {
        $speechace_audio_file_url = qtype_speechace_form_expert_audio_url_by_key($args['speechace_key']);
        $speechace_audio_file_content = qtype_speechace_get_url_contents($speechace_audio_file_url);
        $speechace_audio_file = new qtype_speechace_simple_stored_file(
            $speechace_audio_file_content,
            'speechace-expert.wav');
        qtype_speechace_serve_file(
            $speechace_audio_file,
            qtype_speechace_determine_audio_mime_type($speechace_audio_file));
    } catch (qtype_speechace_exception $ex) {
        header('HTTP/1.1 ' . $ex->getHttpErrorStatusMessage());
    }
}


function qtype_speechace_fetch_speechace_audio_by_scoringinfo($score_args) {
    try {
        $speechace_audio_file = null;
        if ($score_args) {
            $speechace_audio_file_url = qtype_speechace_form_expert_audio_url($score_args);
            $speechace_audio_file_content = qtype_speechace_get_url_contents($speechace_audio_file_url);
            $speechace_audio_file = new qtype_speechace_simple_stored_file(
                $speechace_audio_file_content,
                'expert.wav');
        }
        if (!$speechace_audio_file) {
            header('HTTP/1.1 404 Not Found');
        } else {
            qtype_speechace_serve_file(
                $speechace_audio_file,
                qtype_speechace_determine_audio_mime_type($speechace_audio_file));
        }
    } catch (qtype_speechace_exception $ex) {
        header('HTTP/1.1 ' . $ex->getHttpErrorStatusMessage());
    }
}


function qtype_speechace_fetch_user_audio_score($args) {
    $qa = qtype_speechace_get_question_attempt($args);

    $stored_file = qtype_speechace_get_submitted_file(
        'answer',
        $qa,
        $args['filename'],
        $args['contextid']);

    if (!$stored_file) {
        header('HTTP/1.1 404 Not Found');
    } else {
        $question = $qa->get_question();
        $inreview = $qa->get_state()->is_finished();
        list($succeeded, $speechace_results, $score) = qtype_speechace_grade_user_audio($stored_file, $question, $inreview);

        qtype_speechace_rewrite_json_error($speechace_results);

        if ($succeeded) {
            header('Content-Type: application/json');
            $output = json_encode($speechace_results);
            echo $output;
        } else {
            header('Content-Type: application/json');
            if ($speechace_results) {
                $output = json_encode($speechace_results);
            } else {
                $output = qtype_speechace_create_error_response(null, null);
            }
            echo $output;
        }
    }
}


function qtype_speechace_save_audio_file($args) {
    global $CFG, $USER;

    $fs = get_file_storage();
    $filepath = "/";

    $record = new stdClass();
    $record->filearea = $args['filearea'];
    $record->component = $args['component'];
    $record->filepath = $filepath;
    $record->itemid = $args['itemid'];
    $record->license = $CFG->sitedefaultlicense;
    $record->contextid = context_user::instance($USER->id)->id;
    $record->userid = $USER->id;
    $record->source = '';

    if (array_key_exists('filename', $args)) {
        $filename = $args['filename'];
        $random_file = false;
    } else {
        $filenamebase = $args['filenameprefix'] . rand(100,32767) . rand(100,32767);
        $fileextension = ".wav";
        $filename = $filenamebase . $fileextension;
        $random_file = true;
    }
    $record->filename = $filename;

    $file_exists = $fs->file_exists(
        $record->contextid,
        $record->component,
        $record->filearea,
        $record->itemid,
        $filepath,
        $record->filename);
    if ($file_exists && $random_file) {
        throw new invalid_state_exception("random file colliding with an existing file {$record->filename}");
    }

    if ($file_exists) {
        $stored_file = $fs->get_file(
            $record->contextid,
            $record->component,
            $record->filearea,
            $record->itemid,
            $record->filepath,
            $record->filename);
    } else {
        if (array_key_exists('audiobin', $args)) {
            $xaudioblob = $args['audiobin'];
        } else {
            $audioblob = $args['audioblob'];

            // check there is no metadata prefixed to the base 64.
            // if so it will look like this: data:image/png;base64,iVBORw0K,
            // we remove it
            $metapos = strPos($audioblob, ",");
            if($metapos >10 && $metapos <30){
                $audioblob = substr($audioblob, $metapos + 1);
            }

            $xaudioblob = base64_decode($audioblob);
        }
        $stored_file = $fs->create_file_from_string($record, $xaudioblob);
    }

    return $stored_file;
}


function qtype_speechace_stage_audio_file($args) {
    $staged_file = qtype_speechace_save_audio_file($args);
    if ($staged_file) {
        $result = new stdClass();
        $result->status = "success";
        $result->moodle_filename = $staged_file->get_filename();
        $result->question_attempt_id = $result->moodle_filename;
        $annotation = new stdClass();
        $annotation->stripped = true;
        $text_score = new stdClass();
        $text_score->annotation = $annotation;
        $result->text_score = $text_score;
    } else {
        $result = qtype_speechace_create_error_response(null, null);
    }
    header('Content-Type: application/json');
    $output = json_encode($result);
    echo $output;
}


function qtype_speechace_fetch_speech_from_text($args) {
    list($succeeded, $result, $result_json) = qtype_speechace_create_speech_from_text($args['text'],$args['dialect']);
    if ($succeeded) {
        $args['filename'] = qtype_speechace_filename_from_speechace_key($result_json->speechace_key);
        $args['audiobin'] = $result;
        qtype_speechace_save_audio_file($args);
    }

    qtype_speechace_rewrite_json_error($result_json);

    if ($succeeded) {
        header('Content-Type: application/json');
        $output = json_encode($result_json);
        echo $output;
    } else {
        header('Content-Type: application/json');
        if ($result_json) {
            $output = json_encode($result_json);
        } else {
            $output = qtype_speechace_create_error_response(null, null);
        }
        echo $output;
    }
}


function qtype_speechace_get_question_attempt($args) {
    try {
        $quiz_attempt_or_quba = quiz_attempt::create_from_usage_id($args['usageid']);
    } catch (dml_missing_record_exception $e) {
        // this may be a preview mode
        global $DB;
        $usage = $DB->get_record(
            'question_usages',
            array('id' => $args['usageid']),
            '*',
            MUST_EXIST);
        if ($usage->component !== 'core_question_preview') {
            throw $e;
        }
        $quiz_attempt_or_quba = question_engine::load_questions_usage_by_activity($args['usageid']);
    }
    $qa = $quiz_attempt_or_quba->get_question_attempt($args['slot']);
    return $qa;
}


function qtype_speechace_create_error_response($short_message, $detail_message) {
    if ($short_message === null) {
        $short_message = "error_internal";
        $detail_message = "An internal server error occurred.";
    }
    $root = new stdClass();
    $root->status = "error";
    $root->short_message = $short_message;
    $root->detail_message = $detail_message;
    $output = json_encode($root);
    return $output;
}


function qtype_speechace_serve_file($stored_file, $mime_format)
{
    if (is_a($stored_file, 'stored_file')) {
        send_stored_file($stored_file);
    } else {
        $status_error_code = null;
        $status_error_message = null;

        $req_headers = getallheaders();
        // foreach ($req_headers as $name => $value) {
        //   error_log("serveaudio header: $name : $value");
        // }
        $handle_range = true;
        $request_first_byte_pos = 0;
        $request_last_byte_pos = 0;

        if (isset($req_headers['Range'])) {
            $range_list = qtype_speechace_get_request_range($req_headers);
        } else {
            $range_list = false;
        }

        if ($range_list === false) {
            $handle_range = false;
        } else if ($range_list === NULL) {
            $handle_range = false;
        } else {
            if (count($range_list) !== 1) {
                // We don't support more than one ranges for now.
                $handle_range = false;
            } else {
                $request_first_byte_pos = $range_list[0][0];
                $request_last_byte_pos = $range_list[0][1];
            }
        }

        $filename = $stored_file->get_filename();
        $filenameset = "Content-Disposition:attachment; filename=\"".$filename.".wav\"";

        $content = $stored_file->get_content();
        $file_size = strlen($content);
        if ($handle_range) {
            if ($request_first_byte_pos >= $file_size) {
                $status_error_code = 416;
                $status_error_message = "Requested range not satisfiable";
                header('Accept-Ranges: bytes');
                header('Content-Range: bytes */' . $file_size);
            }
        }

        if ($status_error_code === NULL) {
            header('Accept-Ranges: bytes');
            header('Content-Type: ' . $mime_format);
            $content_length = $file_size;
            $first_byte_pos = 0;
            $last_byte_pos = $content_length - 1;

            if ($handle_range) {
                if ($request_first_byte_pos < 0) {
                    if (($request_first_byte_pos + $file_size) > 0) {
                        $first_byte_pos = ($request_first_byte_pos + $file_size);
                    }
                    $content_length = $last_byte_pos - $first_byte_pos + 1;
                } else {
                    $first_byte_pos = $request_first_byte_pos;
                    if (($request_last_byte_pos < $last_byte_pos) && ($request_last_byte_pos >= $first_byte_pos)) {
                        $last_byte_pos = $request_last_byte_pos;
                    }
                    $content_length = $last_byte_pos - $first_byte_pos + 1;
                }
                $status_error_code = 206;
                $status_error_message = "Partial Content";
            }
            if ($status_error_code !== NULL) {
                header("HTTP/1.1 {$status_error_code} {$status_error_message}");
            }
            header('Content-Length:' . $content_length);
            header('Content-Range: bytes ' . $first_byte_pos . '-' . $last_byte_pos . '/' . $file_size);
            header($filenameset);
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: no-cache');
            print substr($content, $first_byte_pos, $content_length);
        } else {
            header("HTTP/1.1 {$status_error_code} {$status_error_message}");
        }

        return;
    }
}

function qtype_speechace_get_request_range($req_headers)
{
    $range_valid = true;
    $result_range_list = NULL;
    $range = $req_headers['Range'];
    if ($range) {
        $range_exist = true;
        // According to http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html:
        //   A byte range operation MAY specify a single range of bytes, or a set of ranges within a single entity.
        //     ranges-specifier = byte-ranges-specifier
        //     byte-ranges-specifier = bytes-unit "=" byte-range-set
        //     byte-range-set  = 1#( byte-range-spec | suffix-byte-range-spec )
        //     byte-range-spec = first-byte-pos "-" [last-byte-pos]
        //     first-byte-pos  = 1*DIGIT
        //     last-byte-pos   = 1*DIGIT
        //
        //  ...
        //
        //     suffix-byte-range-spec = "-" suffix-length
        //     suffix-length = 1*DIGIT
        //
        //  ...
        //
        //  Examples of byte-ranges-specifier values (assuming an entity-body of length 10000):
        //  * The first 500 bytes (byte offsets 0-499, inclusive):
        //  bytes=0-499
        //
        //  * The second 500 bytes (byte offsets 500-999, inclusve):
        //  bytes=500-999
        //
        //  * The final 500 bytes (byte offsets 9500-9999, inclusive):
        //  bytes=-500
        //  OR
        //  bytes=9500-
        //
        //  * The first byte and the last byte only (bytes 0 and 9999):
        //  bytes=0-0,-1

        $fields = explode("=", $range);
        if (count($fields) == 2) {
            if ($fields[0] === "bytes") {
                $result_range_list = array();
                $range_list = explode(",", $fields[1]);
                foreach ($range_list as $range_item) {
                    $dash_pos = strpos($range_item, "-");
                    if ($dash_pos === false) {
                        $range_valid = false;
                        break;
                    }
                    $first_byte_pos_str = NULL;
                    $last_byte_pos_str = NULL;
                    if ($dash_pos === 0) {
                        $last_byte_pos_str = substr($range_item, $dash_pos + 1);
                    } else if (($dash_pos + 1) === strlen($range_item)) {
                        $first_byte_pos_str = substr($range_item, 0, $dash_pos);
                    } else {
                        $first_byte_pos_str = substr($range_item, 0, $dash_pos);
                        $last_byte_pos_str = substr($range_item, $dash_pos + 1);
                    }
                    $first_byte_pos = 0;
                    $last_byte_pos = 0;
                    if ($first_byte_pos_str !== NULL) {
                        if (!ctype_digit($first_byte_pos_str)) {
                            $range_valid = false;
                            break;
                        }
                        $first_byte_pos = intval($first_byte_pos_str);
                        if ($last_byte_pos_str === NULL) {
                            $last_byte_pos = -1;
                        } else {
                            if (!ctype_digit($last_byte_pos_str)) {
                                $range_valid = false;
                                break;
                            }
                            $last_byte_pos = intval($last_byte_pos_str);
                            if ($last_byte_pos < $first_byte_pos) {
                                $range_valid = false;
                                break;
                            }
                        }
                    } else {
                        if (!ctype_digit($last_byte_pos_str)) {
                            $range_valid = false;
                            break;
                        }
                        $first_byte_pos = -1 * intval($last_byte_pos_str);
                        $last_byte_pos = -1;
                    }
                    array_push($result_range_list, array($first_byte_pos, $last_byte_pos));
                }
            } else {
                $range_valid = false;
            }
        } else {
            $range_valid = false;
        }
    } else {
        $range_exist = false;
    }

    if ($range_valid === false) {
        return false;
    } else {
        return $result_range_list;
    }
}
