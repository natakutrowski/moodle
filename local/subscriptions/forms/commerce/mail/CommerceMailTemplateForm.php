<?php

declare(strict_types=1);

namespace local_subscriptions\form\commerce\mail;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\commerce\mail\CommerceMailType;
use moodleform;

final class CommerceMailTemplateForm extends moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $context = $this->_customdata['context'];
        $editoroptions = [
            'maxfiles' => 0,
            'trusttext' => false,
            'subdirs' => 0,
            'context' => $context,
        ];

        $mform->addElement('hidden', 'mailtype');
        $mform->setType('mailtype', PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', 'language');
        $mform->setType('language', PARAM_ALPHANUMEXT);

        $mform->addElement('static', 'mailtypelabel', get_string('commerce_mail_template_type', 'local_subscriptions'));
        $mform->addElement('static', 'languagelabel', get_string('commerce_mail_template_language', 'local_subscriptions'));
        $mform->addElement('advcheckbox', 'enabled', get_string('commerce_mail_template_enabled', 'local_subscriptions'));

        $mform->addElement('text', 'subject', get_string('commerce_mail_template_subject', 'local_subscriptions'), ['size' => 80]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'preheader', get_string('commerce_mail_template_preheader', 'local_subscriptions'), ['size' => 80]);
        $mform->setType('preheader', PARAM_TEXT);
        $mform->addElement('text', 'heading', get_string('commerce_mail_template_heading', 'local_subscriptions'), ['size' => 80]);
        $mform->setType('heading', PARAM_TEXT);

        foreach (['intro', 'outro', 'signature'] as $field) {
            $mform->addElement('editor', $field . '_editor', get_string('commerce_mail_template_' . $field, 'local_subscriptions'), null, $editoroptions);
            $mform->setType($field . '_editor', PARAM_RAW);
        }

        $mform->addElement('advcheckbox', 'headerimage', get_string('commerce_mail_template_headerimage_enabled', 'local_subscriptions'));
        $mform->addElement('filemanager', 'headerimage_filemanager',
            get_string('commerce_mail_template_headerimage_file', 'local_subscriptions'), null, [
                'context' => $context,
                'maxfiles' => 1,
                'subdirs' => 0,
                'accepted_types' => ['image'],
                'areamaxbytes' => 5 * 1024 * 1024,
            ]);
        $mform->hideIf('headerimage_filemanager', 'headerimage', 'notchecked');
        $mform->addElement('static', 'headerimagenote', '', get_string('commerce_mail_template_headerimage_note', 'local_subscriptions'));

        $tokens = ['{firstname}', '{fullname}', '{order_reference}', '{order_total}', '{order_url}',
            '{my_purchases_url}', '{my_courses_url}', '{digital_library_url}', '{support_email}',
            '{offer_url}', '{offer_product}', '{offer_expiry}', '{offer_price}', '{campaign_name}'];
        $mform->addElement('static', 'tokens', get_string('commerce_mail_template_tokens', 'local_subscriptions'),
            '<code>' . implode('</code> &nbsp; <code>', $tokens) . '</code>');

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (!in_array((string)($data['mailtype'] ?? ''), CommerceMailType::all(), true)) {
            $errors['subject'] = get_string('commerce_mail_template_invalid_type', 'local_subscriptions');
        }
        if (!in_array((string)($data['language'] ?? ''), ['fr', 'en', 'ru'], true)) {
            $errors['subject'] = get_string('commerce_mail_template_invalid_language', 'local_subscriptions');
        }
        return $errors;
    }
}
