<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Statuses reserved for the persistent transactional mail queue introduced later.
 */
final class CommerceMailStatus {

    public const QUEUED = 'queued';
    public const PROCESSING = 'processing';
    public const SENT = 'sent';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::QUEUED,
            self::PROCESSING,
            self::SENT,
            self::FAILED,
            self::CANCELLED,
        ];
    }

    public static function normalise(string $status): string {
        $status = strtolower(trim($status));

        if (!in_array($status, self::all(), true)) {
            throw new \coding_exception(
                'Unsupported Commerce transactional mail status: ' . $status
            );
        }

        return $status;
    }

    private function __construct() {
    }
}
