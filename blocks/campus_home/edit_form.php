<?php

class block_campus_home_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;

        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // === CONTENU TEXTE ===

        $mform->addElement('text', 'config_title',
            get_string('title', 'block_campus_home'));
        $mform->setType('config_title', PARAM_RAW);

        $mform->addElement('textarea', 'config_body',
            get_string('body', 'block_campus_home'),
            'wrap="virtual" rows="6" cols="50"');
        $mform->setType('config_body', PARAM_RAW);

        // === CLASSE CSS (clé du système) ===

        $mform->addElement('text', 'config_css_class',
            get_string('css_class', 'block_campus_home'));
        $mform->setType('config_css_class', PARAM_ALPHANUMEXT);
        $mform->setDefault('config_css_class', 'bloc1');

        $mform->addElement('static', 'config_css_help',
            '',
            get_string('css_class_help', 'block_campus_home'));

        // === CTA / BOUTONS ===

        $mform->addElement('header', 'config_cta_header',
            get_string('cta_header', 'block_campus_home'));

        // Left CTA
        $mform->addElement('text', 'config_left_button_text',
            get_string('left_button_text', 'block_campus_home'));
        $mform->setType('config_left_button_text', PARAM_RAW);

        $mform->addElement('text', 'config_left_button_link',
            get_string('left_button_link', 'block_campus_home'));
        $mform->setType('config_left_button_link', PARAM_RAW);

        // Right CTA
        $mform->addElement('text', 'config_right_button_text',
            get_string('right_button_text', 'block_campus_home'));
        $mform->setType('config_right_button_text', PARAM_RAW);

        $mform->addElement('text', 'config_right_button_link',
            get_string('right_button_link', 'block_campus_home'));
        $mform->setType('config_right_button_link', PARAM_RAW);
    

        // === IMAGES ===

        $mform->addElement('header', 'config_image_heading',
            get_string('config_image_heading', 'theme_edly'));

        $mform->addElement('text', 'config_bg_image',
            get_string('bg_image', 'block_campus_home'));
        $mform->setType('config_bg_image', PARAM_TEXT);

        $mform->addElement('text', 'config_bg_image_mob',
            get_string('bg_image_mob', 'block_campus_home'));
        $mform->setType('config_bg_image_mob', PARAM_TEXT);
    }
}
