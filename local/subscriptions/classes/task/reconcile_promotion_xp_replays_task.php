<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

/** Reconcile eligible new submissions with historical Level Up rewards. */
final class reconcile_promotion_xp_replays_task extends \core\task\scheduled_task {
    public function get_name(): string {
        return 'CampusFR: reconcile completed promotion XP replays';
    }

    public function execute(): void {
        $stats = \local_subscriptions\commerce\education\xp\CommercePedagogicalAttemptReplayReconciler::create()->run();
        mtrace(sprintf(
            'CampusFR XP replays: examined=%d created=%d existing=%d skipped=%d',
            $stats['examined'], $stats['created'], $stats['existing'], $stats['skipped']
        ));
    }
}
