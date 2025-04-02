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
 * Admin settings for speechace question type plugin
 *
 * @package    qtype
 * @subpackage speechace
 * @copyright  2017 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once('constant.php');
require_once('speechacelib.php');

if ($ADMIN->fulltree) {

    // For some reason, Moodle doesn't use require_once or include_once to load
    // this file. Let's protect ourselves with this check.
    if (!class_exists('qtype_speechace_admin_setting_productkey')) {
        /**
         * SpeechAce text area for product key (adapting admin_setting_configtextarea
         * from Moodle)
         *
         * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */
        class qtype_speechace_admin_setting_productkey extends admin_setting_configtext
        {
            private $rows;

            public function __construct($name, $visiblename, $description, $defaultsetting, $paramtype = PARAM_RAW, $rows = '8')
            {
                $this->rows = $rows;
                parent::__construct($name, $visiblename, $description, $defaultsetting, $paramtype);
            }

            public function output_html($data, $query = '')
            {
                $default = $this->get_defaultsetting();

                $defaultinfo = $default;
                if (!is_null($default) and $default !== '') {
                    $defaultinfo = "\n" . $default;
                }

                return format_admin_setting(
                    $this,
                    $this->visiblename,
                    '<div class="form-textarea">' .
                    '<textarea rows="' . $this->rows . '" id="' . $this->get_id() . '" name="' . $this->get_full_name() . '" style="width: 100%; box-sizing: border-box;">' .
                    s($data) .
                    '</textarea>' .
                    '</div>',
                    $this->description,
                    true,
                    '',
                    $defaultinfo,
                    $query);
            }

            public function validate($data)
            {
                $result = parent::validate($data);
                if ($result !== true) {
                    return $result;
                }

                // Make sure that the data is not empty.
                $trimmeddata = trim($data);
                if (empty($trimmeddata)) {
                    return get_string('productkeyempty', 'qtype_speechace');
                }

                // Make sure that the data is url encoded
                $decoded_value = rawurldecode($trimmeddata);
                $revert = array('%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')');
                $encoded_value = strtr(rawurlencode($decoded_value), $revert);
                if ($encoded_value !== $trimmeddata) {
                    return get_string('productkeyinvalid', 'qtype_speechace');
                }

                return true;
            }
        }

        class qtype_speechace_admin_setting_scoremessages extends admin_setting
        {

            /** @var mixed int means PARAM_XXX type, string is a allowed format in regex */
            public $paramtype;

            public function __construct($name, $visiblename, $description, $defaultsetting, $paramtype)
            {
                $this->paramtype = $paramtype;

                parent::__construct($name, $visiblename, $description, $defaultsetting);

            }

            public function get_setting()
            {
                return qtype_speechace_deserialize_score_message_setting($this->config_read($this->name));
            }

            public function write_setting($data)
            {
                if ($this->paramtype === PARAM_INT and $data === '') {
                    // do not complain if '' used instead of 0
                    $data = 0;
                }

                $validated = $this->validate($data);
                if ($validated !== true) {
                    return $validated;
                }
                $data_string = json_encode($data);
                return ($this->config_write($this->name, $data_string) ? '' : get_string('errorsetting', 'admin'));

            }

            public function validate($data) {
                //TODO need to Validate the user messages textt
                return true;

            }


            public function output_html($data, $query='') {
                $default = $this->get_defaultsetting();
                $scoreMessagesData = $data;

                //it cannot be completely null unless no data was saved/submitted by user at all.
                // select1 & select2 will always have values.
                /*if(!$scoreMessagesData)
                {*/
                    $textOne= $default[QTYPE_SPEECHACE_SCOREMESSAGES_TEXTONE];
                    $textTwo= $default[QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTWO];
                    $textThree= $default[QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTHREE];
                    $selectOne= $default[QTYPE_SPEECHACE_SCOREMESSAGES_SELECTONE];
                    $selectTwo= $default[QTYPE_SPEECHACE_SCOREMESSAGES_SELECTTWO];

                /*}else {
                    $textOne = $scoreMessagesData[QTYPE_SPEECHACE_SCOREMESSAGES_TEXTONE];
                    $textTwo = $scoreMessagesData[QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTWO];
                    $textThree = $scoreMessagesData[QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTHREE];
                    $selectOne = $scoreMessagesData[QTYPE_SPEECHACE_SCOREMESSAGES_SELECTONE];
                    $selectTwo = $scoreMessagesData[QTYPE_SPEECHACE_SCOREMESSAGES_SELECTTWO];
                }*/

                global $PAGE, $OUTPUT,$CFG;
                $ScoreMessagesContainerId = 'qtype_speechace_scoremessages_setting_id';
                $ElementId = $this->get_id();
                $ElementName = $this->get_full_name();

                $scoremessagesinfo = [];
                $scoremessagesinfo['elementId']= $this->get_id();
                $scoremessagesinfo['parentElementId'] = $ScoreMessagesContainerId;

                // NOTE: the key for $scoremessagesinfo must be in sync with the code
                //       in moodle.js attachScoreMessagesViewController.
                $scoremessagesinfo['textOne'] = $this->_valueName(QTYPE_SPEECHACE_SCOREMESSAGES_TEXTONE);
                $scoremessagesinfo['textTwo'] = $this->_valueName(QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTWO);
                $scoremessagesinfo['textThree'] = $this->_valueName(QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTHREE);
                $scoremessagesinfo['selectOne'] = $this->_valueName(QTYPE_SPEECHACE_SCOREMESSAGES_SELECTONE);
                $scoremessagesinfo['selectTwo'] = $this->_valueName(QTYPE_SPEECHACE_SCOREMESSAGES_SELECTTWO);

                $scoremessagesinfo[QTYPE_SPEECHACE_SCOREMESSAGES_RESET_VALUE_TEXTONE] = $default[QTYPE_SPEECHACE_SCOREMESSAGES_TEXTONE];
                $scoremessagesinfo[QTYPE_SPEECHACE_SCOREMESSAGES_RESET_VALUE_TEXTTWO] = $default[QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTWO];
                $scoremessagesinfo[QTYPE_SPEECHACE_SCOREMESSAGES_RESET_VALUE_TEXTTHREE] = $default[QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTHREE];
                $scoremessagesinfo[QTYPE_SPEECHACE_SCOREMESSAGES_RESET_VALUE_SELECTONE] = $default[QTYPE_SPEECHACE_SCOREMESSAGES_SELECTONE];
                $scoremessagesinfo[QTYPE_SPEECHACE_SCOREMESSAGES_RESET_VALUE_SELECTTWO] = $default[QTYPE_SPEECHACE_SCOREMESSAGES_SELECTTWO];
                
				$PAGE->requires->string_for_js('moodlejs_MessageScoreGreaterThan', 'qtype_speechace');
				$PAGE->requires->string_for_js('moodlejs_MessageButLessThan', 'qtype_speechace');
				$PAGE->requires->string_for_js('moodlejs_MessageScoreLessThan', 'qtype_speechace');
				$PAGE->requires->string_for_js('moodlejs_MessageReset', 'qtype_speechace');

                $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/question/type/speechace/module.js'));
                $PAGE->requires->js(new moodle_url($CFG->wwwroot . QTYPE_SPEECHACE_JS_PATH));
                $PAGE->requires->js_init_call('M.qtype_speechace.attachScoreMessagesViewController', array($scoremessagesinfo));

                $str  = html_writer::start_div('', array('id' => $ScoreMessagesContainerId, 'class' => 'que speechace speechace-edit', 'style' => 'clear: none;'));
                $str .= html_writer::start_div('', array('id' => $ElementId,'name' =>$ElementName));
                $str .= html_writer::end_div();

                $str .= html_writer::empty_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => $this->_valueName(QTYPE_SPEECHACE_SCOREMESSAGES_TEXTONE),
                        'value' => $textOne
                    )
                );

                $str .= html_writer::empty_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => $this->_valueName(QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTWO),
                        'value' => $textTwo
                    )
                );

                $str .= html_writer::empty_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => $this->_valueName(QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTHREE),
                        'value' => $textThree
                    )
                );

                $str .= html_writer::empty_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => $this->_valueName(QTYPE_SPEECHACE_SCOREMESSAGES_SELECTONE),
                        'value' => (int)$selectOne
                    )
                );

                $str .= html_writer::empty_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => $this->_valueName(QTYPE_SPEECHACE_SCOREMESSAGES_SELECTTWO),
                        'value' => (int)$selectTwo
                    )
                );

                $str .= html_writer::end_div();

                return format_admin_setting(
                    $this,
                    $this->visiblename,
                    $str,
                    $this->description,
                    true,
                    '',
                    get_string('scoremessages_defaultinfo', 'qtype_speechace'),
                    $query);
            }

            function _valueNameSuffix($propName) {
                return '[' . $propName . ']';
            }

            function _valueName($propName) {
                return $this->get_full_name() . $this->_valueNameSuffix($propName);
            }
        }
    }


    $settings->add(new qtype_speechace_admin_setting_productkey(
        'qtype_speechace/productkey',
        get_string('productkey', 'qtype_speechace'),
        get_string('productkey_description', 'qtype_speechace'),
        '',
        PARAM_TEXT
    ));


    global $qtype_speechace_dialect_options;
    $settings->add(new admin_setting_configselect(
        'qtype_speechace/dialect',
        get_string('dialect_default', 'qtype_speechace'),
        get_string('dialect_description', 'qtype_speechace'),
        get_string('dialect_fr_french', 'qtype_speechace'),
        $qtype_speechace_dialect_options

    ));


    //Descriptions are not added since we do not want it to appear on the UI anyway.
    global $qtype_speechace_numericscore_options;
    $settings->add(new admin_setting_configselect(
        'qtype_speechace/numericscore',
        get_string('numericscore', 'qtype_speechace'),
        get_string('numericscore_descritptivename', 'qtype_speechace'),
        get_string('numericscore_default', 'qtype_speechace'),
        $qtype_speechace_numericscore_options
    ));


    global $qtype_speechace_scoremessages_defaults;
    $settings->add(new qtype_speechace_admin_setting_scoremessages(
        'qtype_speechace/scoremessages',
        get_string('scoremessages', 'qtype_speechace'),
        "",
        $qtype_speechace_scoremessages_defaults,
        PARAM_RAW
    ));

}

