<?php

namespace local_subscriptions\commerce\payment\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\orchestration\CommercePaymentInitialization;

/**
 * Non-executing comparison between Legacy checkout data and Commerce.
 */
final class CommercePaymentShadowReport {

    public function __construct(
        private readonly int $legacypaymentrequestid,
        private readonly string $legacypaymentrequesttable,
        private readonly string $legacyprovider,
        private readonly string $legacycurrency,
        private readonly int $legacyamountminor,
        private readonly ?CommercePaymentInitialization
            $simulation,
        private readonly array $differences,
        private readonly array $errors = []
    ) {
    }

    public function is_compatible(): bool {
        return $this->errors === []
            && $this->differences === []
            && $this->simulation !== null
            && $this->simulation
                ->get_validation()
                ->is_valid();
    }

    public function get_differences(): array {
        return $this->differences;
    }

    public function get_errors(): array {
        return $this->errors;
    }

    public function get_simulation():
        ?CommercePaymentInitialization {
        return $this->simulation;
    }

    public function to_array(): array {
        return [
            'legacypaymentrequestid' =>
                $this->legacypaymentrequestid,

            'legacypaymentrequesttable' =>
                $this->legacypaymentrequesttable,

            'legacyprovider' =>
                $this->legacyprovider,

            'legacycurrency' =>
                $this->legacycurrency,

            'legacyamountminor' =>
                $this->legacyamountminor,

            'compatible' =>
                $this->is_compatible(),

            'differences' =>
                $this->differences,

            'errors' =>
                $this->errors,

            'simulation' =>
                $this->simulation?->to_array(),
        ];
    }
}