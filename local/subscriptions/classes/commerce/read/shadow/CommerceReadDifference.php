<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\shadow;

defined('MOODLE_INTERNAL') || die();

final class CommerceReadDifference {
    public const INFO = 'info';
    public const EXPECTED = 'expected';
    public const WARNING = 'warning';
    public const CRITICAL = 'critical';

    public function __construct(
        public readonly string $field,
        public readonly mixed $legacyvalue,
        public readonly mixed $nativevalue,
        public readonly string $severity
    ) {
    }
}
