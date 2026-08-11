<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\status;

defined('MOODLE_INTERNAL') || die();

final class CommerceCatalogVisibility {
    public const VISIBLE = 'visible';
    public const HIDDEN = 'hidden';
    public const DIRECT_LINK = 'direct_link';

    private const ALL = [self::VISIBLE, self::HIDDEN, self::DIRECT_LINK];

    private function __construct() {
    }

    public static function all(): array {
        return self::ALL;
    }

    public static function require_valid(string $visibility): string {
        $visibility = strtolower(trim($visibility));
        if (!in_array($visibility, self::ALL, true)) {
            throw new \coding_exception('Unsupported catalogue visibility: ' . $visibility);
        }
        return $visibility;
    }
}
