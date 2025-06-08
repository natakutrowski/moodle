<?php
require_once($CFG->dirroot . '/theme/edly/inc/course_handler/edly_course_handler.php');

class block_edly_about_area_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;
        $edlyFontList = include($CFG->dirroot . '/theme/edly/inc/font_handler/edly_font_select.php');

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // Top Title
        $mform->addElement('text', 'config_top_title', get_string('config_top_title', 'theme_edly'));
        $mform->setDefault('config_top_title', 'Over 5500+ courses available');
        $mform->setType('config_top_title', PARAM_RAW);

        // Title
        $mform->addElement('text', 'config_title', get_string('config_title', 'theme_edly'));
        $mform->setDefault('config_title', 'Affordable online <span>courses</span> &amp; learning opportunities');
        $mform->setType('config_title', PARAM_RAW);

        // Content
        $mform->addElement('textarea', 'config_content', 'Bottom Title');
        $mform->setDefault('config_content', 'Break into a new field like information technology o data science. No prior experience necessary to get started. Break a new field like information technology.');
        $mform->setType('config_content', PARAM_RAW);

        // Image URL
        $mform->addElement('static', 'config_image_doc', '<b><a style="color: var(--main-color)" href="https://docs.hibootstrap.com/docs/edly-moodle-theme-documentation/faqs/how-to-get-the-image-url/" target="_blank">Doc link: How to make Image URL?</a></b>'); 

        // Shape Image 1
        $mform->addElement('text', 'config_shape_img', 'Shape Image 1 URL');
        $mform->setType('config_shape_img', PARAM_TEXT);

        // Shape Image 2
        $mform->addElement('text', 'config_shape_img2', 'Shape Image 2 URL');
        $mform->setType('config_shape_img2', PARAM_TEXT);

        // Shape Image 3
        $mform->addElement('text', 'config_shape_img3', 'Shape Image 3 URL');
        $mform->setType('config_shape_img3', PARAM_TEXT);

        $items = 2;
        if(isset($this->block->config->items)){
            $items = $this->block->config->items;
        }

        $items_range = array(
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
            5 => '5',
            6 => '6',
            7 => '7',
            8 => '8',
            9 => '9',
            10 => '10',
            11 => '11',
            12 => '12',
            13 => '13',
            14 => '14',
            15 => '15',
            16 => '16',
            17 => '17',
            18 => '18',
            19 => '19',
            20 => '20',
            21 => '21',
            22 => '22',
            23 => '23',
            24 => '24',
            25 => '25',
            26 => '26',
            27 => '27',
            28 => '28',
            29 => '29',
            30 => '30',
        );
        $items_max = 30;

        $mform->addElement('select', 'config_items', get_string('config_items', 'theme_edly'), $items_range);
        $mform->setDefault('config_items', 2);

        for($i = 1; $i <= $items; $i++) {
            $mform->addElement('header', 'config_edly_item' . $i , get_string('config_item', 'theme_edly') .' '. $i);

            $select = $mform->addElement('select', 'config_icon' . $i, 'Icon' . $i, $edlyFontList, array('class'=>'edly_icon_class'));
            $select->setSelected('ri-settings-5-line');

            // Title
            $mform->addElement('text', 'config_features_title' . $i, get_string('config_title', 'theme_edly', $i));
            $mform->setDefault('config_features_title' . $i, 'Learn the essential skills');
            $mform->setType('config_features_title' . $i, PARAM_TEXT);

            // Content
            $mform->addElement('textarea', 'config_features_content' . $i, 'Content '.$i );
            $mform->setDefault('config_features_content' . $i, 'Break into a new field like format technology or data science.');
            $mform->setType('config_features_content' . $i, PARAM_TEXT);

        }
    }
}
