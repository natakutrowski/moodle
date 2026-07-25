<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\command;

defined('MOODLE_INTERNAL') || die();

final class CommerceCommandStatus {
    public const DISABLED = 'disabled';
    public const CREATED = 'created';
    public const UPDATED = 'updated';
    public const UNCHANGED = 'unchanged';
    public const SKIPPED = 'skipped';
    public const FAILED = 'failed';

    private function __construct() {
    }
}
