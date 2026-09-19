<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\currency;
defined('MOODLE_INTERNAL') || die();
final class CommerceFxRefreshBatch {
    /** @param array<string,array{rate:string,source:string,referencedate:string}> $entries @param string[] $unavailable */
    public function __construct(public readonly string $basecurrency, public readonly array $entries, public readonly array $unavailable) {}
}
