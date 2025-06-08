<?php
require_once($CFG->dirroot . '/theme/edly/inc/course_handler/edly_course_handler.php');

class block_edly_video_area_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;
        $edlyFontList = include($CFG->dirroot . '/theme/edly/inc/font_handler/edly_font_select.php');

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // Title
        $mform->addElement('text', 'config_title', get_string('config_title', 'theme_edly'));
        $mform->setDefault('config_title', 'A world class education for anyone, anywhere. Wonderful <span>choice</span>');
        $mform->setType('config_title', PARAM_RAW);

        // Section Image
        $mform->addElement('text', 'config_img', 'Section Image URL');
        $mform->setType('config_img', PARAM_TEXT);

        // YouTube Video Link
        $mform->addElement('text', 'config_video', 'YouTube Video Link');
        $mform->setDefault('config_video', 'https://www.youtube.com/watch?v=ODfy2YIKS1M');
        $mform->setType('config_video', PARAM_RAW);
    }
}
