<?php

namespace local_subscriptions\crm\success\plans\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStatus;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlan;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanLifecycleResult;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanStep;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanRepository;
use local_subscriptions\crm\success\plans\logging\CustomerSuccessPlanAdminEventLogger;

/**
 * Controls plan and step lifecycle transitions.
 *
 * No transition should be performed directly through the repository.
 */
final class CustomerSuccessPlanLifecycleService {

    public function __construct(
        private readonly CustomerSuccessPlanReadRepository $reader =
            new CustomerSuccessPlanReadRepository(),

        private readonly CustomerSuccessPlanRepository $writer =
            new CustomerSuccessPlanRepository(),

        private readonly CustomerSuccessPlanDependencyStateService $dependencies =
            new CustomerSuccessPlanDependencyStateService(),

        private readonly CustomerSuccessPlanAdminEventLogger $events =
            new CustomerSuccessPlanAdminEventLogger()            
    ) {
    }

    public function activate(
        int $planid,
        int $actorid
    ): CustomerSuccessPlanLifecycleResult {
        $plan = $this->reader->get($planid);

        $this->require_plan_status(
            $plan,
            [
                CustomerSuccessPlanStatus::DRAFT,
                CustomerSuccessPlanStatus::PAUSED,
            ]
        );

        $previous = $plan->status;
        $now = time();

        $fields = [
            'status' => CustomerSuccessPlanStatus::ACTIVE,
            'completedat' => null,
        ];

        if ($plan->activatedat === null) {
            $fields['activatedat'] = $now;
        }

        $this->writer->update_plan(
            $planid,
            $fields,
            $actorid
        );

        $this->refresh_step_states(
            $planid,
            $actorid
        );

        $this->events->plan_activated(
            $planid,
            $previous,
            $actorid
        );        

        return new CustomerSuccessPlanLifecycleResult(
            planid: $planid,
            stepid: null,
            action: 'activate',
            previousstatus: $previous,
            newstatus: CustomerSuccessPlanStatus::ACTIVE
        );
    }

    public function pause(
        int $planid,
        int $actorid
    ): CustomerSuccessPlanLifecycleResult {
        $plan = $this->reader->get($planid);

        $this->require_plan_status(
            $plan,
            [CustomerSuccessPlanStatus::ACTIVE]
        );

        $this->writer->update_plan(
            $planid,
            [
                'status' => CustomerSuccessPlanStatus::PAUSED,
            ],
            $actorid
        );

        $this->events->plan_paused(
            $planid,
            $plan->status,
            $actorid
        );

        return new CustomerSuccessPlanLifecycleResult(
            planid: $planid,
            stepid: null,
            action: 'pause',
            previousstatus: $plan->status,
            newstatus: CustomerSuccessPlanStatus::PAUSED
        );
    }

    public function cancel(
        int $planid,
        int $actorid
    ): CustomerSuccessPlanLifecycleResult {
        $plan = $this->reader->get($planid);

        if (!$plan->is_open()) {
            throw new \coding_exception(
                'Only an open Customer Success plan can be cancelled.'
            );
        }

        $this->writer->update_plan(
            $planid,
            [
                'status' => CustomerSuccessPlanStatus::CANCELLED,
                'completedat' => null,
            ],
            $actorid
        );

        $this->events->plan_cancelled(
            $planid,
            $plan->status,
            $actorid
        );

        return new CustomerSuccessPlanLifecycleResult(
            planid: $planid,
            stepid: null,
            action: 'cancel',
            previousstatus: $plan->status,
            newstatus: CustomerSuccessPlanStatus::CANCELLED
        );
    }

    public function complete_plan(
        int $planid,
        int $actorid
    ): CustomerSuccessPlanLifecycleResult {
        $plan = $this->reader->get($planid);

        $this->require_plan_status(
            $plan,
            [CustomerSuccessPlanStatus::ACTIVE]
        );

        if (
            !$this->dependencies->all_terminal(
                $plan->steps
            )
        ) {
            throw new \coding_exception(
                'A Customer Success plan cannot be completed while open steps remain.'
            );
        }

        $this->writer->update_plan(
            $planid,
            [
                'status' => CustomerSuccessPlanStatus::COMPLETED,
                'completedat' => time(),
            ],
            $actorid
        );

        $this->events->plan_completed(
            $planid,
            $plan->status,
            $actorid
        );

        return new CustomerSuccessPlanLifecycleResult(
            planid: $planid,
            stepid: null,
            action: 'complete_plan',
            previousstatus: $plan->status,
            newstatus: CustomerSuccessPlanStatus::COMPLETED
        );
    }

    public function start_step(
        int $stepid,
        int $actorid
    ): CustomerSuccessPlanLifecycleResult {
        $step = $this->reader->get_step($stepid);
        $plan = $this->reader->get($step->planid);

        $this->require_plan_status(
            $plan,
            [CustomerSuccessPlanStatus::ACTIVE]
        );

        $this->refresh_step_states(
            $plan->id,
            $actorid
        );

        $step = $this->reader->get_step($stepid);

        if (
            $step->status !==
            CustomerSuccessPlanStepStatus::READY
        ) {
            throw new \coding_exception(
                'Only a ready Customer Success plan step can be started.'
            );
        }

        $this->writer->update_step(
            $stepid,
            [
                'status' =>
                    CustomerSuccessPlanStepStatus::IN_PROGRESS,
                'startedat' => time(),
                'completedat' => null,
            ],
            $actorid
        );

        $this->events->step_started(
            $stepid,
            $step->status,
            $actorid
        );

        return new CustomerSuccessPlanLifecycleResult(
            planid: $plan->id,
            stepid: $stepid,
            action: 'start_step',
            previousstatus: $step->status,
            newstatus:
                CustomerSuccessPlanStepStatus::IN_PROGRESS
        );
    }

    public function complete_step(
        int $stepid,
        int $actorid
    ): CustomerSuccessPlanLifecycleResult {
        $step = $this->reader->get_step($stepid);
        $plan = $this->reader->get($step->planid);

        $this->require_plan_status(
            $plan,
            [CustomerSuccessPlanStatus::ACTIVE]
        );

        if (
            !in_array(
                $step->status,
                [
                    CustomerSuccessPlanStepStatus::READY,
                    CustomerSuccessPlanStepStatus::IN_PROGRESS,
                ],
                true
            )
        ) {
            throw new \coding_exception(
                'This Customer Success plan step cannot be completed.'
            );
        }

        $stepsbyid =
            $this->dependencies->index_by_id(
                $plan->steps
            );

        if (
            !$this->dependencies->dependency_is_satisfied(
                $step,
                $stepsbyid
            )
        ) {
            throw new \coding_exception(
                'The Customer Success plan step dependency is not completed.'
            );
        }

        $this->writer->update_step(
            $stepid,
            [
                'status' =>
                    CustomerSuccessPlanStepStatus::COMPLETED,
                'completedat' => time(),
                'blockedreason' => null,
            ],
            $actorid
        );

        $this->events->step_completed(
            $stepid,
            $step->status,
            $actorid
        );

        return $this->after_terminal_step(
            $plan->id,
            $step,
            'complete_step',
            CustomerSuccessPlanStepStatus::COMPLETED,
            $actorid
        );
    }

    public function skip_step(
        int $stepid,
        int $actorid
    ): CustomerSuccessPlanLifecycleResult {
        $step = $this->reader->get_step($stepid);
        $plan = $this->reader->get($step->planid);

        $this->require_plan_status(
            $plan,
            [CustomerSuccessPlanStatus::ACTIVE]
        );

        if (
            CustomerSuccessPlanStepStatus::is_terminal(
                $step->status
            )
        ) {
            throw new \coding_exception(
                'A terminal Customer Success plan step cannot be skipped.'
            );
        }

        $this->writer->update_step(
            $stepid,
            [
                'status' =>
                    CustomerSuccessPlanStepStatus::SKIPPED,
                'completedat' => time(),
                'blockedreason' => null,
            ],
            $actorid
        );

        $this->events->step_skipped(
            $stepid,
            $step->status,
            $actorid
        );        

        return $this->after_terminal_step(
            $plan->id,
            $step,
            'skip_step',
            CustomerSuccessPlanStepStatus::SKIPPED,
            $actorid
        );
    }

    public function block_step(
        int $stepid,
        string $reason,
        int $actorid
    ): CustomerSuccessPlanLifecycleResult {
        $step = $this->reader->get_step($stepid);
        $plan = $this->reader->get($step->planid);

        $this->require_plan_status(
            $plan,
            [
                CustomerSuccessPlanStatus::DRAFT,
                CustomerSuccessPlanStatus::ACTIVE,
                CustomerSuccessPlanStatus::PAUSED,
            ]
        );

        $reason = trim($reason);

        if ($reason === '') {
            throw new \InvalidArgumentException(
                'A blocked step requires a reason.'
            );
        }

        if (
            CustomerSuccessPlanStepStatus::is_terminal(
                $step->status
            )
        ) {
            throw new \coding_exception(
                'A terminal step cannot be blocked.'
            );
        }

        $this->writer->update_step(
            $stepid,
            [
                'status' =>
                    CustomerSuccessPlanStepStatus::BLOCKED,
                'blockedreason' => $reason,
            ],
            $actorid
        );

        $this->events->step_blocked(
            $stepid,
            $step->status,
            $reason,
            $actorid
        );

        return new CustomerSuccessPlanLifecycleResult(
            planid: $plan->id,
            stepid: $stepid,
            action: 'block_step',
            previousstatus: $step->status,
            newstatus: CustomerSuccessPlanStepStatus::BLOCKED
        );
    }

    public function unblock_step(
        int $stepid,
        int $actorid
    ): CustomerSuccessPlanLifecycleResult {
        $step = $this->reader->get_step($stepid);
        $plan = $this->reader->get($step->planid);

        if (
            $step->status !==
            CustomerSuccessPlanStepStatus::BLOCKED
        ) {
            throw new \coding_exception(
                'Only a blocked step can be unblocked.'
            );
        }

        if ($step->blockedreason === 'dependency_cycle') {
            throw new \coding_exception(
                'A dependency cycle must be corrected before unblocking this step.'
            );
        }

        $stepsbyid =
            $this->dependencies->index_by_id(
                $plan->steps
            );

        $status =
            $this->dependencies->dependency_is_satisfied(
                $step,
                $stepsbyid
            )
                ? CustomerSuccessPlanStepStatus::READY
                : CustomerSuccessPlanStepStatus::PENDING;

        $this->writer->update_step(
            $stepid,
            [
                'status' => $status,
                'blockedreason' => null,
            ],
            $actorid
        );

        $this->events->step_unblocked(
            $stepid,
            $step->status,
            $status,
            $actorid
        );

        return new CustomerSuccessPlanLifecycleResult(
            planid: $plan->id,
            stepid: $stepid,
            action: 'unblock_step',
            previousstatus: $step->status,
            newstatus: $status
        );
    }

    public function refresh_step_states(
        int $planid,
        int $actorid
    ): void {
        $plan = $this->reader->get($planid);

        $stepsbyid =
            $this->dependencies->index_by_id(
                $plan->steps
            );

        foreach ($plan->steps as $step) {
            if (
                CustomerSuccessPlanStepStatus::is_terminal(
                    $step->status
                )
            ) {
                continue;
            }

            if (
                $step->status ===
                    CustomerSuccessPlanStepStatus::BLOCKED &&
                $step->blockedreason !== null
            ) {
                continue;
            }

            $targetstatus =
                $this->dependencies->dependency_is_satisfied(
                    $step,
                    $stepsbyid
                )
                    ? CustomerSuccessPlanStepStatus::READY
                    : CustomerSuccessPlanStepStatus::PENDING;

            if (
                $step->status ===
                    CustomerSuccessPlanStepStatus::IN_PROGRESS
            ) {
                continue;
            }

            if ($step->status !== $targetstatus) {
                $this->writer->update_step(
                    $step->id,
                    [
                        'status' => $targetstatus,
                    ],
                    $actorid
                );
            }
        }
    }

    private function after_terminal_step(
        int $planid,
        CustomerSuccessPlanStep $previousstep,
        string $action,
        string $newstepstatus,
        int $actorid
    ): CustomerSuccessPlanLifecycleResult {
        $this->refresh_step_states(
            $planid,
            $actorid
        );

        $plan = $this->reader->get($planid);
        $autocompleted = false;

        if (
            $plan->status ===
                CustomerSuccessPlanStatus::ACTIVE &&
            $this->dependencies->all_terminal(
                $plan->steps
            )
        ) {

            $previousplanstatus =
                $plan->status;

            $this->writer->update_plan(
                $planid,
                [
                    'status' =>
                        CustomerSuccessPlanStatus::COMPLETED,
                    'completedat' => time(),
                ],
                $actorid
            );

            $this->events->plan_completed(
                $planid,
                $previousplanstatus,
                $actorid,
                true
            );            

            $autocompleted = true;
        }

        return new CustomerSuccessPlanLifecycleResult(
            planid: $planid,
            stepid: $previousstep->id,
            action: $action,
            previousstatus: $previousstep->status,
            newstatus: $newstepstatus,
            planautocompleted: $autocompleted
        );
    }

    /**
     * @param string[] $allowedstatuses
     */
    private function require_plan_status(
        CustomerSuccessPlan $plan,
        array $allowedstatuses
    ): void {
        if (
            !in_array(
                $plan->status,
                $allowedstatuses,
                true
            )
        ) {
            throw new \coding_exception(
                'Invalid Customer Success plan lifecycle transition.'
            );
        }
    }
}