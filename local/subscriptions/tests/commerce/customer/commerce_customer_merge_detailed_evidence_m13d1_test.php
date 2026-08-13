<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_customer_merge_detailed_evidence_m13d1_test extends advanced_testcase {
    public function test_final_preview_exposes_row_level_read_only_evidence(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/customer/merge/CommerceCustomerMergeFinalStateRenderer.php'
        );
        $service = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/customer/merge/CommerceCustomerMergeTechnicalDetailService.php'
        );

        $this->assertStringContainsString('CommerceCustomerMergeTechnicalDetailService::create()->build($plan)', $renderer);
        $this->assertStringContainsString('render_course_details', $renderer);
        $this->assertStringContainsString('render_purchase_details', $renderer);
        $this->assertStringContainsString('render_legacy_details', $renderer);
        $this->assertStringContainsString('render_rights_details', $renderer);

        $this->assertStringContainsString('FROM {user_enrolments}', $service);
        $this->assertStringContainsString("'local_subscriptions_commerce_purchase'", $service);
        $this->assertStringContainsString('FROM {user_subscription}', $service);
        $this->assertStringContainsString('FROM {subscription_digital_payment_request}', $service);
        $this->assertStringContainsString("'local_subs_commerce_grant'", $service);
        $this->assertStringContainsString("'local_subs_commerce_dig_access'", $service);

        // The technical evidence service is strictly read-only.
        $this->assertStringNotContainsString('update_record(', $service);
        $this->assertStringNotContainsString('delete_records(', $service);
        $this->assertStringNotContainsString('set_field(', $service);
        $this->assertStringNotContainsString('->execute(', $service);
    }
}
