<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\pricing;

defined('MOODLE_INTERNAL') || die();

/** Supported Bundle pricing strategies. */
final class CommerceBundlePricingStrategy {
    public const FIXED = 'fixed';
    public const COMPONENT_SUM = 'component_sum';
    public const PERCENTAGE_DISCOUNT = 'percentage_discount';

    public static function all(): array {
        return [
            self::FIXED,
            self::COMPONENT_SUM,
            self::PERCENTAGE_DISCOUNT,
        ];
    }

    public static function require_valid(string $strategy): string {
        $strategy = strtolower(trim($strategy));

        if (!in_array($strategy, self::all(), true)) {
            throw new \coding_exception('Unknown Commerce bundle pricing strategy: ' . $strategy);
        }

        return $strategy;
    }
}
