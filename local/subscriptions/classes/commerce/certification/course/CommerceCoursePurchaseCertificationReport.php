<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification\course;

defined('MOODLE_INTERNAL') || die();

final class CommerceCoursePurchaseCertificationReport {
    /** @param array<int,array{key:string,status:string,message:string,details:array}> $checks */
    public function __construct(
        public readonly string $purchasereference,
        public readonly bool $certified,
        public readonly array $checks
    ) {
    }

    public function to_array(): array {
        return [
            'phase' => '7.95H4.7',
            'purchase_reference' => $this->purchasereference,
            'certified' => $this->certified,
            'checks' => $this->checks,
        ];
    }
}
