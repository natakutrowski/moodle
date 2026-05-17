<?php
namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\digital\digital_payment_reconciler;

class reconcile_digital_payments_task extends scheduled_task {

    public function get_name(): string {
        return get_string('task_reconcile_digital_payments', 'local_subscriptions');
    }

    public function execute(): void {
        $result = digital_payment_reconciler::reconcile_pending([
            'limit' => 5,
            'minage' => 300,
            'maxage' => 2 * DAYSECS,
        ]);

        mtrace('[digital payments] reconciled=' . $result['reconciled']
            . ' failed=' . $result['failed']
            . ' skipped=' . $result['skipped']
            . ' errors=' . $result['errors']);
    }
}