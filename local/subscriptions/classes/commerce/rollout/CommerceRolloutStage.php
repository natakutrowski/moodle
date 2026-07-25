<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\rollout;
defined('MOODLE_INTERNAL') || die();
final class CommerceRolloutStage {
    public const BASELINE = 'baseline'; public const SHADOW = 'shadow'; public const RUNTIME = 'runtime';
    public const TASKS = 'tasks'; public const RECONCILIATION = 'reconciliation'; public const REPAIR = 'repair';
    public static function ordered(): array { return [self::BASELINE, self::SHADOW, self::RUNTIME, self::TASKS, self::RECONCILIATION, self::REPAIR]; }
}
