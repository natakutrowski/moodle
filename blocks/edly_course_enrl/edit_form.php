<?php

class block_edly_course_enrl_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;
        $edlyFontList = include($CFG->dirroot . '/theme/edly/inc/font_handler/edly_font_select.php');

        $items = 5;
        if(isset($this->block->config->items)){
            $items = $this->block->config->items;
        }

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_video_link', get_string('config_video', 'theme_edly'));
        $mform->setDefault('config_video_link', 'https://www.youtube.com/watch?v=PWvPbGWVRrU');
        $mform->setType('config_video_link', PARAM_RAW);

        $mform->addElement('text', 'config_title', get_string('config_title', 'theme_edly'));
        $mform->setDefault('config_title', 'Course Preview');
        $mform->setType('config_title', PARAM_RAW);

        $itemsrange = range(0, 12);
        $mform->addElement('select', 'config_items', get_string('config_items', 'block_edly_course_enrl'), $itemsrange);

        $mform->addElement('text', 'config_price', get_string('config_price', 'theme_edly'));
        $mform->setDefault('config_price', 'Price');
        $mform->setType('config_price', PARAM_RAW);

        $mform->addElement('text', 'config_free', 'Free Price Value');
        $mform->setDefault('config_free', 'Free');
        $mform->setType('config_free', PARAM_RAW);

        for($i = 1; $i <= $items; $i++) {
            $mform->addElement('header', 'config_header' . $i , 'Item ' . $i);

            $mform->addElement('text', 'config_item_title' . $i, get_string('config_item_title', 'block_edly_course_enrl', $i));
            $mform->setDefault('config_item_title' .$i , 'Course Level');
            $mform->setType('config_item_title' . $i, PARAM_TEXT);

            $mform->addElement('text', 'config_item_value' . $i, get_string('config_item_value', 'block_edly_course_enrl', $i));
            $mform->setDefault('config_item_value' .$i , 'Course Level');
            $mform->setType('config_item_value' . $i, PARAM_TEXT);

            $select = $mform->addElement('select', 'config_item_icon' . $i, get_string('config_item_icon', 'block_edly_course_enrl'), $edlyFontList, array('class'=>'edly_icon_class'));
             $select->setSelected('bx bx-sort-up');            
        } 
     
    }
}
