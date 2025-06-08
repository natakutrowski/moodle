<?php

class block_edly_success_overview_area_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;
        $edlyFontList = include($CFG->dirroot . '/theme/edly/inc/font_handler/edly_font_select.php');

        $itemNumber = 3;
        if(isset($this->block->config->itemNumber)){
            $itemNumber = $this->block->config->itemNumber;
        }

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // Class
        $mform->addElement('text', 'config_class', get_string('config_class', 'theme_edly'));
        $mform->setDefault('config_class', 'success-overview-area pb-100');
        $mform->setType('config_class', PARAM_RAW);

        $sliderrange = array(
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

        $mform->addElement('select', 'config_itemNumber', get_string('config_items', 'theme_edly'), $sliderrange);
        $mform->setDefault('config_itemNumber', 3);

        for($i = 1; $i <= $itemNumber; $i++) {
            $mform->addElement('header', 'config_edly_item' . $i , get_string('config_item', 'theme_edly') . $i);

            // Title
            $mform->addElement('text', 'config_title' . $i, get_string('config_title', 'theme_edly', $i));
            $mform->setDefault('config_title' . $i, 'Inspirational Stories Are Less About Success');
            $mform->setType('config_title' . $i, PARAM_TEXT);

            // Content
            $mform->addElement('textarea', 'config_content' . $i, get_string('config_content', 'theme_edly', $i));
            $mform->setDefault('config_content' . $i, '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Natoque interdum venenatis, volutpat in at volutpat, ut enim. Nisl mauris massa adipiscing ac mauris, habitant ullamcorper. Tempus quis tortor lectus consectetur at suspendisse.</p>');
            $mform->setType('config_content' . $i, PARAM_RAW);

            
            $select = $mform->addElement('select', 'config_icon' . $i, get_string('config_icon', 'theme_edly'), $edlyFontList, array('class'=>'edly_icon_class'));
        }
    }
}
