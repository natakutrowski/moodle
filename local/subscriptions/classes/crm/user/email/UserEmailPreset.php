<?php

namespace local_subscriptions\crm\user\email;

defined('MOODLE_INTERNAL') || die();

final class UserEmailPreset {

    public function __construct(
        public readonly string $subject,
        public readonly string $message,
        public readonly ?string $buttonlabel = null,
        public readonly ?string $buttonurl = null
    ) {
    }

    public function to_form_data(): array {
        return [
            'subject' => $this->subject,
            'message' => [
                'text' => $this->message,
                'format' => FORMAT_HTML,
            ],
            'buttonlabel' => $this->buttonlabel ?? '',
            'buttonurl' => $this->buttonurl ?? '',
        ];
    }
}