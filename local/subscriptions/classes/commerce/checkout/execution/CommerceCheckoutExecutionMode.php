<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\execution;

defined('MOODLE_INTERNAL') || die();

/**
 * Customer-facing execution mode, independent from the payment provider.
 */
final class CommerceCheckoutExecutionMode {
    public const PROVIDER_HOSTED = 'provider_hosted';
    public const CAMPUS_EMBEDDED = 'campus_embedded';

    public static function is_known(string $mode): bool {
        return in_array(
            strtolower(trim($mode)),
            [
                self::PROVIDER_HOSTED,
                self::CAMPUS_EMBEDDED,
            ],
            true
        );
    }

    private function __construct() {
    }
}
