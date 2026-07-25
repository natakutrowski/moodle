<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read;

defined('MOODLE_INTERNAL') || die();

/** Feature flags for I6 native Commerce shadow reads. */
final class CommerceNativeReadFeatureToggle {
    public function is_enabled(): bool {
        return !empty(get_config('local_subscriptions', 'commerce_native_read_shadow_enabled'));
    }

    public function is_strict(): bool {
        return !empty(get_config('local_subscriptions', 'commerce_native_read_shadow_strict'));
    }
}
