<?php

declare(strict_types=1);

namespace local_subscriptions\form\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/** Password definition form for a paid provisional Guest Checkout account. */
final class CommerceGuestAccountActivationForm extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $data = (array)($this->_customdata ?? []);

        foreach (['uid', 'sessionid'] as $field) {
            $mform->addElement('hidden', $field, (int)($data[$field] ?? 0));
            $mform->setType($field, PARAM_INT);
        }
        $mform->addElement('hidden', 'reference', (string)($data['reference'] ?? ''));
        $mform->setType('reference', PARAM_ALPHANUMEXT);

        $mform->addElement('hidden', 'key', (string)($data['key'] ?? ''));
        $mform->setType('key', PARAM_ALPHANUM);

        $mform->addElement('password', 'password', get_string('newpassword'), [
            'autocomplete' => 'new-password',
            'class' => 'commerce-guest-activation__password-input',
        ]);
        $mform->setType('password', PARAM_RAW);
        $mform->addRule('password', null, 'required', null, 'client');

        $mform->addElement('password', 'passwordconfirm', get_string('commerce_guest_activation_confirm_password', 'local_subscriptions'), [
            'autocomplete' => 'new-password',
            'class' => 'commerce-guest-activation__password-input',
        ]);
        $mform->setType('passwordconfirm', PARAM_RAW);
        $mform->addRule('passwordconfirm', null, 'required', null, 'client');

        $requirements = (array)($data['securityrequirements'] ?? []);
        $policy = (array)($data['passwordpolicy'] ?? []);
        if ($requirements !== []) {
            $rules = ['minlength'];
            if ((int)($policy['minlower'] ?? 0) > 0) {
                $rules[] = 'lowercase';
            }
            if ((int)($policy['minupper'] ?? 0) > 0) {
                $rules[] = 'uppercase';
            }
            if ((int)($policy['mindigits'] ?? 0) > 0) {
                $rules[] = 'digit';
            }
            if ((int)($policy['minspecial'] ?? 0) > 0) {
                $rules[] = 'special';
            }

            $items = '';
            foreach ($requirements as $index => $requirement) {
                $rule = $rules[$index] ?? 'policy';
                $items .= \html_writer::tag(
                    'li',
                    \html_writer::span('•', 'commerce-guest-activation__requirement-icon', [
                        'aria-hidden' => 'true',
                    ]) . \html_writer::span((string)$requirement),
                    [
                        'class' => 'commerce-guest-activation__requirement',
                        'data-password-rule' => $rule,
                    ]
                );
            }

            $items .= \html_writer::tag(
                'li',
                \html_writer::span('•', 'commerce-guest-activation__requirement-icon', [
                    'aria-hidden' => 'true',
                ]) . \html_writer::span(
                    get_string('commerce_guest_activation_security_match', 'local_subscriptions')
                ),
                [
                    'class' => 'commerce-guest-activation__requirement',
                    'data-password-rule' => 'match',
                ]
            );

            $security = \html_writer::start_div('commerce-guest-activation__security');
            $security .= \html_writer::div('🔒', 'commerce-guest-activation__security-icon', [
                'aria-hidden' => 'true',
            ]);
            $security .= \html_writer::start_div('commerce-guest-activation__security-content');
            $security .= \html_writer::tag(
                'h2',
                get_string('commerce_guest_activation_security_title', 'local_subscriptions'),
                ['class' => 'commerce-guest-activation__security-title']
            );
            $security .= \html_writer::tag(
                'ul',
                $items,
                ['class' => 'commerce-guest-activation__requirements']
            );
            $security .= \html_writer::end_div();
            $security .= \html_writer::end_div();

            $mform->addElement('html', $security);
        }

        $this->add_action_buttons(false, get_string('commerce_guest_activation_submit', 'local_subscriptions'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $password = (string)($data['password'] ?? '');
        if ($password !== (string)($data['passwordconfirm'] ?? '')) {
            $errors['passwordconfirm'] = get_string('passwordsdiffer');
        }
        $passworderror = '';
        if ($password !== '' && !check_password_policy($password, $passworderror)) {
            $errors['password'] = $passworderror;
        }
        return $errors;
    }
}
