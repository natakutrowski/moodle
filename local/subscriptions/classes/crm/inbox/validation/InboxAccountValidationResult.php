<?php

namespace local_subscriptions\crm\inbox\validation;

defined('MOODLE_INTERNAL') || die();

final class InboxAccountValidationResult {

    /**
     * @param string[] $errors
     * @param string[] $warnings
     */
    public function __construct(
        public readonly array $errors,
        public readonly array $warnings = []
    ) {
    }

    public function is_valid(): bool {
        return empty($this->errors);
    }
}