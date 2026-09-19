<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\certification;

defined('MOODLE_INTERNAL') || die();

final class CommercePaymentMethodsCertification {
    public function __construct(
        public readonly bool $certified,
        public readonly array $errors,
        public readonly array $warnings,
        public readonly array $checks
    ) {
    }
}
