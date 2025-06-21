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

        $mform->addElement('hidden', 'plan_id', $plan->id);
        $mform->setType('plan_id', PARAM_INT);

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_RAW);

        // Langues disponibles
        $langs = get_string_manager()->get_list_of_translations();
        $usedlangs = [];

        if (!$editing) {
            global $DB;
            $usedlangs = $DB->get_records_menu('subscription_plan_translation', [
                'plan_id' => $plan->id
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
        $mform->addElement('textarea', 'description', get_string('translateddescription', 'local_subscriptions'), 'rows="3" cols="60"');

        if ($translation) {
            $mform->setDefaults([
                'lang' => $translation->lang,
                'name' => $translation->name,
                'description' => $translation->description
            ]);
        }
        
        // Boutons d'action personnalisés.
		$buttonarray = [];
		$buttonarray[] = $mform->createElement('submit', 'submittranslation', get_string('save', 'local_subscriptions'));
		$cancelurl = new moodle_url(subscription_config::plans_translations_page());
		
		$cancelbutton = html_writer::tag('button', get_string('cancel', 'local_subscriptions'), [
			'type' => 'button',
			'class' => 'btn btn-secondary',
			'onclick' => "window.location.href='" . $cancelurl->out() . "'"
		]);
		
		$buttonarray[] = $mform->createElement('html', $cancelbutton);
		
		if ($editing && $translation) {
			// Ajout du bouton Supprimer, de type 'button' (pas submit)
			$deletebutton = html_writer::tag('button', get_string('deletetranslation', 'local_subscriptions'), [
				'type' => 'button',
				'class' => 'btn btn-danger deletetranslation',
				'data-id' => $translation->id,
				'data-name' => $translation->name,
				'style' => 'margin-left: 10px;'
			]);
			$buttonarray[] = $mform->createElement('html', $deletebutton);
		}
		
		$mform->addGroup($buttonarray, 'actionbuttons', '', ['style' => 'margin-left: 10px;'], false);

    }
}