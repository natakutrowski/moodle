<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\grant\CommerceBulkGrantCampaignService;

final class process_commerce_grant_campaigns_task extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_process_commerce_grant_campaigns', 'local_subscriptions');
    }

    public function execute(): void {
        global $DB;

        $result = (new CommerceBulkGrantCampaignService($DB))->process(25);

        mtrace(sprintf(
            '[Commerce bulk grants] processed=%d completed=%d skipped=%d failed=%d',
            $result['processed'],
            $result['completed'],
            $result['skipped'],
            $result['failed']
        ));
    }
}
