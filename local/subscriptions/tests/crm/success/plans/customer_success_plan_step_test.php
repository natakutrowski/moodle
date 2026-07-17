<?php

namespace local_subscriptions\crm\success\plans;

defined('MOODLE_INTERNAL') || die();

use basic_testcase;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanStep;

/**
 * Tests Customer Success plan step invariants.
 *
 * @covers \local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanStep
 */
final class customer_success_plan_step_test extends basic_testcase {

    public function test_completed_step_is_completed(): void {
        $step = $this->step(
            CustomerSuccessPlanStepStatus::COMPLETED
        );

        $this->assertTrue(
            $step->is_completed()
        );

        $this->assertFalse(
            $step->is_blocked()
        );
    }

    public function test_skipped_step_is_completed(): void {
        $step = $this->step(
            CustomerSuccessPlanStepStatus::SKIPPED
        );

        $this->assertTrue(
            $step->is_completed()
        );
    }

    public function test_blocked_step_is_blocked(): void {
        $step = $this->step(
            CustomerSuccessPlanStepStatus::BLOCKED
        );

        $this->assertTrue(
            $step->is_blocked()
        );

        $this->assertFalse(
            $step->is_actionable()
        );
    }

    public function test_ready_step_is_actionable(): void {
        $step = $this->step(
            CustomerSuccessPlanStepStatus::READY
        );

        $this->assertTrue(
            $step->is_actionable()
        );
    }

    public function test_in_progress_step_is_actionable(): void {
        $step = $this->step(
            CustomerSuccessPlanStepStatus::IN_PROGRESS
        );

        $this->assertTrue(
            $step->is_actionable()
        );
    }

    public function test_invalid_status_is_rejected(): void {
        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Invalid Customer Success plan step status.'
        );

        $this->step(
            'invalid_status'
        );
    }

    public function test_empty_step_key_is_rejected(): void {
        $this->expectException(
            \InvalidArgumentException::class
        );

        new CustomerSuccessPlanStep(
            id:
                1,

            planid:
                10,

            position:
                1,

            stepkey:
                ' ',

            title:
                'Test step',

            description:
                null,

            status:
                CustomerSuccessPlanStepStatus::READY,

            priority:
                'normal',

            dependsonstepid:
                null,

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

    public function test_relation_id_without_type_is_rejected(): void {
        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'A Customer Success plan relation ID ' .
            'requires a relation type.'
        );

        new CustomerSuccessPlanStep(
            id:
                1,

            planid:
                10,

            position:
                1,

            stepkey:
                'test_step',

            title:
                'Test step',

            description:
                null,

            status:
                CustomerSuccessPlanStepStatus::READY,

            priority:
                'normal',

            dependsonstepid:
                null,

            blockedreason:
                null,

            relationtype:
                null,

            relationid:
                25,

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

    private function step(
        string $status
    ): CustomerSuccessPlanStep {
        return new CustomerSuccessPlanStep(
            id:
                1,

            planid:
                10,

            position:
                1,

            stepkey:
                'test_step',

            title:
                'Test step',

            description:
                null,

            status:
                $status,

            priority:
                'normal',

            dependsonstepid:
                null,

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