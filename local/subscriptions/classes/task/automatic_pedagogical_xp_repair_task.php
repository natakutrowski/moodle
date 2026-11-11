<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

/** Separate, default-off task. Never grants points when cron disabled or in simulation. */
final class automatic_pedagogical_xp_repair_task extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('commerce_xp_auto_task', 'local_subscriptions');
    }

    public function execute(): void {
        $stats = \local_subscriptions\commerce\education\xp\CommerceXpAutomaticRepairService::create()->run();
        mtrace('CampusFR XP AUTO ' . json_encode($stats));
    }
}
