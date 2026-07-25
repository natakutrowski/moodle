<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\dualwrite;

defined('MOODLE_INTERNAL') || die();

/**
 * Central feature toggle for the transitional Legacy -> Native synchronisation.
 *
 * I10E makes commerce_native_dual_write_enabled the canonical runtime flag.
 * The historical commerce_dual_write_enabled flag remains accepted as a
 * compatibility alias during the rollout so an existing environment cannot be
 * silently disabled by the configuration rename.
 */
final class CommerceDualWriteFeatureToggle {
    public const CANONICAL_FLAG = 'commerce_native_dual_write_enabled';
    public const LEGACY_FLAG = 'commerce_dual_write_enabled';
    public const STRICT_FLAG = 'commerce_dual_write_strict';

    public function is_enabled(): bool {
        return $this->canonical_enabled() || $this->legacy_alias_enabled();
    }

    public function effective_source(): string {
        if ($this->canonical_enabled()) {
            return 'canonical';
        }

        if ($this->legacy_alias_enabled()) {
            return 'legacy_alias';
        }

        return 'disabled';
    }

    public function is_strict(): bool {
        return $this->is_flag_enabled(self::STRICT_FLAG);
    }

    public function canonical_enabled(): bool {
        return $this->is_flag_enabled(self::CANONICAL_FLAG);
    }

    public function legacy_alias_enabled(): bool {
        return $this->is_flag_enabled(self::LEGACY_FLAG);
    }

    public function has_configuration_mismatch(): bool {
        return $this->canonical_enabled() !== $this->legacy_alias_enabled();
    }

    private function is_flag_enabled(string $name): bool {
        return !empty(get_config('local_subscriptions', $name));
    }
}
