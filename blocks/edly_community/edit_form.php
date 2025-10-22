<?php
defined('MOODLE_INTERNAL') || die();

class block_edly_community_edit_form extends block_edit_form {
    protected function specific_definition($mform) {

        $mform->addElement('header', 'cfg', get_string('configtitle', 'block_edly_community'));

        // HERO
        $mform->addElement('text', 'config_bgimage', get_string('bgimage', 'block_edly_community'));
        $mform->setType('config_bgimage', PARAM_URL);

        $mform->addElement('textarea', 'config_bigtext', get_string('bigtext', 'block_edly_community'),
            ['rows'=>3,'cols'=>60]);  $mform->setType('config_bigtext', PARAM_RAW);

        $mform->addElement('textarea', 'config_smalltext', get_string('smalltext', 'block_edly_community'),
            ['rows'=>3,'cols'=>60]);  $mform->setType('config_smalltext', PARAM_RAW);

        // Strapline
        $mform->addElement('textarea', 'config_strap', get_string('strap', 'block_edly_community'),
            ['rows'=>3,'cols'=>60]);  $mform->setType('config_strap', PARAM_RAW);

        // Scroller items
        $mform->addElement('textarea', 'config_scroller_raw', get_string('scroller_raw', 'block_edly_community'),
            ['rows'=>6,'cols'=>60]);
        $mform->setType('config_scroller_raw', PARAM_RAW);

        $mform->addElement('text', 'config_item_icon', get_string('item_icon', 'block_edly_community'));
        $mform->setType('config_item_icon', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('config_scroller_raw', 'scroller_raw', 'block_edly_community');
    }
}
