<?php

declare(strict_types=1);

namespace local_subscriptions\tests\fixtures;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;

/** Available provider fixture that never performs an external call. */
final class CommerceCatalogTestPaymentProvider implements CommercePaymentProvider {
    public function __construct(
        private readonly string $key = 'stripe',
        private readonly array $currencies = ['EUR']
    ) {
    }

    public function get_key(): string {
        return $this->key;
    }

    public function get_priority(): int {
        return 100;
    }

    public function is_available(): bool {
        return true;
    }

    public function get_capabilities(): CommercePaymentProviderCapabilities {
        return new CommercePaymentProviderCapabilities(
            $this->currencies,
            true,
            false,
            false,
            false,
            true
        );
    }

    public function supports(CommercePaymentRequest $request): bool {
        return $request->requires_payment()
            && $this->get_capabilities()->supports_currency($request->get_currency());
    }

    public function validate(
        CommercePaymentRequest $request
    ): CommercePaymentProviderValidationResult {
        $result = CommercePaymentProviderValidationResult::valid();

        if (!$this->supports($request)) {
            $result->add_error(
                'unsupported_request',
                'The test provider does not support this request.'
            );
        }

        return $result;
    }

    public function initialize(
        CommercePaymentRequest $request,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult {
        return CommercePaymentResult::pending(
            $request->get_reference(),
            $this->key,
            $this->key . '-test-payment'
        );
    }

    public function retrieve(
        string $providerpaymentid,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult {
        return CommercePaymentResult::pending(
            'catalogue-test',
            $this->key,
            $providerpaymentid
        );
    }

    public function cancel(
        string $providerpaymentid,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult {
        return CommercePaymentResult::pending(
            'catalogue-test',
            $this->key,
            $providerpaymentid
        );
    }
}
