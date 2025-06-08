<?php
require_once($CFG->dirroot . '/theme/edly/inc/course_handler/edly_course_handler.php');

class block_edly_single_feedback_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;
        $edlyFontList = include($CFG->dirroot . '/theme/edly/inc/font_handler/edly_font_select.php');

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // Title
        $mform->addElement('text', 'config_title', get_string('config_title', 'theme_edly'));
        $mform->setDefault('config_title', 'Thomson Martin');
        $mform->setType('config_title', PARAM_RAW);

        // Sub Title
        $mform->addElement('text', 'config_subtitle', 'Sub Title');
        $mform->setDefault('config_subtitle', 'Designer of MTX');
        $mform->setType('config_subtitle', PARAM_RAW);

        // Content
        $mform->addElement('text', 'config_content', 'Content');
        $mform->setDefault('config_content', 'Thomson Martin');
        $mform->setType('config_content', PARAM_RAW);

        // Section Image
        $mform->addElement('text', 'config_img', 'Section Image URL');
        $mform->setType('config_img', PARAM_TEXT);

        // Shape Image
        $mform->addElement('text', 'config_shape_img', 'Section Shape Image URL');
        $mform->setType('config_shape_img', PARAM_TEXT);
    }
}
