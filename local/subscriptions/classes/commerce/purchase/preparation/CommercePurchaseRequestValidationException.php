<?php

namespace local_subscriptions\commerce\purchase\preparation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationResult;

/**
 * Raised when a complete Commerce purchase request cannot be prepared.
 */
final class CommercePurchaseRequestValidationException
    extends \RuntimeException {

    public function __construct(
        string $message,
        private readonly CommercePurchaseValidationResult
            $validationresult
    ) {
        parent::__construct($message);
    }

    public function get_validation_result():
        CommercePurchaseValidationResult {
        return $this->validationresult;
    }
}