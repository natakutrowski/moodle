<?php
require_once($CFG->dirroot . '/theme/edly/inc/course_handler/edly_course_handler.php');

class block_edly_categories_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;
        $edlyFontList = include($CFG->dirroot . '/theme/edly/inc/font_handler/edly_font_select.php');
        $edlyCourseHandler = new edlyCourseHandler();
        $edlyCourseCategories = $edlyCourseHandler->edlyListCategories();

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        $mform->addElement('select', 'config_style', get_string('config_style', 'theme_edly'), array(1 => 'Style 1', 2 => 'Style 2'));
        $mform->setDefault('config_style', 1);

        // Class
        $mform->addElement('text', 'config_class', get_string('config_class', 'theme_edly'));
        $mform->setDefault('config_class', 'categories-area pb-75');
        $mform->setType('config_class', PARAM_RAW);

        // Top Title
        $mform->addElement('text', 'config_top_title', get_string('config_top_title', 'theme_edly'));
        $mform->setDefault('config_top_title', 'Top Categories');
        $mform->setType('config_top_title', PARAM_RAW);

        // Title
        $mform->addElement('text', 'config_title', get_string('config_title', 'theme_edly'));
        $mform->setDefault('config_title', 'Top Categories you want to <span>learn</span>');
        $mform->setType('config_title', PARAM_RAW);

        // Bottom Content
        $mform->addElement('textarea', 'config_bottom_content', 'Bottom Content(work with style 2)');
        $mform->setDefault('config_bottom_content', 'Enjoy the top notch learning methods and achieve next level skills! You are the creator of your own career &amp; we will guide you through that. <a href="#">Browse all categories.</a>');
        $mform->setType('config_bottom_content', PARAM_RAW);

        $items = 8;
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
        $mform->setDefault('config_items', 8);

        for($i = 1; $i <= $items; $i++) {
            $mform->addElement('header', 'config_edly_item' . $i , get_string('config_item', 'theme_edly') .' '. $i);

            $options = array(
                'multiple' => false,
            );
            $mform->addElement('autocomplete', 'config_category' . $i, get_string('category'), $edlyCourseCategories, $options);

            $mform->addElement('text', 'config_img' . $i, 'Category Icon ' . $i);
            if($i <= 8):
                $mform->setDefault('config_img' . $i, $CFG->wwwroot.'/theme/edly/pix/categories/icon'.$i.'.svg');
            else:
                $mform->setDefault('config_img' . $i, $CFG->wwwroot.'/theme/edly/pix/categories/icon1.svg');
            endif;
            $mform->setType('config_img' . $i, PARAM_TEXT);
        }
    }
}
