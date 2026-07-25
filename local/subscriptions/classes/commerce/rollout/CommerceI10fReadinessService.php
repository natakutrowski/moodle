<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\rollout;

defined('MOODLE_INTERNAL') || die();

final class CommerceI10fReadinessService {
    public function build(string $pluginroot): CommerceI10fReadinessReport {
        $inventory = new CommerceRuntimeWriteInventory();
        $findings = $inventory->scan($pluginroot);
        $counts = $inventory->count_by_classification($findings);

        $safety = (new CommerceMigrationSafetyInspector())->inspect($pluginroot);

        $state = (new CommerceRolloutGuard())->state();
        $flagissues = (new CommerceRolloutStageEvaluator())->violations($state);

        return new CommerceI10fReadinessReport(
            $counts,
            $safety,
            $flagissues
        );
    }
}
