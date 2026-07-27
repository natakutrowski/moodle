<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\application;

defined('MOODLE_INTERNAL') || die();

/**
 * Runtime context supplied while applying Native Commerce entitlements.
 */
final class CommerceEntitlementApplicationContext {
    public function __construct(
        private readonly string $transactionid,
        private readonly string $provider,
        private readonly int $appliedat,
        private readonly array $metadata = []
    ) {
        if (trim($transactionid) === '') {
            throw new \coding_exception('An entitlement application transaction identifier is required.');
        }

        if (trim($provider) === '') {
            throw new \coding_exception('An entitlement application provider is required.');
        }

        if ($appliedat <= 0) {
            throw new \coding_exception('An entitlement application timestamp must be positive.');
        }
    }

    public function get_transaction_id(): string {
        return trim($this->transactionid);
    }

    public function get_provider(): string {
        return strtolower(trim($this->provider));
    }

    public function get_applied_at(): int {
        return $this->appliedat;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(string $key, mixed $default = null): mixed {
        return $this->metadata[$key] ?? $default;
    }
}
