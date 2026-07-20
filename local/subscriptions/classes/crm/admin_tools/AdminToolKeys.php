<?php

namespace local_subscriptions\crm\admin_tools;

defined('MOODLE_INTERNAL') || die();

/**
 * Stable identifiers for CRM administrative tools.
 */
final class AdminToolKeys {

    public const INBOX_SYNC = 'inbox_sync';

    public const INBOX_DIAGNOSTICS =
        'inbox_diagnostics';

    public const AUTOMATIONS = 'automations';

    public const INTELLIGENCE_SNAPSHOT =
        'intelligence_snapshot';

    public const RECOMMENDATIONS =
        'recommendations';

    public const DIGITAL_RECONCILIATION =
        'digital_reconciliation';

    public const HELP_VALIDATION =
        'help_validation';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::INBOX_SYNC,
            self::INBOX_DIAGNOSTICS,
            self::AUTOMATIONS,
            self::INTELLIGENCE_SNAPSHOT,
            self::RECOMMENDATIONS,
            self::DIGITAL_RECONCILIATION,
            self::HELP_VALIDATION,
        ];
    }

    public static function is_valid(
        string $key
    ): bool {
        return in_array(
            $key,
            self::all(),
            true
        );
    }
}