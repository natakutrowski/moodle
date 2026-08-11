<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\library;

defined('MOODLE_INTERNAL') || die();

/** Stable business origins used to enrich Moodle course enrolments. */
final class CommerceCourseAccessOrigin {
    public const PURCHASE = 'purchase';
    public const GIFT = 'gift';
    public const TRIAL = 'trial';
    public const ADMIN = 'admin';
    public const UNKNOWN = 'unknown';

    /** @return string[] */
    public static function all(): array {
        return [self::PURCHASE, self::GIFT, self::TRIAL, self::ADMIN, self::UNKNOWN];
    }

    public static function priority(string $origin): int {
        return match ($origin) {
            self::PURCHASE => 500,
            self::GIFT => 400,
            self::TRIAL => 300,
            self::ADMIN => 200,
            default => 100,
        };
    }

    private function __construct() {
    }
}
