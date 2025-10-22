<?php
defined('MOODLE_INTERNAL') || die();

class block_edly_subscribe_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('configtitle', 'block_edly_subscribe'));

        // En-tête de section
        $mform->addElement('text', 'config_top_title', get_string('top_title', 'block_edly_subscribe'));
        $mform->setType('config_top_title', PARAM_RAW_TRIMMED);
        $mform->addElement('text', 'config_title', get_string('title', 'block_edly_subscribe'));
        $mform->setType('config_title', PARAM_RAW_TRIMMED);

        // Filtres d’affichage
        $mform->addElement('advcheckbox', 'config_onlyactive', get_string('onlyactive', 'block_edly_subscribe'));
        $mform->setDefault('config_onlyactive', 1);

        $mform->addElement('text', 'config_planids', get_string('planids', 'block_edly_subscribe'), ['size' => 40]);
        $mform->setType('config_planids', PARAM_TEXT);
        $mform->addHelpButton('config_planids', 'planids', 'block_edly_subscribe');

        $mform->addElement('advcheckbox', 'config_sortbydur', get_string('sortbydur', 'block_edly_subscribe'));
        $mform->setDefault('config_sortbydur', 1);

        $mform->addElement('static', 'config_note', '', get_string('renderernote', 'block_edly_subscribe'));
    }
}
