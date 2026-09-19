<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\education\access;

defined('MOODLE_INTERNAL') || die();

/**
 * Individual pedagogical access profile for one student/course relation.
 *
 * This is intentionally individual: no global Moodle course restriction may
 * prevent historical/full-access students from coexisting with promotion users.
 */
final class CommerceStudentAccessProfile {
    public const LEGACY_FULL = 'legacy_full';
    public const PROMOTION_PROGRESSIVE = 'promotion_progressive';
    public const LIFETIME_FULL = 'lifetime_full';

    public static function all(): array {
        return [
            self::LEGACY_FULL,
            self::PROMOTION_PROGRESSIVE,
            self::LIFETIME_FULL,
        ];
    }

    public static function normalise(string $profile): string {
        $profile = strtolower(trim($profile));
        if (!in_array($profile, self::all(), true)) {
            throw new \coding_exception('Unsupported Commerce student access profile: ' . $profile);
        }
        return $profile;
    }

    public static function has_full_course_access(string $profile): bool {
        return in_array(
            self::normalise($profile),
            [self::LEGACY_FULL, self::LIFETIME_FULL],
            true
        );
    }

    public static function is_progressive(string $profile): bool {
        return self::normalise($profile) === self::PROMOTION_PROGRESSIVE;
    }
}
