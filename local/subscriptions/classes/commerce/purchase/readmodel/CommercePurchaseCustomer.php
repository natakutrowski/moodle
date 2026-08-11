<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\readmodel;

defined('MOODLE_INTERNAL') || die();

final class CommercePurchaseCustomer {
    public function __construct(
        public readonly ?int $userid,
        public readonly string $email,
        public readonly string $firstname = '',
        public readonly string $lastname = ''
    ) {
    }

    public function display_name(): string {
        return trim($this->firstname . ' ' . $this->lastname);
    }
}
