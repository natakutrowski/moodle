<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\currency;
defined('MOODLE_INTERNAL') || die();

final class CommerceFxRateRefreshResult {
    /** @param array<string,string> $rates @param string[] $unavailable */
    public function __construct(
        public readonly string $source,
        public readonly string $basecurrency,
        public readonly array $rates,
        public readonly array $unavailable,
        public readonly string $referencedate
    ) {}
}
