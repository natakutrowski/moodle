<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\status;

defined('MOODLE_INTERNAL') || die();

final class CommerceCatalogTechnicalState {
    public const VALID = 'valid';
    public const INCOMPLETE = 'incomplete';
    public const ERROR = 'error';

    private const ALL = [self::VALID, self::INCOMPLETE, self::ERROR];

    private function __construct() {
    }

    public static function all(): array {
        return self::ALL;
    }
}
