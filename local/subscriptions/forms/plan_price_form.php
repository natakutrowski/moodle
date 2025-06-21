<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_config.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');

use local_subscriptions\subscription_config;

class plan_price_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $planid = $this->_customdata['planid'] ?? null;
        $editingcurrency = $this->_customdata['editingcurrency'] ?? null;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'planid');
        $mform->setType('planid', PARAM_INT);
        $mform->setDefault('planid', $planid);

        $alloptions = subscription_config::get_currency_options();
        $usedcurrencies = local_subscriptions_get_used_currencies_for_plan($planid);
        $editingcurrency = $this->_customdata['editingcurrency'] ?? null;

        $options = [];
        foreach ($alloptions as $code => $label) {
            if (!in_array($code, $usedcurrencies) || $code === $editingcurrency) {
                $options[$code] = $label;
            }
        }
        $mform->addElement('select', 'currency', get_string('currency', 'local_subscriptions'), $options);
        $mform->setType('currency', PARAM_ALPHANUM);
        $mform->addRule('currency', null, 'required');

        $mform->addElement('text', 'price', get_string('price', 'local_subscriptions'));
        $mform->setType('price', PARAM_FLOAT);
        $mform->addRule('price', null, 'required');
        $mform->addRule('price', get_string('err_numeric', 'form'), 'numeric');

        $this->add_action_buttons(true, get_string('saveprice', 'local_subscriptions'));
    }

    public function validation($data, $files) {
        $errors = [];

        if ($data['price'] <= 0) {
            $errors['price'] = get_string('error_invalid_price', 'local_subscriptions');
        }

        if (empty($data['id'])) {
            // Nouvelle entrée : vérifier si la devise est déjà utilisée pour ce plan.
            global $DB;
            $exists = $DB->record_exists('subscription_plan_price', [
                'plan_id' => $data['planid'],
                'currency' => strtoupper(trim($data['currency']))
            ]);

            if ($exists) {
                $errors['currency'] = get_string('error_currency_already_exists', 'local_subscriptions');
            }
        }       

        return $errors;
    }
}
