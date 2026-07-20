<?php

namespace local_subscriptions\crm\admin_tools;

defined('MOODLE_INTERNAL') || die();

/**
 * Supported administrative tool execution statuses.
 */
final class AdminToolStatuses {

    public const RUNNING = 'running';

    public const SUCCESS = 'success';

    public const FAILED = 'failed';

    public const BUSY = 'busy';

    public const CANCELLED = 'cancelled';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::RUNNING,
            self::SUCCESS,
            self::FAILED,
            self::BUSY,
            self::CANCELLED,
        ];
    }

    public static function is_valid(
        string $status
    ): bool {
        return in_array(
            $status,
            self::all(),
            true
        );
    }
}