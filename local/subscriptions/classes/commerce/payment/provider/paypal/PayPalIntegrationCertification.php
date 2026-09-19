<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

defined('MOODLE_INTERNAL') || die();

final class PayPalIntegrationCertification {
    /**
     * @param string[] $errors
     * @param string[] $warnings
     * @param array<string,mixed> $checks
     */
    public function __construct(
        public readonly bool $certified,
        public readonly array $errors,
        public readonly array $warnings,
        public readonly array $checks
    ) {
    }
}
