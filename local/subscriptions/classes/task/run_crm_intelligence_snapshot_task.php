<?php

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\crm\intelligence\history\CrmScoreSnapshotRunner;

final class run_crm_intelligence_snapshot_task extends scheduled_task {

    public function get_name(): string {
        return get_string('task_run_crm_intelligence_snapshot', 'local_subscriptions');
    }

    public function execute(): void {
        $runner = new CrmScoreSnapshotRunner();
        $count = $runner->run();

        mtrace('CRM intelligence snapshots created: ' . $count);
    }
}