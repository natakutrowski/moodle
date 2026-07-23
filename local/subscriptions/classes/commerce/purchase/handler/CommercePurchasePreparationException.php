<?php

namespace local_subscriptions\commerce\purchase\handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when a PurchaseHandler cannot prepare an invalid item.
 */
final class CommercePurchasePreparationException
    extends \RuntimeException {

    public function __construct(
        string $message,
        private readonly ?CommercePurchaseValidationResult
            $validationresult = null
    ) {
        parent::__construct($message);
    }

    public function get_validation_result():
        ?CommercePurchaseValidationResult {
        return $this->validationresult;
    }
}