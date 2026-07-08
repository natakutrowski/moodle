<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class TrialExpiredScanner {

    public function __construct(
        private readonly AutomationScannerRepository $repository = new AutomationScannerRepository(),
        private readonly AutomationDispatcher $dispatcher = new AutomationDispatcher()
    ) {
    }

    public function run(int $now): int {
        $count = 0;
        $trials = $this->repository->get_unprocessed_expired_trials($now);

        foreach ($trials as $trial) {
            $this->dispatcher->dispatch_entity(
                AutomationTriggerKeys::TRIAL_EXPIRED,
                AutomationEntityTypes::USER_SUBSCRIPTION,
                (int)$trial->id,
                (int)$trial->userid,
                [
                    'subscriptionid' => (int)$trial->id,
                    'planid' => (int)$trial->planid,
                    'startdate' => (int)$trial->start_date,
                    'enddate' => (int)$trial->end_date,
                    'status' => (string)$trial->status,
                    'source' => 'cron_trial_expired_scanner',
                ]
            );

            $count++;
        }

        return $count;
    }
}