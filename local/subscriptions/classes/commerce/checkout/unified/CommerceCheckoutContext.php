<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

/** Immutable customer and navigation context for one checkout attempt. */
final class CommerceCheckoutContext {
    public function __construct(
        private readonly int $customerid,
        private readonly string $currency,
        private readonly string $language,
        private readonly string $provider,
        private readonly string $returnurl,
        private readonly string $cancelurl,
        private readonly bool $live = true,
        private readonly array $metadata = []
    ) {
        $preview = !empty($metadata['checkout_preview']);
        if ($customerid < 0 || ($customerid === 0 && !$preview)) {
            throw new \coding_exception('Unified checkout requires an authenticated customer outside preview mode.');
        }
        if (!preg_match('/^[A-Z]{3}$/', strtoupper(trim($currency)))) {
            throw new \coding_exception('Unified checkout currency must use ISO 4217 format.');
        }
        if (trim($language) === '' || trim($provider) === '') {
            throw new \coding_exception('Unified checkout language and provider are required.');
        }
        if (trim($returnurl) === '' || trim($cancelurl) === '') {
            throw new \coding_exception('Unified checkout return and cancel URLs are required.');
        }
    }

    public function get_customer_id(): int { return $this->customerid; }
    public function get_currency(): string { return strtoupper(trim($this->currency)); }
    public function get_language(): string { return trim($this->language); }
    public function get_provider(): string { return strtolower(trim($this->provider)); }
    public function get_return_url(): string { return trim($this->returnurl); }
    public function get_cancel_url(): string { return trim($this->cancelurl); }
    public function is_live(): bool { return $this->live; }
    public function get_metadata(): array { return $this->metadata; }
}
