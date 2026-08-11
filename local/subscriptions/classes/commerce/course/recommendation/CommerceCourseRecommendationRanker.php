<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\recommendation;

defined('MOODLE_INTERNAL') || die();

/** Assigns a stable relevance score to learner course recommendations. */
final class CommerceCourseRecommendationRanker {
    public const TRIAL_GRAMMAR_SCORE = 15000;
    public const TRIAL_FULL_SCORE = 12000;
    public const UPGRADE_SCORE = 10000;
    public const GUSTAVE_CHOICE_SCORE = 5000;
    public const FEATURED_SCORE = 1000;
    public const COURSE_SCORE = 200;
    public const BUNDLE_SCORE = 100;

    /** @param string[] $badges */
    public function score(
        bool $upgrade,
        array $badges,
        bool $featured,
        string $type,
        bool $trialoffer = false,
        string $trialaccesslevel = ''
    ): int {
        $score = 0;

        if ($trialoffer) {
            $score += $trialaccesslevel === 'grammar'
                ? self::TRIAL_GRAMMAR_SCORE
                : self::TRIAL_FULL_SCORE;
        }
        // Bundles are merchandising products, never subscription upgrades.
        // Ignore an accidental upgrade flag defensively.
        if ($upgrade && $type !== 'bundle') {
            $score += self::UPGRADE_SCORE;
        }
        if (in_array('gustave_choice', $badges, true)) {
            $score += self::GUSTAVE_CHOICE_SCORE;
        }
        if ($featured) {
            $score += self::FEATURED_SCORE;
        }

        $score += match ($type) {
            'course_access', 'subscription' => self::COURSE_SCORE,
            'bundle' => self::BUNDLE_SCORE,
            default => 0,
        };

        return $score;
    }
}
