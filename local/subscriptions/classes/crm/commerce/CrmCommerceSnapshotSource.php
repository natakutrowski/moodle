<?php

namespace local_subscriptions\crm\commerce;

defined('MOODLE_INTERNAL') || die();

/**
 * Indicates which source produced a CRM Commerce snapshot.
 */
final class CrmCommerceSnapshotSource {

    public const COMMERCE_DOMAIN = 'commerce_domain';
    public const LEGACY_FALLBACK = 'legacy_fallback';

    private const VALID_VALUES = [
        self::COMMERCE_DOMAIN,
        self::LEGACY_FALLBACK,
    ];

    public static function is_valid(
        string $source
    ): bool {
        return in_array(
            $source,
            self::VALID_VALUES,
            true
        );
    }
}