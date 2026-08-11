<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\status;

defined('MOODLE_INTERNAL') || die();

final class CommerceCatalogEditorialStatus {
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';
    public const ARCHIVED = 'archived';

    private const ALL = [self::DRAFT, self::PUBLISHED, self::ARCHIVED];

    private function __construct() {
    }

    public static function all(): array {
        return self::ALL;
    }

    public static function require_valid(string $status): string {
        $status = strtolower(trim($status));
        if (!in_array($status, self::ALL, true)) {
            throw new \coding_exception('Unsupported catalogue editorial status: ' . $status);
        }
        return $status;
    }
}
