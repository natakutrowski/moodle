<?php
defined('MOODLE_INTERNAL') || die();

class block_edly_contact_form_edit_form extends block_edit_form {
    protected function specific_definition($mform) {

        $mform->addElement('header', 'cfg', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('title', 'block_edly_contact_form'));
        $mform->setType('config_title', PARAM_RAW_TRIMMED);
        $mform->setDefault('config_title', get_string('defaulttitle','block_edly_contact_form'));

        $mform->addElement('textarea', 'config_strap', get_string('strap', 'block_edly_contact_form'),
            ['rows'=>2, 'cols'=>60]);
        $mform->setType('config_strap', PARAM_RAW);
        $mform->setDefault('config_strap', get_string('defaultstrap','block_edly_contact_form'));

        $mform->addElement('text', 'config_recipient', get_string('recipient', 'block_edly_contact_form'));
        $mform->setType('config_recipient', PARAM_EMAIL);
        $mform->addRule('config_recipient', null, 'email', null, 'client');
        $mform->addHelpButton('config_recipient', 'recipient', 'block_edly_contact_form');

        // Image (colonne gauche)
        $mform->addElement('header', 'cfg_left', get_string('left_side', 'block_edly_contact_form'));
        $mform->addElement('text', 'config_imageurl', get_string('imageurl', 'block_edly_contact_form'));
        $mform->setType('config_imageurl', PARAM_URL);
        $mform->addHelpButton('config_imageurl', 'imageurl', 'block_edly_contact_form');

        $mform->addElement('text', 'config_imagealt', get_string('imagealt', 'block_edly_contact_form'));
        $mform->setType('config_imagealt', PARAM_RAW_TRIMMED);

        // Réseaux sociaux
        $mform->addElement('header', 'cfg_social', get_string('social_header', 'block_edly_contact_form'));

        $mform->addElement('text', 'config_youtube',   get_string('youtube', 'block_edly_contact_form'));
        $mform->setType('config_youtube', PARAM_URL);

        $mform->addElement('text', 'config_instagram', get_string('instagram', 'block_edly_contact_form'));
        $mform->setType('config_instagram', PARAM_URL);

        $mform->addElement('text', 'config_telegram',  get_string('telegram', 'block_edly_contact_form'));
        $mform->setType('config_telegram', PARAM_URL);

    }
}
