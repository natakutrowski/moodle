<?php

namespace local_subscriptions\commerce\payment\legacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when a Commerce payment cannot be mapped safely to a Legacy request.
 */
final class LegacyPaymentRequestMappingException
    extends \RuntimeException {

    public function __construct(
        string $message,
        private readonly string $mappingcode,
        private readonly array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            0,
            $previous
        );
    }

    public function get_mapping_code(): string {
        return $this->mappingcode;
    }

    public function get_context(): array {
        return $this->context;
    }
}