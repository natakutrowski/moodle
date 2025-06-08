<?php

class block_edly_banner_1_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;
        $edlyFontList = include($CFG->dirroot . '/theme/edly/inc/font_handler/edly_font_select.php');

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // Title
        $mform->addElement('text', 'config_title', 'Banner Title');
        $mform->setDefault('config_title', '<span>5500+</span> Courses Upgrade your learning Skills and Upgrade Life');
        $mform->setType('config_title', PARAM_RAW);

        // Content
        $mform->addElement('textarea', 'config_body', get_string('config_body', 'theme_edly'), 'wrap="virtual" rows="6" cols="50"');
        $mform->setDefault('config_body', 'Learn 100% online with world class universities and industry experts.');

        // Search Placeholder Text
        $mform->addElement('text', 'config_search_placeholder', get_string('config_search_placeholder', 'block_edly_banner_1'));
        $mform->setDefault('config_search_placeholder', 'Search our 12,500+ courses');
        $mform->setType('config_search_placeholder', PARAM_RAW);

        // Search Button Text
        $mform->addElement('text', 'config_search_btn', get_string('config_search_btn', 'block_edly_banner_1'));
        $mform->setDefault('config_search_btn', 'Search Now');
        $mform->setType('config_search_btn', PARAM_RAW);

        // Search Field Icon
        $select = $mform->addElement('select', 'config_placeholder_icon', get_string('config_placeholder_icon', 'block_edly_banner_1'), $edlyFontList, array('class'=>'edly_icon_class'));
        $select->setSelected('ri-search-line');

        // Bottom Content
        $mform->addElement('text', 'config_bottom_title', get_string('config_bottom_title', 'block_edly_banner_1'));
        $mform->setDefault('config_bottom_title', '522,8910 <span>people are learning on edly today.</span>');
        $mform->setType('config_bottom_title', PARAM_RAW);

        // Section Image header title according to language file.
        $mform->addElement('header', 'config_image_heading', get_string('config_image_heading', 'theme_edly'));

        $mform->addElement('static', 'config_image_doc', '<b><a style="color: var(--main-color)" href="https://docs.hibootstrap.com/docs/edly-moodle-theme-documentation/faqs/how-to-get-the-image-url/" target="_blank">Doc link: How to make Image URL?</a></b>'); 

        // Banner Image
        $mform->addElement('text', 'config_banner_img', 'Banner Image URL');
        $mform->setType('config_banner_img', PARAM_TEXT);

        // Shape Images
        $shape_image_count = 4;
        for($i = 1; $i <= $shape_image_count; $i++) {
            $mform->addElement('text', 'config_shape_img' . $i, 'Banner Shape Image ' . $i);
            $mform->setType('config_shape_img' . $i, PARAM_TEXT);
        }
    }
}
