<?php

namespace local_subscriptions\commerce\payment\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when a Commerce payment provider operation cannot be completed.
 */
class CommercePaymentProviderException
    extends \RuntimeException {

    public function __construct(
        string $message,
        private readonly ?string $providerkey = null,
        private readonly ?string $providercode = null,
        private readonly array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            0,
            $previous
        );
    }

    public function get_provider_key(): ?string {
        return $this->providerkey;
    }

    public function get_provider_code(): ?string {
        return $this->providercode;
    }

    public function get_context(): array {
        return $this->context;
    }
}