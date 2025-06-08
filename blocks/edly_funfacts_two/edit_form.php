<?php

class block_edly_funfacts_two_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;
        $edlyFontList = include($CFG->dirroot . '/theme/edly/inc/font_handler/edly_font_select.php');


        $funfactsnumber = 4;
        if(isset($this->block->config->funfactsnumber)){
            $funfactsnumber = $this->block->config->funfactsnumber;
        }

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // Class
        $mform->addElement('text', 'config_class', get_string('config_class', 'theme_edly'));
        $mform->setDefault('config_class', 'funfacts-area pt-100 pb-75');
        $mform->setType('config_class', PARAM_RAW);

        $funfactsrange = array(
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

        $mform->addElement('select', 'config_funfactsnumber', get_string('config_items', 'theme_edly'), $funfactsrange);
        $mform->setDefault('config_funfactsnumber', 4);

        for($i = 1; $i <= $funfactsnumber; $i++) {
            $mform->addElement('header', 'config_edly_item' . $i , get_string('config_item', 'theme_edly') . $i);

            $mform->addElement('text', 'config_funfacts_title' . $i, get_string('config_title', 'theme_edly', $i));
            $mform->setDefault('config_funfacts_title' . $i, 'Student enrolled');
            $mform->setType('config_funfacts_title' . $i, PARAM_TEXT);

            $mform->addElement('text', 'config_funfacts_number' . $i, get_string('config_number', 'theme_edly', $i));
            $mform->setDefault('config_funfacts_number' . $i, '56892');
            $mform->setType('config_funfacts_number' . $i, PARAM_TEXT);

            $mform->addElement('text', 'config_funfacts_prefix' . $i, get_string('config_number_prefix', 'theme_edly', $i));
            $mform->setDefault('config_funfacts_prefix' . $i, '');
            $mform->setType('config_funfacts_prefix' . $i, PARAM_TEXT);

            $select = $mform->addElement('select', 'config_icon' . $i, get_string('config_icon', 'theme_edly'), $edlyFontList, array('class'=>'edly_icon_class'));

        }
    }
}
