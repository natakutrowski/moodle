<?php

namespace local_subscriptions\crm\success\plans;

defined('MOODLE_INTERNAL') || die();

use basic_testcase;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStatus;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;

/**
 * Tests Customer Success lifecycle status definitions.
 *
 * @covers \local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStatus
 * @covers \local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus
 */
final class status_test extends basic_testcase {

    public function test_plan_status_sets_are_consistent(): void {
        $this->assertSame(
            [
                'draft',
                'active',
                'paused',
                'completed',
                'cancelled',
            ],
            CustomerSuccessPlanStatus::all()
        );

        $this->assertSame(
            [
                'draft',
                'active',
                'paused',
            ],
            CustomerSuccessPlanStatus::open()
        );

        $this->assertSame(
            [
                'completed',
                'cancelled',
            ],
            CustomerSuccessPlanStatus::terminal()
        );

        foreach (
            CustomerSuccessPlanStatus::all()
            as $status
        ) {
            $this->assertTrue(
                CustomerSuccessPlanStatus::is_valid(
                    $status
                )
            );
        }

        $this->assertFalse(
            CustomerSuccessPlanStatus::is_valid(
                'unknown'
            )
        );
    }

    public function test_plan_open_and_terminal_helpers(): void {
        $this->assertTrue(
            CustomerSuccessPlanStatus::is_open(
                CustomerSuccessPlanStatus::DRAFT
            )
        );

        $this->assertTrue(
            CustomerSuccessPlanStatus::is_open(
                CustomerSuccessPlanStatus::ACTIVE
            )
        );

        $this->assertTrue(
            CustomerSuccessPlanStatus::is_open(
                CustomerSuccessPlanStatus::PAUSED
            )
        );

        $this->assertFalse(
            CustomerSuccessPlanStatus::is_open(
                CustomerSuccessPlanStatus::COMPLETED
            )
        );

        $this->assertTrue(
            CustomerSuccessPlanStatus::is_terminal(
                CustomerSuccessPlanStatus::COMPLETED
            )
        );

        $this->assertTrue(
            CustomerSuccessPlanStatus::is_terminal(
                CustomerSuccessPlanStatus::CANCELLED
            )
        );

        $this->assertFalse(
            CustomerSuccessPlanStatus::is_terminal(
                CustomerSuccessPlanStatus::ACTIVE
            )
        );
    }

    public function test_step_status_sets_are_consistent(): void {
        $this->assertSame(
            [
                'pending',
                'ready',
                'blocked',
                'in_progress',
                'completed',
                'skipped',
            ],
            CustomerSuccessPlanStepStatus::all()
        );

        $this->assertTrue(
            CustomerSuccessPlanStepStatus::is_terminal(
                CustomerSuccessPlanStepStatus::COMPLETED
            )
        );

        $this->assertTrue(
            CustomerSuccessPlanStepStatus::is_terminal(
                CustomerSuccessPlanStepStatus::SKIPPED
            )
        );

        $this->assertFalse(
            CustomerSuccessPlanStepStatus::is_terminal(
                CustomerSuccessPlanStepStatus::BLOCKED
            )
        );
    }

    public function test_step_open_and_validation_helpers(): void {
        foreach (
            CustomerSuccessPlanStepStatus::open()
            as $status
        ) {
            $this->assertTrue(
                CustomerSuccessPlanStepStatus::is_open(
                    $status
                )
            );

            $this->assertTrue(
                CustomerSuccessPlanStepStatus::is_valid(
                    $status
                )
            );
        }

        foreach (
            CustomerSuccessPlanStepStatus::terminal()
            as $status
        ) {
            $this->assertTrue(
                CustomerSuccessPlanStepStatus::is_terminal(
                    $status
                )
            );
        }

        $this->assertFalse(
            CustomerSuccessPlanStepStatus::is_valid(
                'unknown'
            )
        );
    }

}