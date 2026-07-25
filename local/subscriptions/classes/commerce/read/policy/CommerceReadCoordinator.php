<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\policy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\read\service\CommercePurchaseReadService;
use local_subscriptions\commerce\read\shadow\CommerceReadShadowComparator;

/** Native-first coordinator with explicit and measurable legacy fallback. */
final class CommerceReadCoordinator {
    public function __construct(
        private readonly CommercePurchaseReadService $native,
        private readonly CommerceReadPolicy $policy,
        private readonly CommerceReadShadowComparator $comparator
    ) {
    }

    public function read_purchase(string $consumer, string $family, int $legacyid): CommerceReadDecision {
        if (!$this->policy->is_native_enabled($consumer)) {
            return $this->legacy_decision($family, $legacyid);
        }

        $native = $this->native->find_by_legacy_reference($family, $legacyid);
        if ($native === null) {
            return $this->policy->is_legacy_fallback_enabled()
                ? $this->legacy_decision($family, $legacyid)
                : new CommerceReadDecision(CommerceReadDecision::SOURCE_NONE, null);
        }

        if (!$this->policy->is_shadow_enabled()) {
            return new CommerceReadDecision(CommerceReadDecision::SOURCE_NATIVE, $native);
        }

        $legacy = $this->load_legacy($family, $legacyid);
        if ($legacy === null) {
            return new CommerceReadDecision(CommerceReadDecision::SOURCE_NATIVE, $native, true);
        }

        $comparison = $this->comparator->compare($legacy, $native);
        return new CommerceReadDecision(
            CommerceReadDecision::SOURCE_NATIVE,
            $native,
            true,
            $comparison->highest_severity(),
            $comparison->differences
        );
    }

    private function legacy_decision(string $family, int $legacyid): CommerceReadDecision {
        $legacy = $this->load_legacy($family, $legacyid);
        return new CommerceReadDecision(
            $legacy === null ? CommerceReadDecision::SOURCE_NONE : CommerceReadDecision::SOURCE_LEGACY_FALLBACK,
            $legacy
        );
    }

    private function load_legacy(string $family, int $legacyid): mixed {
        $source = CommerceLegacyMigrationFactory::create_source_registry()->get($family);
        $purchase = $source->get_by_id($legacyid);
        return $purchase === null ? null : (new CommerceLegacySnapshotFactory())->create($purchase);
    }
}
