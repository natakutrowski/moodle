<?php

class block_edly_contact_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // Subtitle
        $mform->addElement('text', 'config_subtitle', get_string('config_subtitle', 'theme_edly'));
        $mform->setDefault('config_subtitle', 'Contact Us');
        $mform->setType('config_subtitle', PARAM_RAW);

        // Title
        $mform->addElement('text', 'config_title', get_string('config_title', 'theme_edly'));
        $mform->setDefault('config_title', 'Ready to Get Started? Fill up the form and our team <span>contact you</span>');
        $mform->setType('config_title', PARAM_RAW);

        $mform->addElement('textarea', 'config_contact_from_code', get_string('config_contact_from_code', 'theme_edly'), 'wrap="virtual" rows="10" cols="50"');

        $mform->addElement('static', 'config_cotact_doc', '<b><a style="color: var(--main-color)" href="https://moodle.org/plugins/local_contact" target="_blank">Please make sure Contact Form plugin is installed.</a></b>'); 
    }
}
