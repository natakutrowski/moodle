<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\editing;

defined('MOODLE_INTERNAL') || die();

/** Describes the safe write boundary for federated catalogue records. */
final class CommerceCatalogCompatibilityEditor {
    public function is_native_editable(string $origin): bool {
        return $origin === 'native';
    }

    public function legacy_edit_url(string $origin, int $id): ?\moodle_url {
        return match ($origin) {
            'legacy_plan' => \local_subscriptions\commerce\catalog\navigation\CommerceLegacyCatalogLinkGenerator::plan_edit_url($id),
            'legacy_digital' => \local_subscriptions\commerce\catalog\navigation\CommerceLegacyCatalogLinkGenerator::digital_edit_url($id),
            default => null,
        };
    }
}
