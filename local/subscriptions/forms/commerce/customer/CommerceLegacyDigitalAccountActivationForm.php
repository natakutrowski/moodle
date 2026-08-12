<?php

declare(strict_types=1);

namespace local_subscriptions\form\commerce\customer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Password creation form for a Legacy Digital provisioned account.
 */
final class CommerceLegacyDigitalAccountActivationForm extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $data = (array)($this->_customdata ?? []);

        $mform->addElement(
            'hidden',
            'uid',
            (int)($data['uid'] ?? 0)
        );
        $mform->setType('uid', PARAM_INT);

        $mform->addElement(
            'hidden',
            'key',
            (string)($data['key'] ?? '')
        );
        $mform->setType('key', PARAM_ALPHANUM);

        $mform->addElement(
            'password',
            'password',
            get_string('newpassword'),
            [
                'autocomplete' => 'new-password',
                'class' => 'commerce-guest-activation__password-input',
            ]
        );
        $mform->setType('password', PARAM_RAW);
        $mform->addRule(
            'password',
            null,
            'required',
            null,
            'client'
        );

        $mform->addElement(
            'password',
            'passwordconfirm',
            get_string(
                'commerce_guest_activation_confirm_password',
                'local_subscriptions'
            ),
            [
                'autocomplete' => 'new-password',
                'class' => 'commerce-guest-activation__password-input',
            ]
        );
        $mform->setType('passwordconfirm', PARAM_RAW);
        $mform->addRule(
            'passwordconfirm',
            null,
            'required',
            null,
            'client'
        );

        $this->add_action_buttons(
            false,
            get_string(
                'commerce_legacy_account_activation_submit',
                'local_subscriptions'
            )
        );
    }

    public function validation(
        $data,
        $files
    ): array {
        $errors = parent::validation($data, $files);
        $password = (string)($data['password'] ?? '');

        if (
            $password !==
            (string)($data['passwordconfirm'] ?? '')
        ) {
            $errors['passwordconfirm'] = get_string('passwordsdiffer');
        }

        $passworderror = '';
        if (
            $password !== ''
            && !check_password_policy(
                $password,
                $passworderror
            )
        ) {
            $errors['password'] = $passworderror;
        }

        return $errors;
    }
}
