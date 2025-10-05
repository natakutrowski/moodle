<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_subscriptions\subscription_config;
class plan_translation_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $data = $this->_customdata;

        $translation = $data['translation'] ?? null;
        $plan = $data['plan'];
        $editing = !empty($data['editing']);

        // Champs cachés
        $mform->addElement('hidden', 'id', $translation->id ?? '');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'planid', $plan->id);
        $mform->setType('planid', PARAM_INT);

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_RAW);

        // Langues disponibles
        $langs = get_string_manager()->get_list_of_translations();
        $usedlangs = [];

        if (!$editing) {
            global $DB;
            $usedlangs = $DB->get_records_menu('subscription_plan_translation', [
                'planid' => $plan->id
            ], '', 'lang, id');
        }

        $select = $mform->createElement('select', 'lang', get_string('language', 'local_subscriptions'));

        foreach ($langs as $code => $label) {
            $attrs = [];
            if (!$editing && isset($usedlangs[$code])) {
                $label .= ' (' . get_string('alreadyused', 'local_subscriptions') . ')';
                $attrs['disabled'] = 'disabled';
            }
            $select->addOption($label, $code, $attrs);
        }

        $mform->setType('lang', PARAM_ALPHANUMEXT);

        // Label du plan
        $planlabel = html_writer::div(
            html_writer::tag('strong', get_string('plandefaultname', 'local_subscriptions')) . '<br>' .
            html_writer::tag('div', format_string($plan->name) . ' (id : ' . $plan->id . ')', [
                'style' => 'background:#f9f9f9; border:1px solid #ddd; padding:8px 12px; margin:6px 0; border-radius:6px; font-size:1.1em;'
            ])
        );
        $mform->addElement('html', $planlabel);

        $mform->addElement($select);
        $mform->addElement('text', 'name', get_string('translatedname', 'local_subscriptions'));
        $mform->setType('name', PARAM_TEXT); // ← IMPORTANT
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // --- Description (éditeur riche avec fichiers) ----------------------------
        global $CFG;
        $context = \context_system::instance();
        $editoroptions = [
            'maxfiles'  => 50,
            'maxbytes'  => $CFG->maxbytes,
            'trusttext' => false,
            'subdirs'   => 0,
            'context'   => $context,
        ];

        $mform->addElement('editor', 'description_editor', get_string('description'), null, $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        // --- Préremplissage -------------------------------------------------------
        // NB : on aime bien préremplir dans le contrôleur, mais si tu préfères ici, fais :
        if (!empty($translation)) {
            // On prépare l'objet comme attendu par file_prepare_standard_editor
            $seed = (object)[
                'id'                => (int)$translation->id,
                'lang'              => (string)$translation->lang,
                'name'              => (string)$translation->name,
                'description'       => (string)($translation->description ?? ''),
                'descriptionformat' => (int)($translation->descriptionformat ?? FORMAT_HTML),
            ];

            // Injecte draft itemid + réécriture URLs pluginfile dans $seed->description_editor
            $seed = file_prepare_standard_editor(
                $seed, 'description', $editoroptions, $context,
                'local_subscriptions', 'plan_desc', $seed->id
            );

            $this->set_data($seed); // moodleform::set_data()
        }

        // --- Boutons d'action (submit / cancel / delete) ---

        $buttonarray = [];

        // Enregistrer (submit)
        $buttonarray[] = $mform->createElement(
            'submit', 'submittranslation',
            get_string('save', 'local_subscriptions')
        );

        // Annuler (utilise l'élément 'cancel' standard → $mform->is_cancelled() fonctionnera)
        $buttonarray[] = $mform->createElement('cancel');

        // Supprimer (en édition uniquement)
        $custom = $this->_customdata ?? [];
        $editing = !empty($custom['editing']);
        $translation = $custom['translation'] ?? null;

        if ($editing && $translation) {
            $buttonarray[] = $mform->createElement(
                'button', 'deletetranslation',
                get_string('deletetranslation', 'local_subscriptions'),
                [
                    'class'     => 'deletetranslation',
                    'data-id'   => $translation->id,
                    'data-name' => $translation->name,
                    'style' => 'margin-left:20px; background-color:#dc3545 !important;color:#fff !important;outline:none;box-shadow:none;',
                    'onfocus' => "this.style.boxShadow='0 0 0 .2rem rgba(220,53,69,.5)'",
                    'onblur'  => "this.style.boxShadow='none'",
                ]
            );
        }

        // Ajoute le groupe ; le 4e param est le séparateur (ici un espace).
        $mform->addGroup($buttonarray, 'actionbuttons', '', ' ', false);

        // Place ce groupe en “footer” (évite les soucis d’ancrage dans un header).
        $mform->closeHeaderBefore('actionbuttons');

    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty(trim((string)$data['name']))) {
            $errors['name'] = get_string('required');
        }
        return $errors;
    }
}