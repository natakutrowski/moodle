<?php

namespace local_subscriptions\commerce\catalog\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Lifecycle statuses for products in the Native Commerce catalogue.
 */
final class CommerceProductStatus {

    public const DRAFT = 'draft';
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const ARCHIVED = 'archived';

    private const VALID_STATUSES = [
        self::DRAFT,
        self::ACTIVE,
        self::INACTIVE,
        self::ARCHIVED,
    ];

    private function __construct() {
    }

    public static function require_valid(string $status): string {
        $status = strtolower(trim($status));

        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new \coding_exception('Unsupported Commerce product status: ' . $status);
        }

        return $status;
    }

    public static function all(): array {
        return self::VALID_STATUSES;
    }
}
