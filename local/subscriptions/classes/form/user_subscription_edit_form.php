<?php

namespace local_subscriptions\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_subscriptions\constants\Status;

class user_subscription_edit_form extends \moodleform {

    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('date_selector', 'start_date', get_string('start_date', 'local_subscriptions'));
        $mform->addRule('start_date', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'no_end_date', get_string('no_end_date', 'local_subscriptions'));
        $mform->setDefault('no_end_date', 0);

        $mform->addElement('date_selector', 'end_date', get_string('end_date', 'local_subscriptions'));

        $statuses = [
            Status::ACTIVE => get_string('status_active', 'local_subscriptions'),
            Status::QUEUED => get_string('status_queued', 'local_subscriptions'),
            Status::INACTIVE => get_string('status_inactive', 'local_subscriptions'),
            Status::EXPIRED => get_string('status_expired', 'local_subscriptions'),
            Status::SUSPENDED => get_string('status_suspended', 'local_subscriptions'),
            Status::CANCELED => get_string('status_canceled', 'local_subscriptions'),
            Status::REPLACED => get_string('status_replaced', 'local_subscriptions'),
            Status::PENDING => get_string('status_pending', 'local_subscriptions'),
            Status::FAILED => get_string('status_failed', 'local_subscriptions'),
            Status::ERROR => get_string('status_error', 'local_subscriptions'),
            Status::PAID => get_string('status_paid', 'local_subscriptions'),
            Status::COMPLETED => get_string('status_completed', 'local_subscriptions'),
        ];

        $mform->addElement('select', 'status', get_string('status', 'local_subscriptions'), $statuses);
        $mform->addRule('status', null, 'required', null, 'client');

        $this->add_action_buttons(
            true,
            get_string('savechanges')
        );
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $start = (int)($data['start_date'] ?? 0);
        $end = (int)($data['end_date'] ?? 0);
        $noend = !empty($data['no_end_date']);

        if (!$noend && $end > 0 && $end < $start) {
            $errors['end_date'] = get_string('end_date_before_start_date', 'local_subscriptions');
        }

        return $errors;
    }
}