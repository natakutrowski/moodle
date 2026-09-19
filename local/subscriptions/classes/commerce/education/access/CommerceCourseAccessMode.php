<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\education\access;

defined('MOODLE_INTERNAL') || die();

/**
 * Technical access mode configured for a Moodle course.
 *
 * Kept separate from local_subscriptions\commerce\promotion, which remains
 * the commercial discount/pricing bounded context certified in 7.96.
 */
final class CommerceCourseAccessMode {
    public const CLASSIC_IMMEDIATE = 'classic_immediate';
    public const PROMOTION = 'promotion';

    public static function all(): array {
        return [self::CLASSIC_IMMEDIATE, self::PROMOTION];
    }

    public static function default(): string {
        return self::CLASSIC_IMMEDIATE;
    }

    public static function normalise(?string $mode): string {
        $mode = strtolower(trim((string)$mode));
        if ($mode === '') {
            return self::default();
        }
        if (!in_array($mode, self::all(), true)) {
            throw new \coding_exception('Unsupported Commerce course access mode: ' . $mode);
        }
        return $mode;
    }

    public static function is_promotion(string $mode): bool {
        return self::normalise($mode) === self::PROMOTION;
    }
}
