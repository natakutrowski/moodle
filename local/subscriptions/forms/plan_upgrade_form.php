<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');

class plan_upgrade_form extends moodleform {

    public function definition() {
        global $DB;

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $plans = $DB->get_records_menu(
            'subscription_plan',
            null,
            'name ASC',
            'id, name'
        );

        $mform->addElement(
            'select',
            'fromplanid',
            get_string('upgrade_fromplan', 'local_subscriptions'),
            $plans,
            ['class' => 'select2']
        );
        $mform->setType('fromplanid', PARAM_INT);
        $mform->addRule('fromplanid', null, 'required');

        $mform->addElement(
            'select',
            'toplanid',
            get_string('upgrade_toplan', 'local_subscriptions'),
            $plans,
            ['class' => 'select2']
        );
        $mform->setType('toplanid', PARAM_INT);
        $mform->addRule('toplanid', null, 'required');

        $pricingmodes = [
            'difference' => get_string('upgrade_pricing_difference', 'local_subscriptions'),
        ];

        $mform->addElement(
            'select',
            'pricingmode',
            get_string('upgrade_pricingmode', 'local_subscriptions'),
            $pricingmodes
        );
        $mform->setType('pricingmode', PARAM_ALPHANUMEXT);
        $mform->setDefault('pricingmode', 'difference');
        $mform->addHelpButton('pricingmode', 'upgrade_pricingmode', 'local_subscriptions');

        $mform->addElement(
            'selectyesno',
            'isactive',
            get_string('upgrade_isactive', 'local_subscriptions')
        );
        $mform->setType('isactive', PARAM_INT);
        $mform->setDefault('isactive', 1);

        $this->add_action_buttons(true, get_string('saveupgrade', 'local_subscriptions'));
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        $id = (int)($data['id'] ?? 0);
        $fromplanid = (int)($data['fromplanid'] ?? 0);
        $toplanid = (int)($data['toplanid'] ?? 0);

        if ($fromplanid === $toplanid) {
            $errors['toplanid'] = get_string('upgrade_same_plan_error', 'local_subscriptions');
            return $errors;
        }

        $existing = $DB->get_record('subscription_plan_upgrade', [
            'fromplanid' => $fromplanid,
            'toplanid' => $toplanid,
        ]);

        if ($existing && (int)$existing->id !== $id) {
            $errors['toplanid'] = get_string('upgrade_already_exists', 'local_subscriptions');
        }

        return $errors;
    }
}