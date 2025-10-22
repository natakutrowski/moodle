<?php
defined('MOODLE_INTERNAL') || die();

class block_edly_about_me_edit_form extends block_edit_form {
    protected function specific_definition($mform) {

        $mform->addElement('header', 'cfg_hdr', get_string('configtitle', 'block_edly_about_me'));

        // HERO
        $mform->addElement('text', 'config_hero_title', get_string('hero_title', 'block_edly_about_me'));
        $mform->setType('config_hero_title', PARAM_RAW_TRIMMED);

        $mform->addElement('textarea', 'config_hero_lead', get_string('hero_lead', 'block_edly_about_me'),
            ['rows'=>3, 'cols'=>60]);
        $mform->setType('config_hero_lead', PARAM_RAW);

        $mform->addElement('text', 'config_hero_image', get_string('hero_image', 'block_edly_about_me'));
        $mform->setType('config_hero_image', PARAM_URL);

        $mform->addElement('text', 'config_hero_badge_text', get_string('hero_badge_text', 'block_edly_about_me'));
        $mform->setType('config_hero_badge_text', PARAM_RAW_TRIMMED);

        $mform->addElement('text', 'config_hero_badge_url', get_string('hero_badge_url', 'block_edly_about_me'));
        $mform->setType('config_hero_badge_url', PARAM_URL);

        $mform->addElement('header', 'cards_hdr', get_string('cards_title', 'block_edly_about_me'));

        // Cartes (un seul contenu par carte, HTML autorisé)
        $mform->addElement('textarea', 'config_card1_content',
            get_string('card1_content', 'block_edly_about_me'), ['rows'=>6,'cols'=>60]);
        $mform->setType('config_card1_content', PARAM_RAW);

        $mform->addElement('textarea', 'config_card2_content',
            get_string('card2_content', 'block_edly_about_me'), ['rows'=>6,'cols'=>60]);
        $mform->setType('config_card2_content', PARAM_RAW);

    }
}
