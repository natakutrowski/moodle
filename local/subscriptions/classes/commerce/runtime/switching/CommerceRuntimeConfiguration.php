<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\switching;

defined('MOODLE_INTERNAL') || die();

final class CommerceRuntimeConfiguration {
    public function get_mode(): string {
        return CommerceRuntimeMode::normalize((string) get_config('local_subscriptions', 'commerce_runtime_mode'));
    }

    public function native_fallback_enabled(): bool {
        return (bool) get_config('local_subscriptions', 'commerce_runtime_native_fallback_enabled');
    }

    public function set_mode(string $mode): void {
        $mode = CommerceRuntimeMode::normalize($mode);
        set_config('commerce_runtime_mode', $mode, 'local_subscriptions');
        set_config('commerce_fulfillment_shadow_enabled', $mode === CommerceRuntimeMode::SHADOW ? 1 : 0, 'local_subscriptions');
    }
}
