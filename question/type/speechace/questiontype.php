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
 * Question type class for the speechace question type.
 *
 * @package    qtype
 * @subpackage speechace
 * @copyright  2017 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
require_once('speechacelib.php');
require_once('constant.php');

/**
 * The speechace question type.
 *
 * @copyright  2014 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_speechace extends question_type {

    public function response_file_areas() {
        return array('answer');
    }

    public function get_question_options($question) {
        global $DB;
        $question->options = $DB->get_record('qtype_speechace_opts',
                array('questionid' => $question->id), '*', MUST_EXIST);
        parent::get_question_options($question);
    }

    public function save_question_options($formdata) {
        global $DB;
        $context = $formdata->context;

        $options = $DB->get_record('qtype_speechace_opts', array('questionid' => $formdata->id));
        if (!$options) {
            $options = new stdClass();
            $options->questionid = $formdata->id;
            $options->id = $DB->insert_record('qtype_speechace_opts', $options);
        }

        if ($formdata->scoringinfo[QTYPE_SPEECHACE_FORM_MOODLEITEMID]) {
            $filelist = [];
            if ($formdata->scoringinfo[QTYPE_SPEECHACE_FROM_MOODLEKEY]) {
                array_push($filelist, $formdata->scoringinfo[QTYPE_SPEECHACE_FROM_MOODLEKEY]);
            }
            if ($formdata->scoringinfo[QTYPE_SPEECHACE_FORM_SPEECHACEKEY]) {
                $speechace_key = $formdata->scoringinfo[QTYPE_SPEECHACE_FORM_SPEECHACEKEY];
                if (qtype_speechace_is_scoring_server_file_key($speechace_key)) {
                    array_push($filelist, qtype_speechace_filename_from_speechace_key($speechace_key));
                }
            }
            $maxfiles = qtype_speechace_trim_draft_area_files(
                $formdata->scoringinfo[QTYPE_SPEECHACE_FORM_MOODLEITEMID],
                $filelist
            );

            file_save_draft_area_files($formdata->scoringinfo[QTYPE_SPEECHACE_FORM_MOODLEITEMID],
                $context->id, 'qtype_speechace', 'scoringinfo', $formdata->id,
                array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => $maxfiles));
        } else {
            $formdata->scoringinfo[QTYPE_SPEECHACE_FORM_MOODLEITEMID] = null;
        }
        if (object_property_exists($options, 'scoringinfo')) {
            $scoringinfo_obj = qtype_speechace_scoringinfo::deserialize($options->scoringinfo);
            $scoringinfo_obj->applyFormData($formdata->scoringinfo);
        } else {
            $scoringinfo_obj = qtype_speechace_scoringinfo::createFromFormData($formdata->scoringinfo);
        }
        $options->scoringinfo = $scoringinfo_obj->serialize();

        if (isset($formdata->showanswer)) {
            $options->showanswer = $formdata->showanswer;
        } else {
            $options->showanswer = $this->show_answer_default();
        }

        if (isset($formdata->showresult)) {
            $options->showresult = $formdata->showresult;
        } else {
            $options->showresult = $this->show_result_default();
        }
        if(isset($formdata->dialect)) {
            $options->dialect =  $formdata->dialect;}
        else{
            $options->dialect= $this->show_dialect_default();
        }

        if(isset($formdata->showexpertaudio)) {
            $options->showexpertaudio =  $formdata->showexpertaudio;}
        else {
            $options->showexpertaudio = $this->show_expert_audio_default();
        }


        $DB->update_record('qtype_speechace_opts', $options);
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
		$question->scoringinfo=$questiondata->options->scoringinfo;
		$question->showanswer=$questiondata->options->showanswer;
		$question->showresult=$questiondata->options->showresult;
		$question->dialect= $questiondata->options->dialect;
		$question->showexpertaudio= $questiondata->options->showexpertaudio;
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $fs = get_file_storage();
        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'qtype_speechace', 'scoringinfo', $questionid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $fs = get_file_storage();
        $fs->delete_area_files($contextid, 'qtype_speechace', 'scoringinfo', $questionid);
    }
    
    /**
     * If your question type has a table that extends the question table, and
     * you want the base class to automatically save, backup and restore the extra fields,
     * override this method to return an array wherer the first element is the table name,
     * and the subsequent entries are the column names (apart from id and questionid).
     *
     * @return mixed array as above, or null to tell the base class to do nothing.
     */


    public function extra_question_fields() {
    	$tableinfo = array("qtype_speechace_opts",
            "scoringinfo",
            "showanswer",
            "showresult",
            "dialect",
            "showexpertaudio"
        );

        return $tableinfo;
    }
    
    /**
     * Export question to the Moodle XML format
     *
     * Export question using information from extra_question_fields function
     * We override this because we need to export file fields as base 64 strings, not ids
     */
    public function export_to_xml($question, qformat_xml $format, $extra=null) {

		//get file storage
		$fs = get_file_storage();
		$expout ="";

        $expout .= "    <scoringinfo>\n";
        $expout .= $format->writetext($question->options->scoringinfo, 3);
        $expout .= $format->write_files($fs->get_area_files($question->contextid, 'qtype_speechace',
                'scoringinfo', $question->id));
        $expout .= "    </scoringinfo>\n";
        $expout .= "    <showanswer>" . $question->options->showanswer . "</showanswer>\n";
        $expout .= "    <showresult>" . $question->options->showresult . "</showresult>\n";
        $expout .= "    <dialect>" . $question->options->dialect . "</dialect>\n";
        $expout .= "    <showexpertaudio>" . $question->options->showexpertaudio . "</showexpertaudio>\n";

        return $expout;
   
    }
    
    /**
     * Imports question from the Moodle XML format
     *
     * Imports question using information from extra_question_fields function
     * If some of you fields contains id's you'll need to reimplement this
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        global $CFG;
    
        $question_type = "speechace";
        
        //omit table name
        $qo = $format->import_headers($data);
        $qo->qtype = $question_type;
        $q = $data;

        $scoringinfo_path = array('#', 'scoringinfo', 0);
		$scoringinfo_json = $format->getpath($q,
            array_merge($scoringinfo_path, array('#', 'text', 0, '#')), null, true);
		$scoringinfo_moodleitemid = null;
		$scoringinfo_obj = null;
		if ($scoringinfo_json) {
            $scoringinfo_moodleitemid = $format->import_files_as_draft($format->getpath($q,
                array_merge($scoringinfo_path, array('#', 'file')), array(), false));
            $scoringinfo_obj = qtype_speechace_scoringinfo::deserialize($scoringinfo_json);
        } else {
		    $scoringinfo_json = $format->getpath($q,
                array('#', 'scoringinfo', 0, '#'), null);
		    if (!$scoringinfo_json) {
		        $scoringinfo_json = $qo->name;
            }
            $inner_scoringinfo_obj = qtype_speechace_transform_scoringinfo_to_json($scoringinfo_json);
            if ($inner_scoringinfo_obj) {
                list($scoringText, $extra) = qtype_speechace_extract_scoring_text_from_question_text($qo->questiontext);
                $newQuestionText = null;
                if ($scoringText) {
                    $qo->name = $scoringText;
                    if ($extra) {
                        $qo->questiontext = $extra;
                    } else {
                        $qo->questiontext = '&nbsp;';
                    }
                } else {
                    $qo->name = $inner_scoringinfo_obj->text;
                }
                $scoringinfo_obj = new qtype_speechace_scoringinfo($inner_scoringinfo_obj);
            }
        }
        $scoringinfo = null;
		if ($scoringinfo_obj) {
		    $scoringinfo = array(
		        QTYPE_SPEECHACE_FORM_TEXT => $scoringinfo_obj->getText(),
                QTYPE_SPEECHACE_FORM_SOURCETYPE => $scoringinfo_obj->getSourceType(),
                QTYPE_SPEECHACE_FORM_MOODLEITEMID => $scoringinfo_moodleitemid,
                QTYPE_SPEECHACE_FORM_SPEECHACEKEY => $scoringinfo_obj->getSpeechaceKey(),
                QTYPE_SPEECHACE_FROM_MOODLEKEY => $scoringinfo_obj->getMoodleKey()
            );
        }
        $qo->scoringinfo = $scoringinfo;

		$qo->showanswer = $format->getpath($q,
            array('#', 'showanswer', 0, '#'), $this->show_answer_default());
		$qo->showresult = $format->getpath($q,
            array('#', 'showresult', 0, '#'), $this->show_result_default());
        $qo->dialect = $format->getpath($q,
            array('#', 'dialect', 0, '#'), $this->show_dialect_default());
        $qo->showexpertaudio = $format->getpath($q,
            array('#', 'showexpertaudio', 0, '#'), $this->show_expert_audio_default());


        return $qo;
    }

    public function show_answer_options() {
        return array(
            QTYPE_SPEECHACE_SHOW_ANSWER_ALWAYS => get_string('showanswer_always', 'qtype_speechace'),
            QTYPE_SPEECHACE_SHOW_ANSWER_RESULT => get_string('showanswer_result', 'qtype_speechace'),
        );
    }

    public function show_answer_default() {
        return QTYPE_SPEECHACE_SHOW_ANSWER_ALWAYS;
    }

    public function show_result_options() {
        return array(
            QTYPE_SPEECHACE_SHOW_RESULT_IMMEDIATELY => get_string('showresult_immediately', 'qtype_speechace'),
            QTYPE_SPEECHACE_SHOW_RESULT_REVIEW => get_string('showresult_review', 'qtype_speechace')
        );
    }

    public function show_result_default() {
        return QTYPE_SPEECHACE_SHOW_RESULT_IMMEDIATELY;
    }

    public function show_dialect_options() {

     global $qtype_speechace_dialect_options;
     return $qtype_speechace_dialect_options;
    }

    public function show_dialect_default() {
        $config_value = get_config('qtype_speechace','dialect');
        return($config_value);
    }

    public function show_expert_audio_default(){
        return QTYPE_SPEECHACE_SHOW_EXPERT_AUDIO_ALWAYS;
    }

    public function show_expert_audio_options(){
        global $qtype_speechace_show_expert_audio_options;
        return $qtype_speechace_show_expert_audio_options;
    }
}
