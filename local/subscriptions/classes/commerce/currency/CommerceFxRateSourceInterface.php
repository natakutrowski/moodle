<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\currency;
defined('MOODLE_INTERNAL') || die();

interface CommerceFxRateSourceInterface {
    public function key(): string;
    public function label(): string;
    /** @param string[] $targetcurrencies */
    public function refresh(string $basecurrency, array $targetcurrencies): CommerceFxRateRefreshResult;
}
