<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\rollout;
defined('MOODLE_INTERNAL') || die();
final class CommerceRolloutGuard {
    public function assert_safe_configuration(): void {
        $repair = !empty(get_config('local_subscriptions', 'commerce_native_repair_enabled'));
        $reconciliation = !empty(get_config('local_subscriptions', 'commerce_native_reconciliation_enabled'));
        if ($repair && !$reconciliation) {
            throw new \RuntimeException('Native repair cannot be enabled while reconciliation is disabled.');
        }
    }
    public function state(): array {
        return [
            'runtime_dual_write' => !empty(get_config('local_subscriptions', 'commerce_native_dual_write_enabled')) || !empty(get_config('local_subscriptions', 'commerce_dual_write_enabled')),
            'shadow_compare' => !empty(get_config('local_subscriptions', 'commerce_native_shadow_write_compare_enabled')),
            'reconciliation' => !empty(get_config('local_subscriptions', 'commerce_native_reconciliation_enabled')),
            'repair' => !empty(get_config('local_subscriptions', 'commerce_native_repair_enabled')),
            'task_dual_write' => !empty(get_config('local_subscriptions', 'commerce_native_task_dual_write_enabled')),
        ];
    }
}
