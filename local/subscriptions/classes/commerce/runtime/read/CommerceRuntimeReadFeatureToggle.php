<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\read;

defined('MOODLE_INTERNAL') || die();

/** Runtime configuration for I7 reads. */
final class CommerceRuntimeReadFeatureToggle {
    public function __construct(
        private readonly ?string $modeoverride = null,
        private readonly ?bool $strictoverride = null
    ) {
    }

    public function get_mode(): string {
        $mode = $this->modeoverride ?? (string)get_config('local_subscriptions', 'commerce_runtime_read_mode');
        if ($mode === '') {
            $mode = CommerceRuntimeReadMode::LEGACY;
        }
        return CommerceRuntimeReadMode::normalise($mode);
    }

    public function is_strict(): bool {
        if ($this->strictoverride !== null) {
            return $this->strictoverride;
        }
        return !empty(get_config('local_subscriptions', 'commerce_runtime_read_strict'));
    }
}
