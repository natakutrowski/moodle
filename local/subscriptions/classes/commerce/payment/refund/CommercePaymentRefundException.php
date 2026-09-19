<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\refund;

defined('MOODLE_INTERNAL') || die();

final class CommercePaymentRefundException extends \RuntimeException {
    public function __construct(
        string $message,
        private readonly string $codekey,
        private readonly ?string $providerkey = null,
        private readonly array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function get_code_key(): string {
        return $this->codekey;
    }

    public function get_provider_key(): ?string {
        return $this->providerkey;
    }

    public function get_context(): array {
        return $this->context;
    }
}
