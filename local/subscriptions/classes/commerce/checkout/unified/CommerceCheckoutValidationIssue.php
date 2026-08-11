<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

/** One structured checkout validation problem. */
final class CommerceCheckoutValidationIssue {
    public function __construct(
        private readonly string $code,
        private readonly string $message,
        private readonly array $context = []
    ) {
        if (trim($code) === '' || trim($message) === '') {
            throw new \coding_exception('Checkout validation issue code and message are required.');
        }
    }

    public function get_code(): string { return trim($this->code); }
    public function get_message(): string { return trim($this->message); }
    public function get_context(): array { return $this->context; }
}
