<?php

class block_edly_newsletter_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));
        
        // Class
        $mform->addElement('text', 'config_class', get_string('config_class', 'theme_edly'));
        $mform->setDefault('config_class', 'ptb-100');
        $mform->setType('config_class', PARAM_RAW);

        // Title
        $mform->addElement('text', 'config_title', get_string('config_title', 'theme_edly'));
        $mform->setDefault('config_title', 'Subscribe to our <span>Newsletter</span>');
        $mform->setType('config_title', PARAM_RAW);

        // Content
        $mform->addElement('textarea', 'config_body', get_string('config_body', 'theme_edly'));
        $mform->setDefault('config_body', 'Break into a new field like information technology or data science.');
        $mform->setType('config_body', PARAM_RAW);

        // Action URL
        $mform->addElement('text', 'config_action_url', get_string('config_action_url', 'block_edly_newsletter'));
        $mform->setType('config_action_url', PARAM_RAW);

        $mform->addElement('static', 'config_newsletter_doc', '<b><a style="color: var(--main-color)" href="https://docs.hibootstrap.com/docs/edly-moodle-theme-documentation/faqs/get-mailchimp-newsletter-form-action-url/" target="_blank">Doc link: Get MailChimp Newsletter Form Action URL</a></b>'); 

        // Placeholder Text
        $mform->addElement('text', 'config_placeholder', get_string('config_placeholder', 'block_edly_newsletter'));
        $mform->setDefault('config_placeholder', 'Enter your email');
        $mform->setType('config_placeholder', PARAM_RAW);

        // Button Text
        $mform->addElement('text', 'config_btn', get_string('config_btn', 'block_edly_newsletter'));
        $mform->setDefault('config_btn', 'Subscribe');
        $mform->setType('config_btn', PARAM_RAW);

        // Section Image header title according to language file.
        $mform->addElement('header', 'config_image_heading', get_string('config_image_heading', 'theme_edly'));

        $mform->addElement('static', 'config_image_doc', '<b><a style="color: var(--main-color)" href="https://docs.hibootstrap.com/docs/edly-moodle-theme-documentation/faqs/how-to-get-the-image-url/" target="_blank">Doc link: How to make Image URL?</a></b>'); 
            
        // Section Image
        $mform->addElement('text', 'config_img', 'Section Image 1 URL');
        $mform->setType('config_img', PARAM_TEXT);

        // Shape Image
        $mform->addElement('text', 'config_shape_img', 'Shape Image 2 URL');
        $mform->setType('config_shape_img', PARAM_TEXT);
    }
}
