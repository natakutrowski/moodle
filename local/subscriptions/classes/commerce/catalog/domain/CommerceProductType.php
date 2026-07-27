<?php

namespace local_subscriptions\commerce\catalog\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Known Commerce catalogue product types.
 *
 * Product types remain strings at persistence and integration boundaries so
 * that the catalogue can be extended without changing the database schema.
 */
final class CommerceProductType {

    public const COURSE_ACCESS = 'course_access';
    public const DIGITAL_DOWNLOAD = 'digital_download';
    public const BUNDLE = 'bundle';
    public const SERVICE = 'service';

    private const KNOWN_TYPES = [
        self::COURSE_ACCESS,
        self::DIGITAL_DOWNLOAD,
        self::BUNDLE,
        self::SERVICE,
    ];

    private function __construct() {
    }

    public static function normalise(string $type): string {
        return strtolower(trim($type));
    }

    public static function require_valid(string $type): string {
        $type = self::normalise($type);

        if ($type === '' || !preg_match('/^[a-z][a-z0-9_]{1,63}$/', $type)) {
            throw new \coding_exception('A Commerce product type must be a valid machine identifier.');
        }

        return $type;
    }

    public static function is_known(string $type): bool {
        return in_array(self::normalise($type), self::KNOWN_TYPES, true);
    }

    public static function all_known(): array {
        return self::KNOWN_TYPES;
    }
}
