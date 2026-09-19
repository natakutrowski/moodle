<?php

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

/**
 * Alfa order registered server-side for a direct/fast payment method.
 *
 * H11 consumers (Alfa Pay, SBP, etc.) need the provider order id without
 * depending on the historical hosted-form redirect flow. The form URL is
 * retained as a safe fallback and for diagnostics, but no secret is exposed.
 */
final class AlfaRegisteredOrder {

    public function __construct(
        private readonly string $orderid,
        private readonly ?string $formurl = null,
        private readonly array $metadata = []
    ) {
        if (trim($orderid) === '') {
            throw new \coding_exception(
                'An Alfa registered order identifier cannot be empty.'
            );
        }
    }

    public function get_order_id(): string {
        return trim($this->orderid);
    }

    public function get_form_url(): ?string {
        if ($this->formurl === null) {
            return null;
        }

        $value = trim($this->formurl);
        return $value !== '' ? $value : null;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key] ?? $default;
    }
}
