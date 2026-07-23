<?php

namespace local_subscriptions\commerce\payment\orchestration;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;

/**
 * Raised when the selected provider rejects a Commerce payment request.
 */
final class CommercePaymentValidationException
    extends CommercePaymentOrchestrationException {

    public function __construct(
        string $requestreference,
        string $providerkey,
        private readonly CommercePaymentProviderValidationResult
            $validation
    ) {
        parent::__construct(
            'The selected Commerce payment provider rejected the request.',
            'payment_validation_failed',
            $requestreference,
            $providerkey,
            [
                'validation' =>
                    $validation->to_array(),
            ]
        );
    }

    public function get_validation():
        CommercePaymentProviderValidationResult {
        return $this->validation;
    }
}