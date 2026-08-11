<?php

declare(strict_types=1);

namespace local_subscriptions\form\commerce\support;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_subscriptions\commerce\support\CommerceSupportRequest;
use html_writer;

final class CommerceSupportRequestForm extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $context = (array)($this->_customdata['context'] ?? []);

        $mform->addElement('hidden', 'reference');
        $mform->setType('reference', PARAM_ALPHANUMEXT);
        $mform->setDefault('reference', (string)($context['reference'] ?? ''));

        if (!empty($context['publicreference'])) {
            $mform->addElement(
                'static',
                'orderreference',
                get_string('commerce_support_order', 'local_subscriptions'),
                s((string)$context['publicreference'])
            );
        }
        $identityeditable = !empty($context['identityeditable']);
        if ($identityeditable) {
            $mform->addElement(
                'html',
                html_writer::tag(
                    'p',
                    get_string('commerce_support_guest_contact_help', 'local_subscriptions'),
                    ['class' => 'text-muted small mb-3']
                )
            );

            $mform->addElement(
                'text',
                'firstname',
                get_string('firstname'),
                ['maxlength' => 100, 'size' => 40]
            );
            $mform->setType('firstname', PARAM_TEXT);

            $mform->addElement(
                'text',
                'lastname',
                get_string('lastname'),
                ['maxlength' => 100, 'size' => 40]
            );
            $mform->setType('lastname', PARAM_TEXT);

            $mform->addElement(
                'text',
                'email',
                get_string('email'),
                ['maxlength' => 254, 'size' => 60, 'autocomplete' => 'email']
            );
            $mform->setType('email', PARAM_EMAIL);
            $mform->addRule('email', null, 'required', null, 'client');
            $mform->setDefault('email', (string)($context['email'] ?? ''));
        } else {
            $mform->addElement(
                'static',
                'customer',
                get_string('commerce_support_customer', 'local_subscriptions'),
                s((string)($context['customer'] ?? ''))
            );
            $mform->addElement(
                'static',
                'emaildisplay',
                get_string('commerce_support_email', 'local_subscriptions'),
                s((string)($context['email'] ?? ''))
            );
        }

        $categories = [];
        foreach (CommerceSupportRequest::categories() as $category) {
            $categories[$category] = get_string(
                'commerce_support_category_' . $category,
                'local_subscriptions'
            );
        }
        $mform->addElement(
            'select',
            'category',
            get_string('commerce_support_category', 'local_subscriptions'),
            $categories
        );
        $mform->setType('category', PARAM_ALPHANUMEXT);

        $mform->addElement('text', 'subject', get_string('commerce_support_subject', 'local_subscriptions'), [
            'maxlength' => 180,
            'size' => 60,
        ]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');

        $mform->addElement('textarea', 'message', get_string('commerce_support_message', 'local_subscriptions'), [
            'rows' => 9,
            'class' => 'w-100',
        ]);
        $mform->setType('message', PARAM_TEXT);
        $mform->addRule('message', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('commerce_support_send', 'local_subscriptions'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (!in_array((string)($data['category'] ?? ''), CommerceSupportRequest::categories(), true)) {
            $errors['category'] = get_string('invaliddata', 'error');
        }
        if (!empty($this->_customdata['context']['identityeditable'])) {
            $email = trim((string)($data['email'] ?? ''));
            if ($email === '') {
                $errors['email'] = get_string('required');
            } else if (!validate_email($email)) {
                $errors['email'] = get_string('invalidemail');
            }
        }
        if (trim((string)($data['message'] ?? '')) === '') {
            $errors['message'] = get_string('required');
        }
        return $errors;
    }
}
