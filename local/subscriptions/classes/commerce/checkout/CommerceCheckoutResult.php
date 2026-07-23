<?php

namespace local_subscriptions\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent checkout initialization result.
 */
final class CommerceCheckoutResult {

    public const ENGINE_LEGACY = 'legacy';
    public const ENGINE_COMMERCE = 'commerce';

    public function __construct(
        private readonly string $engine,
        private readonly string $redirecturl,
        private readonly ?string $providerpaymentid = null,
        private readonly array $metadata = []
    ) {
        if (!in_array($engine, [self::ENGINE_LEGACY, self::ENGINE_COMMERCE], true)) {
            throw new \coding_exception('Unsupported checkout engine: ' . $engine);
        }

        if (trim($redirecturl) === '') {
            throw new \coding_exception('A checkout redirect URL is required.');
        }
    }

    public function get_engine(): string {
        return $this->engine;
    }

    public function get_redirect_url(): string {
        return trim($this->redirecturl);
    }

    public function get_provider_payment_id(): ?string {
        $value = trim((string)$this->providerpaymentid);
        return $value !== '' ? $value : null;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function is_commerce(): bool {
        return $this->engine === self::ENGINE_COMMERCE;
    }
}
