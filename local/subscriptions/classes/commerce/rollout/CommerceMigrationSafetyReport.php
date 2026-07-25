<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\rollout;

defined('MOODLE_INTERNAL') || die();

final class CommerceMigrationSafetyReport {
    public function __construct(
        private readonly bool $legacytablesavailable,
        private readonly bool $canonicalflagavailable,
        private readonly bool $legacyaliasavailable,
        private readonly bool $upgradebridgeavailable,
        private readonly bool $fallbackenabled,
        private readonly array $notes = []
    ) {
    }

    public function is_safe_for_preprod(): bool {
        return $this->legacytablesavailable
            && $this->canonicalflagavailable
            && $this->legacyaliasavailable
            && $this->upgradebridgeavailable
            && $this->fallbackenabled;
    }

    public function to_array(): array {
        return [
            'legacy_tables_available' => $this->legacytablesavailable,
            'canonical_flag_available' => $this->canonicalflagavailable,
            'legacy_alias_available' => $this->legacyaliasavailable,
            'upgrade_bridge_available' => $this->upgradebridgeavailable,
            'legacy_fallback_enabled' => $this->fallbackenabled,
            'notes' => $this->notes,
        ];
    }
}
