<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

/** Audit only. Never writes Level Up or promotion XP. */
final class audit_pedagogical_xp_task extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('commerce_xp_audit_task', 'local_subscriptions');
    }

    public function execute(): void {
        $stats = \local_subscriptions\commerce\education\xp\CommerceXpAuditReconciler::create()->run();
        mtrace('CampusFR XP AUDIT ONLY: ' . json_encode($stats));
    }
}
