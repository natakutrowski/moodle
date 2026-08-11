<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification\bundle;

defined('MOODLE_INTERNAL') || die();

final class CommerceBundlePurchaseCertificationReport {
    public function __construct(
        public readonly string $purchasereference,
        public readonly string $scenario,
        public readonly bool $certified,
        public readonly array $checks
    ) {
    }

    public function to_array(): array {
        return [
            'phase' => '7.95H4.9',
            'purchase_reference' => $this->purchasereference,
            'scenario' => $this->scenario,
            'certified' => $this->certified,
            'checks' => $this->checks,
        ];
    }
}
