<?php

namespace local_subscriptions\task;

use core\task\scheduled_task;
use local_subscriptions\crm\automation\AutomationCronRunner;

defined('MOODLE_INTERNAL') || die();

final class run_crm_automations_task extends scheduled_task {

    public function get_name(): string {
        return get_string('task_run_crm_automations', 'local_subscriptions');
    }

    public function execute(): void {
        $runner = new AutomationCronRunner();
        $runner->run();
    }
}