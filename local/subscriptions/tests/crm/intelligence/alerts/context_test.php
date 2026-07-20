<?php

namespace local_subscriptions\crm\intelligence\alerts;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * Tests the operational alert context DTO.
 *
 * @covers \local_subscriptions\crm\intelligence\alerts\CrmAlertContext
 */
final class context_test extends advanced_testcase {

    public function test_empty_context_has_no_relations(): void {
        $context = new CrmAlertContext(
            userid: 42
        );

        $this->assertFalse(
            $context->has_work_item()
        );

        $this->assertFalse(
            $context->
                has_customer_success_plan()
        );
    }

    public function test_context_can_expose_work_item(): void {
        $context = new CrmAlertContext(
            userid: 42,
            workitem: (object)[
                'id' => 15,
            ]
        );

        $this->assertTrue(
            $context->has_work_item()
        );
    }

    public function test_context_can_expose_plan(): void {
        $context = new CrmAlertContext(
            userid: 42,
            customersuccessplan: (object)[
                'id' => 21,
            ]
        );

        $this->assertTrue(
            $context->
                has_customer_success_plan()
        );
    }
}