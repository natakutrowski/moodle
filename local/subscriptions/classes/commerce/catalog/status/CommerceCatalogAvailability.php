<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\status;

defined('MOODLE_INTERNAL') || die();

final class CommerceCatalogAvailability {
    public const ON_SALE = 'on_sale';
    public const UPCOMING = 'upcoming';
    public const UNAVAILABLE = 'unavailable';
    public const ENDED = 'ended';

    private const ALL = [self::ON_SALE, self::UPCOMING, self::UNAVAILABLE, self::ENDED];

    private function __construct() {
    }

    public static function all(): array {
        return self::ALL;
    }
}
