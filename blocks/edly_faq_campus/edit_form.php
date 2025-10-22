<?php
defined('MOODLE_INTERNAL') || die();

class block_edly_faq_campus_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        // En-tête standard.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // 1) Titre affiché en haut du bloc (défaut: "FAQ").
        $mform->addElement('text', 'config_title', get_string('title', 'block_edly_faq_campus', null, true));
        $mform->setType('config_title', PARAM_RAW_TRIMMED);
        if (!isset($this->block->config->title)) {
            $mform->setDefault('config_title', 'FAQ');
        }

        // 2) Nombre de questions (1..30).
        $range = [];
        for ($i = 1; $i <= 30; $i++) { $range[$i] = (string)$i; }
        $mform->addElement('select', 'config_question_count',
            get_string('questioncount', 'block_edly_faq_campus', null, true), $range);
        $mform->setDefault('config_question_count',
            isset($this->block->config->question_count) ? (int)$this->block->config->question_count : 6);

        // Le formulaire est construit avec le nombre **actuel** (après 1er enregistrement, ré-éditer pour voir +/–).
        $count = isset($this->block->config->question_count) ? (int)$this->block->config->question_count : 6;

        // 3) Pour chaque item : Question (HTML) + Réponse (HTML).
        for ($i = 1; $i <= $count; $i++) {
            $mform->addElement('header', 'cfg_q_'.$i, get_string('questionx', 'block_edly_faq_campus', $i, true));

            $mform->addElement('textarea', 'config_question_'.$i,
                get_string('question_label', 'block_edly_faq_campus', null, true),
                ['rows' => 2, 'cols' => 60]);
            $mform->setType('config_question_'.$i, PARAM_RAW);

            $mform->addElement('textarea', 'config_answer_'.$i,
                get_string('answer_label', 'block_edly_faq_campus', null, true),
                ['rows' => 6, 'cols' => 60]);
            $mform->setType('config_answer_'.$i, PARAM_RAW);

            // Petites valeurs par défaut la 1re fois.
            if (empty($this->block->config)) {
                $mform->setDefault('config_question_'.$i, 'Votre question '.$i);
                $mform->setDefault('config_answer_'.$i, '<p>Votre réponse en HTML…</p>');
            }
        }
    }
}
