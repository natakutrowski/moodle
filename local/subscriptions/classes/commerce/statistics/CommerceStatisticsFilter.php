<?php

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Optional Native Commerce statistics filters. */
final class CommerceStatisticsFilter {
    public function __construct(
        private readonly ?string $currency = null,
        private readonly ?string $provider = null,
        private readonly ?string $productreference = null
    ) {
        if ($currency !== null && !preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \coding_exception('Commerce statistics currency must be an ISO 4217 code.');
        }
    }

    public static function all(): self {
        return new self();
    }

    public function currency(): ?string {
        return $this->currency;
    }

    public function provider(): ?string {
        return $this->provider;
    }

    public function product_reference(): ?string {
        return $this->productreference;
    }
}
