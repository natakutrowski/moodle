<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\rollback;

use local_subscriptions\commerce\runtime\switching\CommerceRuntimeConfiguration;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode;

/**
 * Performs a guarded Commerce runtime rollback without touching Commerce data.
 */
final class CommerceRuntimeRollbackService {
    public function __construct(
        private readonly CommerceRuntimeConfiguration $configuration = new CommerceRuntimeConfiguration(),
    ) {
    }

    /**
     * Returns the current state and the state expected after rollback.
     *
     * @return array<string, mixed>
     */
    public function inspect(): array {
        return [
            'before' => $this->current_state(),
            'target' => [
                'runtime_mode' => CommerceRuntimeMode::LEGACY,
                'native_fallback_enabled' => true,
                'shadow_enabled' => false,
            ],
            'data_changes' => false,
        ];
    }

    /**
     * Applies and verifies the rollback configuration.
     *
     * @return array<string, mixed>
     */
    public function execute(): array {
        $before = $this->current_state();

        $this->configuration->set_mode(CommerceRuntimeMode::LEGACY);
        set_config('commerce_runtime_native_fallback_enabled', 1, 'local_subscriptions');

        $after = $this->current_state();
        $verified = $after['runtime_mode'] === CommerceRuntimeMode::LEGACY
            && $after['native_fallback_enabled'] === true
            && $after['shadow_enabled'] === false;

        if (!$verified) {
            throw new \RuntimeException('Commerce runtime rollback verification failed.');
        }

        return [
            'before' => $before,
            'after' => $after,
            'verified' => true,
            'data_changes' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function current_state(): array {
        return [
            'runtime_mode' => $this->configuration->get_mode(),
            'native_fallback_enabled' => $this->configuration->native_fallback_enabled(),
            'shadow_enabled' => (bool) get_config('local_subscriptions', 'commerce_fulfillment_shadow_enabled'),
        ];
    }
}
