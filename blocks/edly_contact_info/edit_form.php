<?php

class block_edly_contact_info_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;
        $edlyFontList = include($CFG->dirroot . '/theme/edly/inc/font_handler/edly_font_select.php');

        $features_number = 4;
        if(isset($this->block->config->features_number)){
            $features_number = $this->block->config->features_number;
        }

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // Top Title
        $mform->addElement('text', 'config_top_title', get_string('config_top_title', 'theme_edly'));
        $mform->setType('config_top_title', PARAM_RAW);

        // Title
        $mform->addElement('text', 'config_title', get_string('config_title', 'theme_edly'));
        $mform->setDefault('config_title', 'Get In Touch <span>With Us</span>');
        $mform->setType('config_title', PARAM_RAW);

        $featuresrange = array(
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

        $mform->addElement('select', 'config_features_number', get_string('config_items', 'theme_edly'), $featuresrange);
        $mform->setDefault('config_features_number', 4);

        for($i = 1; $i <= $features_number; $i++) {
            $mform->addElement('header', 'config_edly_item' . $i , get_string('config_item', 'theme_edly') . $i);

            // Icon
            $select = $mform->addElement('select', 'config_icon' . $i, get_string('config_icon', 'theme_edly'), $edlyFontList, array('class'=>'edly_icon_class'));

            // Title
            $mform->addElement('text', 'config_features_title' . $i, get_string('config_title', 'theme_edly', $i));
            $mform->setDefault('config_features_title' . $i, 'Our Address');
            $mform->setType('config_features_title' . $i, PARAM_TEXT);

            // Card Content
            $mform->addElement('text', 'config_features_content' . $i, get_string('config_content', 'theme_edly', $i));
            $mform->setDefault('config_features_content' . $i, '32D, Jenmark road, Franklin. USA');
            $mform->setType('config_features_content' . $i, PARAM_RAW);                
        }
    }
}
