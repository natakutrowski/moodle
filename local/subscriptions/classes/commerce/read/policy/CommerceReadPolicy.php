<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\policy;

defined('MOODLE_INTERNAL') || die();

/** Reads I10C feature flags without leaking Moodle configuration to consumers. */
final class CommerceReadPolicy {
    public function is_native_enabled(string $consumer): bool {
        $this->assert_consumer($consumer);
        return !empty(get_config('local_subscriptions', 'commerce_native_' . $consumer . '_reads_enabled'));
    }

    public function is_shadow_enabled(): bool {
        return !empty(get_config('local_subscriptions', 'commerce_native_shadow_compare_enabled'));
    }

    public function is_legacy_fallback_enabled(): bool {
        $value = get_config('local_subscriptions', 'commerce_native_legacy_fallback_enabled');
        return $value === false || $value === '' ? true : !empty($value);
    }

    private function assert_consumer(string $consumer): void {
        if (!in_array($consumer, CommerceReadConsumer::all(), true)) {
            throw new \InvalidArgumentException('Unknown Commerce read consumer: ' . $consumer);
        }
    }
}
