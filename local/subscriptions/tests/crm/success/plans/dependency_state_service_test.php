<?php

namespace local_subscriptions\crm\success\plans;

defined('MOODLE_INTERNAL') || die();

use basic_testcase;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanStep;
use local_subscriptions\crm\success\plans\services\CustomerSuccessPlanDependencyStateService;

/**
 * Tests persisted Customer Success dependency evaluation.
 *
 * @covers \local_subscriptions\crm\success\plans\services\CustomerSuccessPlanDependencyStateService
 */
final class dependency_state_service_test extends basic_testcase {

    private CustomerSuccessPlanDependencyStateService $service;

    protected function setUp(): void {
        parent::setUp();

        $this->service =
            new CustomerSuccessPlanDependencyStateService();
    }

    public function test_step_without_dependency_is_satisfied(): void {
        $step = $this->step(
            id: 1,
            status:
                CustomerSuccessPlanStepStatus::PENDING
        );

        $this->assertTrue(
            $this->service
                ->dependency_is_satisfied(
                    $step,
                    [
                        1 => $step,
                    ]
                )
        );
    }

    public function test_completed_dependency_is_satisfied(): void {
        $dependency = $this->step(
            id: 1,
            status:
                CustomerSuccessPlanStepStatus::COMPLETED
        );

        $child = $this->step(
            id: 2,
            status:
                CustomerSuccessPlanStepStatus::PENDING,
            dependsonstepid: 1
        );

        $this->assertTrue(
            $this->service
                ->dependency_is_satisfied(
                    $child,
                    $this->service->index_by_id(
                        [
                            $dependency,
                            $child,
                        ]
                    )
                )
        );
    }

    public function test_skipped_dependency_is_satisfied(): void {
        $dependency = $this->step(
            id: 1,
            status:
                CustomerSuccessPlanStepStatus::SKIPPED
        );

        $child = $this->step(
            id: 2,
            status:
                CustomerSuccessPlanStepStatus::PENDING,
            dependsonstepid: 1
        );

        $this->assertTrue(
            $this->service
                ->dependency_is_satisfied(
                    $child,
                    $this->service->index_by_id(
                        [
                            $dependency,
                            $child,
                        ]
                    )
                )
        );
    }

    public function test_open_dependency_is_not_satisfied(): void {
        $dependency = $this->step(
            id: 1,
            status:
                CustomerSuccessPlanStepStatus::IN_PROGRESS
        );

        $child = $this->step(
            id: 2,
            status:
                CustomerSuccessPlanStepStatus::PENDING,
            dependsonstepid: 1
        );

        $this->assertFalse(
            $this->service
                ->dependency_is_satisfied(
                    $child,
                    $this->service->index_by_id(
                        [
                            $dependency,
                            $child,
                        ]
                    )
                )
        );
    }

    public function test_missing_dependency_is_not_satisfied(): void {
        $child = $this->step(
            id: 2,
            status:
                CustomerSuccessPlanStepStatus::PENDING,
            dependsonstepid: 999
        );

        $this->assertFalse(
            $this->service
                ->dependency_is_satisfied(
                    $child,
                    [
                        2 => $child,
                    ]
                )
        );
    }

    public function test_all_terminal_requires_at_least_one_step(): void {
        $this->assertFalse(
            $this->service->all_terminal([])
        );
    }

    public function test_all_completed_or_skipped_steps_are_terminal(): void {
        $this->assertTrue(
            $this->service->all_terminal(
                [
                    $this->step(
                        id: 1,
                        status:
                            CustomerSuccessPlanStepStatus::COMPLETED
                    ),

                    $this->step(
                        id: 2,
                        status:
                            CustomerSuccessPlanStepStatus::SKIPPED
                    ),
                ]
            )
        );
    }

    public function test_one_open_step_prevents_terminal_state(): void {
        $this->assertFalse(
            $this->service->all_terminal(
                [
                    $this->step(
                        id: 1,
                        status:
                            CustomerSuccessPlanStepStatus::COMPLETED
                    ),

                    $this->step(
                        id: 2,
                        status:
                            CustomerSuccessPlanStepStatus::READY
                    ),
                ]
            )
        );
    }

    public function test_index_rejects_non_step_values(): void {
        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Dependency state service requires ' .
            'CustomerSuccessPlanStep objects.'
        );

        $this->service->index_by_id(
            [
                new \stdClass(),
            ]
        );
    }

    public function test_index_by_id_uses_step_ids(): void {
        $first = $this->step(
            id: 12,
            status:
                CustomerSuccessPlanStepStatus::READY
        );

        $second = $this->step(
            id: 27,
            status:
                CustomerSuccessPlanStepStatus::PENDING
        );

        $indexed =
            $this->service->index_by_id(
                [
                    $first,
                    $second,
                ]
            );

        $this->assertSame(
            [
                12,
                27,
            ],
            array_keys(
                $indexed
            )
        );

        $this->assertSame(
            $first,
            $indexed[12]
        );

        $this->assertSame(
            $second,
            $indexed[27]
        );
    }    

    public function test_blocked_step_prevents_terminal_state(): void {
        $this->assertFalse(
            $this->service->all_terminal(
                [
                    $this->step(
                        id: 1,
                        status:
                            CustomerSuccessPlanStepStatus::COMPLETED
                    ),

                    $this->step(
                        id: 2,
                        status:
                            CustomerSuccessPlanStepStatus::BLOCKED
                    ),
                ]
            )
        );
    }

    private function step(
        int $id,
        string $status,
        ?int $dependsonstepid = null
    ): CustomerSuccessPlanStep {
        return new CustomerSuccessPlanStep(
            id:
                $id,

            planid:
                10,

            position:
                $id,

            stepkey:
                'step_' . $id,

            title:
                'Step ' . $id,

            description:
                null,

            status:
                $status,

            priority:
                'normal',

            dependsonstepid:
                $dependsonstepid,

            blockedreason:
                null,

            relationtype:
                null,

            relationid:
                null,

            assignedteamid:
                null,

            assigneduserid:
                null,

            dueat:
                null,

            startedat:
                null,

            completedat:
                null,

            timecreated:
                100,

            timemodified:
                100,

            createdby:
                2,

            modifiedby:
                2
        );
    }
}