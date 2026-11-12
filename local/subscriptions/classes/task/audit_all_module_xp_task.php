<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

/** Observation only; independent from all repair/replay workers. */
final class audit_all_module_xp_task extends \core\task\scheduled_task {
    public function get_name(): string {
        return 'CampusFR : audit XP transversal de toutes les activites (sans attribution)';
    }

    public function execute(): void {
        $stats = \local_subscriptions\commerce\education\xp\CommerceXpAllModuleCompletionAudit::create()->run();
        mtrace('CampusFR XP V4 AUDIT ' . json_encode($stats));
    }
}
