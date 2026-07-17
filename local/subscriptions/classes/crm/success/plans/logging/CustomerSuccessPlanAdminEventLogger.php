<?php

namespace local_subscriptions\crm\success\plans\logging;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlan;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanStep;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanPresentation;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;

/**
 * Writes Customer Success lifecycle events to the unified admin log.
 */
final class CustomerSuccessPlanAdminEventLogger {

    public function __construct(
        private readonly CustomerSuccessPlanReadRepository $reader =
            new CustomerSuccessPlanReadRepository()
    ) {
    }

    public function plan_created(
        int $planid,
        int $actorid
    ): void {
        $this->log_plan(
            AdminEvents::CUSTOMER_SUCCESS_PLAN_CREATED,
            $planid,
            $actorid
        );
    }

    public function plan_activated(
        int $planid,
        string $previousstatus,
        int $actorid
    ): void {
        $this->log_plan(
            AdminEvents::CUSTOMER_SUCCESS_PLAN_ACTIVATED,
            $planid,
            $actorid,
            [
                'previousstatus' =>
                    $previousstatus,
            ]
        );
    }

    public function plan_paused(
        int $planid,
        string $previousstatus,
        int $actorid
    ): void {
        $this->log_plan(
            AdminEvents::CUSTOMER_SUCCESS_PLAN_PAUSED,
            $planid,
            $actorid,
            [
                'previousstatus' =>
                    $previousstatus,
            ]
        );
    }

    public function plan_cancelled(
        int $planid,
        string $previousstatus,
        int $actorid
    ): void {
        $this->log_plan(
            AdminEvents::CUSTOMER_SUCCESS_PLAN_CANCELLED,
            $planid,
            $actorid,
            [
                'previousstatus' =>
                    $previousstatus,
            ]
        );
    }

    public function plan_completed(
        int $planid,
        string $previousstatus,
        int $actorid,
        bool $automatic = false
    ): void {
        $this->log_plan(
            $automatic
                ? AdminEvents::CUSTOMER_SUCCESS_PLAN_AUTO_COMPLETED
                : AdminEvents::CUSTOMER_SUCCESS_PLAN_COMPLETED,
            $planid,
            $actorid,
            [
                'previousstatus' =>
                    $previousstatus,

                'automatic' =>
                    $automatic,
            ]
        );
    }

    public function step_started(
        int $stepid,
        string $previousstatus,
        int $actorid
    ): void {
        $this->log_step(
            AdminEvents::CUSTOMER_SUCCESS_STEP_STARTED,
            $stepid,
            $actorid,
            [
                'previousstatus' =>
                    $previousstatus,
            ]
        );
    }

    public function step_completed(
        int $stepid,
        string $previousstatus,
        int $actorid
    ): void {
        $this->log_step(
            AdminEvents::CUSTOMER_SUCCESS_STEP_COMPLETED,
            $stepid,
            $actorid,
            [
                'previousstatus' =>
                    $previousstatus,
            ]
        );
    }

    public function step_skipped(
        int $stepid,
        string $previousstatus,
        int $actorid
    ): void {
        $this->log_step(
            AdminEvents::CUSTOMER_SUCCESS_STEP_SKIPPED,
            $stepid,
            $actorid,
            [
                'previousstatus' =>
                    $previousstatus,
            ]
        );
    }

    public function step_blocked(
        int $stepid,
        string $previousstatus,
        string $reason,
        int $actorid
    ): void {
        $this->log_step(
            AdminEvents::CUSTOMER_SUCCESS_STEP_BLOCKED,
            $stepid,
            $actorid,
            [
                'previousstatus' =>
                    $previousstatus,

                'blockedreason' =>
                    $reason,
            ]
        );
    }

    public function step_unblocked(
        int $stepid,
        string $previousstatus,
        string $newstatus,
        int $actorid
    ): void {
        $this->log_step(
            AdminEvents::CUSTOMER_SUCCESS_STEP_UNBLOCKED,
            $stepid,
            $actorid,
            [
                'previousstatus' =>
                    $previousstatus,

                'newstatus' =>
                    $newstatus,
            ]
        );
    }

    private function log_plan(
        string $event,
        int $planid,
        int $actorid,
        array $details = []
    ): void {
        try {
            $plan =
                $this->reader->get(
                    $planid
                );
        } catch (\Throwable $exception) {
            debugging(
                sprintf(
                    'Unable to log Customer Success plan event for plan #%d: %s',
                    $planid,
                    $exception->getMessage()
                ),
                DEBUG_DEVELOPER
            );

            return;
        }

        AdminLog::log(
            $event,
            $plan->userid,
            'customer_success_plan',
            $plan->id,
            array_merge(
                $this->plan_details(
                    $plan,
                    $actorid
                ),
                $details
            )
        );
    }

    private function log_step(
        string $event,
        int $stepid,
        int $actorid,
        array $details = []
    ): void {
        try {
            $step =
                $this->reader->get_step(
                    $stepid
                );

            $plan =
                $this->reader->get(
                    $step->planid
                );
        } catch (\Throwable $exception) {
            debugging(
                sprintf(
                    'Unable to log Customer Success step event for step #%d: %s',
                    $stepid,
                    $exception->getMessage()
                ),
                DEBUG_DEVELOPER
            );

            return;
        }

        AdminLog::log(
            $event,
            $plan->userid,
            'customer_success_step',
            $step->id,
            array_merge(
                $this->plan_details(
                    $plan,
                    $actorid
                ),
                $this->step_details(
                    $step
                ),
                $details
            )
        );
    }

    private function plan_details(
        CustomerSuccessPlan $plan,
        int $actorid
    ): array {
        return [
            'planid' =>
                $plan->id,

            'planreference' =>
                $plan->reference,

            'plantitle' =>
                CustomerSuccessPlanPresentation::title(
                    $plan->objectivekey,
                    $plan->title
                ),

            'objectivekey' =>
                $plan->objectivekey,

            'status' =>
                $plan->status,

            'priority' =>
                $plan->priority,

            'source' =>
                $plan->source,

            'actorid' =>
                $actorid,
        ];
    }

    private function step_details(
        CustomerSuccessPlanStep $step
    ): array {
        return [
            'stepid' =>
                $step->id,

            'stepkey' =>
                $step->stepkey,

            'steptitle' =>
                $step->title,

            'stepstatus' =>
                $step->status,

            'position' =>
                $step->position,
        ];
    }
}