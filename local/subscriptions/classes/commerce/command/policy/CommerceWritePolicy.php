<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\command\policy;

defined('MOODLE_INTERNAL') || die();

final class CommerceWritePolicy {
    public function native_dual_write_enabled(string $consumer): bool {
        $setting = $consumer === 'task'
            ? 'commerce_native_task_dual_write_enabled'
            : 'commerce_native_dual_write_enabled';

        return !empty(get_config('local_subscriptions', $setting));
    }

    public function shadow_compare_enabled(): bool {
        return !empty(get_config('local_subscriptions', 'commerce_native_shadow_write_compare_enabled'));
    }
}
