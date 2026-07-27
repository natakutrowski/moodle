<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/** G6 stable business classification for Shadow comparisons. */
final class CommerceShadowDivergenceClassifier {
    public const MATCH = 'match';
    public const REPRESENTATION_ONLY = 'representation_only';
    public const BUSINESS_DIFFERENCE = 'business_difference';
    public const NOT_COMPARABLE = 'not_comparable';
    public const SHADOW_FAILURE = 'shadow_failure';

    public function classify(CommerceShadowComparison $comparison): string {
        return match ($comparison->get_status()) {
            CommerceShadowComparison::EQUAL => self::MATCH,
            CommerceShadowComparison::EQUIVALENT => self::REPRESENTATION_ONLY,
            CommerceShadowComparison::DIFFERENT => self::BUSINESS_DIFFERENCE,
            CommerceShadowComparison::NOT_COMPARABLE => self::NOT_COMPARABLE,
            CommerceShadowComparison::SHADOW_ERROR => self::SHADOW_FAILURE,
            default => throw new \coding_exception('Unknown Commerce Shadow comparison status.'),
        };
    }
}
