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
 * Defines the editing form for the speechace question type.
 *
 * @package    qtype
 * @subpackage speechace
 * @copyright  2017 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once('HTML/QuickForm/element.php');
require_once($CFG->dirroot.'/lib/setuplib.php');
require_once($CFG->dirroot.'/lib/sessionlib.php');
require_once('speechacelib.php');
require_once('constant.php');

class MoodleQuickForm_qtype_speechace_editor extends HTML_QuickForm_element {
    /** @var string html for help button, if empty then no help will icon will be dispalyed. */
    public $_helpbutton = '';

    private $_options = array(
        'required' => false,
        'context' => null,
        'maxbytes' => 0,
        'questionid' => null
    );

    private $_values = array(
        QTYPE_SPEECHACE_FORM_TEXT => null,
        QTYPE_SPEECHACE_FORM_SOURCETYPE => null,
        QTYPE_SPEECHACE_FORM_MOODLEITEMID => null,
        QTYPE_SPEECHACE_FORM_SPEECHACEKEY => null,
        QTYPE_SPEECHACE_FROM_MOODLEKEY => null,
    );

    /**
     * constructor (needed for moodle 3.1+)
     *
     * @param string $elementName (optional) name of the qtype_speechace_editor element
     * @param string $elementLabel (optional) label of the qtype_speechace_editor element
     * @param array $attributes (optional) Either a typical HTML attribute string
     *              or an associative array
     * @param array $options set of options for this element
     */
    public function __construct($elementName=null, $elementLabel=null, $attributes=null, $options=null) {
        global $CFG, $PAGE;

        $options = (array)$options;
        foreach ($options as $name=>$value) {
            if (array_key_exists($name, $this->_options)) {
                $this->_options[$name] = $value;
            }
        }
        if (!empty($options['maxbytes'])) {
            $this->_options['maxbytes'] = get_max_upload_file_size($CFG->maxbytes, $options['maxbytes']);
        }
        if (!$this->_options['context']) {
            if (!empty($PAGE->context->id)) {
                $this->_options['context'] = $PAGE->context;
            } else {
                $this->_options['context'] = context_system::instance();
            }
        }
        if (is_callable('parent::__construct')) {
            parent::__construct($elementName, $elementLabel, $attributes);
        } else {
            parent::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        }
    }

    /**
     * constructor (needed for moodle 2.7 - 3.0)
     *
     * @param string $elementName (optional) name of the qtype_speechace_editor element
     * @param string $elementLabel (optional) label of the qtype_speechace_editor element
     * @param array $attributes (optional) Either a typical HTML attribute string
     *              or an associative array
     * @param array $options set of options for this element
     */
    function MoodleQuickForm_qtype_speechace_editor($elementName=null, $elementLabel=null, $attributes=null, $options=null) {
        self::__construct($elementName, $elementLabel, $attributes, $options);
    }

    function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event) {
            case 'createElement':
                $caller->setType($arg[0] . $this->_valueNameSuffix(QTYPE_SPEECHACE_FORM_TEXT), PARAM_TEXT);
                $caller->setType($arg[0] . $this->_valueNameSuffix(QTYPE_SPEECHACE_FORM_SOURCETYPE), PARAM_ALPHAEXT);
                $caller->setType($arg[0] . $this->_valueNameSuffix(QTYPE_SPEECHACE_FORM_MOODLEITEMID), PARAM_INT);
                $caller->setType($arg[0] . $this->_valueNameSuffix(QTYPE_SPEECHACE_FORM_SPEECHACEKEY), PARAM_TEXT);
                $caller->setType($arg[0] . $this->_valueNameSuffix(QTYPE_SPEECHACE_FROM_MOODLEKEY), PARAM_TEXT);
                break;
        }

        return parent::onQuickFormEvent($event, $arg, $caller);
    }

    function setName($name) {
        $this->updateAttributes(array('name' => $name));
    }

    function getName() {
        return $this->getAttribute('name');
    }

    function setValue($values) {
        $values = (array)$values;
        foreach ($values as $name=>$value) {
            if (array_key_exists($name, $this->_values)) {
                $this->_values[$name] = $value;
            }
        }
    }

    function getValue() {
        return $this->_values;
    }

    function getMaxbytes() {
        return $this->_options['maxbytes'];
    }

    function setMaxbytes($maxbytes) {
        global $CFG;
        $this->_options['maxbytes'] = get_max_upload_file_size($CFG->maxbytes, $maxbytes);
    }

    function isRequired() {
        return (isset($this->options['required']) && $this->_options['required']);
    }

    /**
     * @deprecated since Moodle 2.0
     */
    function setHelpButton($_helpbuttonargs, $function='_helpbutton') {
        throw new coding_exception('setHelpButton() can not be used any more, please see MoodleQuickForm::addHelpButton().');
    }

    /**
     * Returns html for help button.
     *
     * @return string html for help button
     */
    function getHelpButton() {
        return $this->_helpbutton;
    }

    /**
     * Returns type of editor element
     *
     * @return string
     */
    function getElementTemplateType() {
        if ($this->_flagFrozen){
            return 'nodisplay';
        } else {
            return 'default';
        }
    }

    function toHtml() {
        global $PAGE, $CFG;

        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/question/type/speechace/module.js'));
        $PAGE->requires->js(new moodle_url($CFG->wwwroot . QTYPE_SPEECHACE_JS_PATH));

        $id = $this->_attributes['id'];
        if (empty($id)) {
            $id = uniqid('qtype_speechace_editor_');
            $viewId = $id . '_view';
        } else {
            $viewId = 'qtype_speechace_editor_' . $id . '_view';
        }

        $text = $this->_values[QTYPE_SPEECHACE_FORM_TEXT];
        $sourcetype = $this->_values[QTYPE_SPEECHACE_FORM_SOURCETYPE];
        $moodleitemid = $this->_values[QTYPE_SPEECHACE_FORM_MOODLEITEMID];
        $speechacekey = $this->_values[QTYPE_SPEECHACE_FORM_SPEECHACEKEY];
        $moodlekey = $this->_values[QTYPE_SPEECHACE_FROM_MOODLEKEY];

        if (!$this->_flagFrozen) {
            if (empty($moodleitemid)) {
                require_once("$CFG->libdir/filelib.php");
                $this->setValue(array(QTYPE_SPEECHACE_FORM_MOODLEITEMID => file_get_unused_draft_itemid()));
                $moodleitemid = $this->_values[QTYPE_SPEECHACE_FORM_MOODLEITEMID];
            }
        }

        $str = $this->_getTabs();
        $str .= html_writer::start_div('', array('id' => $id, 'class' => 'que speechace speechace-edit', 'style' => 'clear: none;'));

        $str .= html_writer::start_div('', array('id' => $viewId));
        $str .= html_writer::end_div();

        $str .= html_writer::empty_tag(
            'input',
            array(
                'type' => 'hidden',
                'name' => $this->_valueName(QTYPE_SPEECHACE_FORM_TEXT),
                'value' => $text
            )
        );

        $str .= html_writer::empty_tag(
            'input',
            array(
                'type' => 'hidden',
                'name' => $this->_valueName(QTYPE_SPEECHACE_FORM_SOURCETYPE),
                'value' => $sourcetype
            )
        );

        $str .= html_writer::empty_tag(
            'input',
            array(
                'type' => 'hidden',
                'name' => $this->_valueName(QTYPE_SPEECHACE_FORM_MOODLEITEMID),
                'value' => $moodleitemid
            )
        );

        $str .= html_writer::empty_tag(
            'input',
            array(
                'type' => 'hidden',
                'name' => $this->_valueName(QTYPE_SPEECHACE_FORM_SPEECHACEKEY),
                'value' => $speechacekey
            )
        );

        $str .= html_writer::empty_tag(
            'input',
            array(
                'type' => 'hidden',
                'name' => $this->_valueName(QTYPE_SPEECHACE_FROM_MOODLEKEY),
                'value' => $moodlekey
            )
        );

        $str .= html_writer::end_div();

        $viewOpts = array();
        $viewOpts[QTYPE_SPEECHACE_FORM_TEXT] = $this->_valueName(QTYPE_SPEECHACE_FORM_TEXT);
        $viewOpts[QTYPE_SPEECHACE_FORM_SOURCETYPE] = $this->_valueName(QTYPE_SPEECHACE_FORM_SOURCETYPE);
        $viewOpts[QTYPE_SPEECHACE_FORM_MOODLEITEMID] = $this->_valueName(QTYPE_SPEECHACE_FORM_MOODLEITEMID);
        $viewOpts[QTYPE_SPEECHACE_FORM_SPEECHACEKEY] = $this->_valueName(QTYPE_SPEECHACE_FORM_SPEECHACEKEY);
        $viewOpts[QTYPE_SPEECHACE_FROM_MOODLEKEY] = $this->_valueName(QTYPE_SPEECHACE_FROM_MOODLEKEY);
        $viewOpts['contextid'] = $this->_options['context']->id;
        $viewOpts['sesskey'] = sesskey();
        $viewOpts['viewId'] = $viewId;
        $viewOpts['questionid'] = $this->_options['questionid'];
        $viewOpts['baseurl'] = $CFG->wwwroot . '/question/type/speechace/jsapi.php';
        $viewOpts['moodleitemid'] = $moodleitemid;

        $viewOpts['workerPath'] = $CFG->wwwroot . '/question/type/speechace/js/recorderWorker.js';
        $viewOpts['swfPath'] = $CFG->wwwroot . QTYPE_SPEECHACE_SWF_PATH;
        $viewOpts['preferSwf'] = QTYPE_SPEECHACE_PREFER_SWF;
        $viewOpts['fillColor'] = '#fff';
        $viewOpts['color'] = '#1ccff6';
        $viewOpts['volumeFillColor'] = '#bbb';
        $viewOpts['readOnly'] = $this->_flagFrozen;
        $viewOpts['id'] = $id;
        $viewOpts['dialectElementId'] = 'id_dialect';
        
        
		$PAGE->requires->string_for_js('moodlejs_Say', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_Review', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_FetchingAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_AudioSaved', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UnableSaveAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_TryAgain', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_RecordYourAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_UseSpeechAceAudio', 'qtype_speechace');
		$PAGE->requires->string_for_js('moodlejs_PlaceHolder', 'qtype_speechace');        


        $PAGE->requires->js_init_call("M.qtype_speechace.attachMoodleEditViewController", array($viewOpts), false);
        return $str;
    }

    function _valueNameSuffix($propName) {
        return '[' . $propName . ']';
    }

    function _valueName($propName) {
        return $this->_attributes['name'] . $this->_valueNameSuffix($propName);
    }
}

MoodleQuickForm::registerElementType(
    'qtype_speechace_editor',
    "$CFG->dirroot/question/type/speechace/edit_speechace_form.php",
    'MoodleQuickForm_qtype_speechace_editor');


/**
 * SpeechAce question type editing form.
 *
 * @copyright  2014 SpeechAce Question 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_speechace_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        $qtype = question_bank::get_qtype('speechace');


        $mform->addElement(
		    'qtype_speechace_editor',
            'scoringinfo',
            get_string('scoringinfo', 'qtype_speechace'),
            null,
            array(
                'context' => $this->context,
                'required' => true,
                'questionid' => isset($this->question->id) ? (int)$this->question->id : null));
        $mform->addRule('scoringinfo', null, 'required', null);
        $mform->addHelpButton('scoringinfo', 'scoringinfo', 'qtype_speechace');
		$mform->insertElementBefore($mform->removeElement('scoringinfo', false), 'defaultmark');

        $mform->addElement(
            'select',
            'dialect',
            get_string('dialect','qtype_speechace'),
            $qtype->show_dialect_options());
        $mform->setDefault('dialect', $qtype->show_dialect_default());
        $mform->insertElementBefore($mform->removeElement('dialect',false),'defaultmark');


        $mform->addElement(
		    'select',
            'showanswer',
            get_string('showanswer', 'qtype_speechace'),
            $qtype->show_answer_options());
		$mform->setDefault('showanswer', $qtype->show_answer_default());
		$mform->insertElementBefore($mform->removeElement('showanswer', false), 'defaultmark');

        $mform->addElement(
		    'select',
            'showresult',
            get_string('showresult', 'qtype_speechace'),
            $qtype->show_result_options());
		$mform->setDefault('showresult', $qtype->show_result_default());
		$mform->insertElementBefore($mform->removeElement('showresult', false), 'defaultmark');

        $mform->addElement(
            'select',
            'showexpertaudio',
            get_string('showexpertaudio', 'qtype_speechace'),
            $qtype->show_expert_audio_options());
        $mform->setDefault('showexpertaudio', $qtype->show_expert_audio_default());
        $mform->insertElementBefore($mform->removeElement('showexpertaudio', false), 'defaultmark');



    }


    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        if (empty($question->options)) {
            return $question;
        }

        $moodleitemid = file_get_submitted_draft_itemid('scoringinfo');
        file_prepare_draft_area($moodleitemid, $this->context->id, 'qtype_speechace', 'scoringinfo',
            !empty($question->id) ? (int) $question->id : null,
            array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => -1));
        $scoringinfo_obj = qtype_speechace_scoringinfo::deserialize($question->options->scoringinfo);
        $question->scoringinfo = array();
        $question->scoringinfo[QTYPE_SPEECHACE_FORM_TEXT] = $scoringinfo_obj->getText();
        $question->scoringinfo[QTYPE_SPEECHACE_FORM_MOODLEITEMID] = $moodleitemid;
        $question->scoringinfo[QTYPE_SPEECHACE_FORM_SPEECHACEKEY] = $scoringinfo_obj->getSpeechaceKey();
        $question->scoringinfo[QTYPE_SPEECHACE_FORM_SOURCETYPE] = $scoringinfo_obj->getSourceType();
        $question->scoringinfo[QTYPE_SPEECHACE_FROM_MOODLEKEY] = $scoringinfo_obj->getMoodleKey();

        $question->showanswer = $question->options->showanswer;
        $question->showresult = $question->options->showresult;
        $question->dialect = $question->options->dialect;
        $question->showexpertaudio = $question->options->showexpertaudio;

        return $question;
    }

    public function qtype() {
        return 'speechace';
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        global $qtype_speechace_dialect_options;
        if(!array_key_exists('dialect',$fromform)) {
            $errors['dialect'] = get_string('dialectinfomissing', 'qtype_speechace');
        } elseif(!array_key_exists($fromform['dialect'],$qtype_speechace_dialect_options)){
            $errors['dialect'] = get_string('dialectinfoinvalid','qtype_speechace');
        }

        global $qtype_speechace_show_expert_audio_options;
        if(!array_key_exists('showexpertaudio',$fromform)){
            $errors['showexpertaudio'] = get_string('showexpertaudio_missing','qtype_speechace');
        } elseif  (!array_key_exists($fromform['showexpertaudio'],$qtype_speechace_show_expert_audio_options)){
            $errors['showexpertaudio'] = get_string('showexpertaudio_infoinvalid','qtype_speechace');
        }


        if (!array_key_exists('scoringinfo', $fromform)) {
            $errors['scoringinfo'] = get_string('scoringinfomissing', 'qtype_speechace');
        } elseif (!array_key_exists(QTYPE_SPEECHACE_FORM_TEXT, $fromform['scoringinfo'])) {
            $errors['scoringinfo'] = get_string('scoringinfotextmissing', 'qtype_speechace');
        } elseif(!array_key_exists('dialect',$errors) && !array_key_exists('showexpertaudio',$errors)) {
            $dialect = $fromform['dialect'];
            $scoringinfo_obj = null;
            $text = trim($fromform['scoringinfo'][QTYPE_SPEECHACE_FORM_TEXT]);
            if (!strlen($text)) {
                $errors['scoringinfo'] = get_string('scoringinfotextmissing', 'qtype_speechace');
            } else {
                global $DB;
                $needvalidation = true;
                $options = $DB->get_record('qtype_speechace_opts', array('questionid' => $fromform['id']));
                if ($options && object_property_exists($options, 'scoringinfo')) {
                    $scoringinfo_obj = qtype_speechace_scoringinfo::deserialize($options->scoringinfo);
                    if ($scoringinfo_obj->getText() === $text) {
                        $needvalidation = false;
                    }
                }
                if ($needvalidation) {
                    $result = qtype_speechace_validate_scoringinfo_text($text,$dialect);
                    $errmsg = get_string('scoringinfotextunknownerror', 'qtype_speechace');
                    if (object_property_exists($result, 'status') && $result->status === 'success') {
                        $errmsg = null;
                    } else {
                        qtype_speechace_rewrite_json_error($result);
                        $defaultmsg = true;
                        if (object_property_exists($result, 'short_message')) {
                            if ($result->short_message === 'error_unknown_words') {
                                $errmsg = get_string('scoringinfotextoutofvocab', 'qtype_speechace', $result);
                                $defaultmsg = false;
                            } else if ($result->short_message === 'error_text_too_long') {
                                $errmsg = get_string('scoringinfotexttoolong', 'qtype_speechace', $result);
                                $defaultmsg = false;
                            }
                        }
                        if ($defaultmsg &&
                            object_property_exists($result, 'detail_message') &&
                            strlen($result->detail_message)) {
                            $errmsg = $result->detail_message;
                        }
                    }
                    if ($errmsg) {
                        $errors['scoringinfo'] = $errmsg;
                    }
                }
            }

            $source_type = 'speechace_key';
            if (!array_key_exists('scoringinfo', $errors)) {
                if (array_key_exists(QTYPE_SPEECHACE_FORM_SOURCETYPE, $fromform['scoringinfo']) &&
                        strlen($fromform['scoringinfo'][QTYPE_SPEECHACE_FORM_SOURCETYPE])) {

                    $source_type = $fromform['scoringinfo'][QTYPE_SPEECHACE_FORM_SOURCETYPE];
                    if ($source_type !== 'speechace_key' && $source_type !== 'moodle_key') {
                        $errors['scoringinfo'] = get_string('scoringinfosourcetypeinvalid', 'qtype_speechace');
                    }
                }
            }

            if ((!array_key_exists('scoringinfo', $errors)) &&
                array_key_exists(QTYPE_SPEECHACE_FORM_MOODLEITEMID, $fromform['scoringinfo']) &&
                $fromform['scoringinfo'][QTYPE_SPEECHACE_FORM_MOODLEITEMID]) {

                $moodle_key = '';
                if ($source_type === 'moodle_key') {
                    if (array_key_exists(QTYPE_SPEECHACE_FROM_MOODLEKEY, $fromform['scoringinfo'])) {
                        $moodle_key = trim($fromform['scoringinfo'][QTYPE_SPEECHACE_FROM_MOODLEKEY]);
                    }
                }
                if (strlen($moodle_key)) {
                    global $USER;
                    $fs = get_file_storage();
                    $contextid = context_user::instance($USER->id)->id;
                    $itemid = $fromform['scoringinfo'][QTYPE_SPEECHACE_FORM_MOODLEITEMID];
                    if ($fs->file_exists($contextid, 'user', 'draft', $itemid, '/', $moodle_key)) {
                        $stored_file = $fs->get_file($contextid, 'user', 'draft', $itemid, '/', $moodle_key);
                        $speechace_results = qtype_speechace_load_score_results($stored_file);
                        if (empty($speechace_results)) {
                            $new_scoringinfo_obj = qtype_speechace_scoringinfo::createFromFormData($fromform['scoringinfo']);
                            list($succeeded, $speechace_results) = qtype_speechace_grade_audio(
                                $new_scoringinfo_obj->getInner(),
                                $stored_file,
                                $dialect);
                            if (!$succeeded ||
                                !object_property_exists($speechace_results, 'status') ||
                                ($speechace_results->status != 'success')) {

                                qtype_speechace_rewrite_json_error($speechace_results);

                                $errmsg = get_string('scoringinfomoodlekeygenericerror', 'qtype_speechace');
                                if ($speechace_results && object_property_exists($speechace_results, 'detail_message')) {
                                    $errmsg = get_string('scoringinfomoodlekeydetailmessage', 'qtype_speechace', $speechace_results);
                                }
                                $errors['scoringinfo'] = $errmsg;
                            } else {
                                qtype_speechace_save_score_results($speechace_results, $stored_file);
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }
}
