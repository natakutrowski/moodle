<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_subscriptions\subscription_config;
class plan_form extends moodleform {
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $default_scope = $this->_customdata['accessscopeid'] ?? '';
        $default_duration_key = $this->_customdata['duration_key'] ?? '';

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('planname', 'local_subscriptions'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $scopes = $DB->get_records_menu('subscription_access_scope', null, 'name ASC', 'id, name');
        $mform->addElement('select', 'accessscopeid', get_string('scopes', 'local_subscriptions'), $scopes, [
            'size' => 1,
            'class' => 'select2'
        ]);

        $mform->setDefault('accessscopeid', $default_scope);
        $mform->addRule('accessscopeid', null, 'required');

        $plan_keys = subscription_config::get_plans();
        $mform->addElement('select', 'duration_key', get_string('planduration', 'local_subscriptions'), $plan_keys, [
            'size' => 1,
            'class' => 'select2'
        ]);
        $mform->setDefault('duration_key', $default_duration_key);
        $mform->setType('duration_key', PARAM_TEXT);
        $mform->addRule('duration_key', null, 'required');

        $mform->addElement('select', 'is_recurring', get_string('is_recurring', 'local_subscriptions'),
            [0 => get_string('no'), 1 => get_string('yes')]);
        $mform->setType('is_recurring', PARAM_INT);
        $mform->setDefault('is_recurring', 0);
        $mform->addHelpButton('is_recurring', 'is_recurring', 'local_subscriptions');

        // == Mise en avant (highlight_type) ==
        $options = [
            ''         => get_string('highlight_none', 'local_subscriptions'),
            'popular'  => get_string('highlight_popular', 'local_subscriptions'),
            'premium'  => get_string('highlight_premium', 'local_subscriptions'),
        ];
        $mform->addElement('select', 'highlight_type',
            get_string('plan_highlight', 'local_subscriptions'),
            $options
        );
        $mform->setType('highlight_type', PARAM_ALPHA);
        $mform->setDefault('highlight_type', ''); // par défaut rien
        $mform->addHelpButton('highlight_type', 'plan_highlight', 'local_subscriptions'); // si tu ajoutes un help string

        $this->add_action_buttons(true, get_string('saveplan', 'local_subscriptions'));
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $allowed = ['', 'popular', 'premium'];
        if (!in_array($data['highlight_type'] ?? '', $allowed, true)) {
            $errors['highlight_type'] = get_string('invaliddata', 'error');
        }

        $name = trim($data['name']);
        $id = (int)($data['id'] ?? 0);

        // Vérifie si un autre plan avec ce nom existe déjà (autre que celui en cours d'édition).
        $params = ['name' => $name];
        $existing = $DB->get_record_sql(
            "SELECT * FROM {subscription_plan} WHERE LOWER(name) = LOWER(:name)",
            $params
        );

        if ($existing && $existing->id != $id) {
            $errors['name'] = get_string('error_plan_name_exists', 'local_subscriptions');
        }

        return $errors;
    }
}