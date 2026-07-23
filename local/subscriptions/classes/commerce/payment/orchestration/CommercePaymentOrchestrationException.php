<?php

namespace local_subscriptions\commerce\payment\orchestration;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when Commerce cannot orchestrate a payment.
 */
class CommercePaymentOrchestrationException
    extends \RuntimeException {

    public function __construct(
        string $message,
        private readonly string $orchestrationcode,
        private readonly ?string $requestreference = null,
        private readonly ?string $providerkey = null,
        private readonly array $context = [],
        ?\Throwable $previous = null
    ) {
        if (trim($orchestrationcode) === '') {
            throw new \coding_exception(
                'A Commerce orchestration exception code cannot be empty.'
            );
        }

        parent::__construct(
            $message,
            0,
            $previous
        );
    }

    public function get_orchestration_code(): string {
        return trim(
            $this->orchestrationcode
        );
    }

    public function get_request_reference(): ?string {
        if ($this->requestreference === null) {
            return null;
        }

        $reference = trim(
            $this->requestreference
        );

        return $reference !== ''
            ? $reference
            : null;
    }

    public function get_provider_key(): ?string {
        if ($this->providerkey === null) {
            return null;
        }

        $providerkey = strtolower(
            trim($this->providerkey)
        );

        return $providerkey !== ''
            ? $providerkey
            : null;
    }

    public function get_context(): array {
        return $this->context;
    }
}