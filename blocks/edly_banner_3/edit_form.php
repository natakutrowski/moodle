<?php

class block_edly_banner_3_edit_form extends block_edit_form {

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

        // Left Button Text
        $mform->addElement('text', 'config_button_text', 'Left ' . get_string('config_button_text', 'theme_edly'));
        $mform->setDefault('config_button_text', 'Sign Up Now');
        $mform->setType('config_button_text', PARAM_RAW);

        // Left Button Link
        $mform->addElement('text', 'config_button_link', 'Left ' . get_string('config_button_link', 'theme_edly'));
        $mform->setDefault('config_button_link', $CFG->wwwroot . '/login/signup.php');
        $mform->setType('config_button_link', PARAM_RAW);

        // Right Button Text
        $mform->addElement('text', 'config_right_button_text', 'Right ' . get_string('config_button_text', 'theme_edly'));
        $mform->setDefault('config_right_button_text', 'Find Courses');
        $mform->setType('config_right_button_text', PARAM_RAW);

        // Right Button Link
        $mform->addElement('text', 'config_right_button_link', 'Right ' . get_string('config_button_link', 'theme_edly'));
        $mform->setDefault('config_right_button_link', $CFG->wwwroot . '/course');
        $mform->setType('config_right_button_link', PARAM_RAW);

        // Section Image header title according to language file.
        $mform->addElement('header', 'config_image_heading', get_string('config_image_heading', 'theme_edly'));

        $mform->addElement('static', 'config_image_doc', '<b><a style="color: var(--main-color)" href="https://docs.hibootstrap.com/docs/edly-moodle-theme-documentation/faqs/how-to-get-the-image-url/" target="_blank">Doc link: How to make Image URL?</a></b>'); 

        // Banner Image
        $mform->addElement('text', 'config_banner_img', 'Banner Image URL');
        $mform->setType('config_banner_img', PARAM_TEXT);
        $mform->setDefault('config_banner_img', $CFG->wwwroot . '/theme/edly/pix/main-banner/banner-large.webp');

        // Shape
        $mform->addElement('text', 'config_shape', 'Banner Shape Image URL');
        $mform->setType('config_shape', PARAM_TEXT);        
        $mform->setDefault('config_shape', $CFG->wwwroot . '/theme/edly/pix/main-banner/shape4.png');
        
        // Shape
        $mform->addElement('text', 'config_shape_two', 'Banner Shape Image Two URL');
        $mform->setType('config_shape_two', PARAM_TEXT);
        $mform->setDefault('config_shape_two', $CFG->wwwroot . '/theme/edly/pix/main-banner/shape5.png');
    }
}
