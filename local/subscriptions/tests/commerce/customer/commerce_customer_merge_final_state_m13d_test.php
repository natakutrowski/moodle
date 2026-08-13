<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_customer_merge_final_state_m13d_test extends advanced_testcase {
    public function test_merge_preview_exposes_explicit_final_state_and_technical_detail(): void {
        global $CFG;

        $merge = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/customer-identities/merge.php');
        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/customer/merge/CommerceCustomerMergeFinalStateRenderer.php'
        );
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/customer_identity_merge.css');

        $this->assertStringContainsString('CommerceCustomerMergeFinalStateRenderer::render(', $merge);
        $this->assertStringContainsString('commerce_identity_merge_warning_different_emails_transfer', $merge);
        $this->assertStringContainsString('m13d-account-card--retained', $renderer);
        $this->assertStringContainsString('m13d-account-card--absorbed', $renderer);
        $this->assertStringContainsString('commerce_identity_merge_final_sentence', $renderer);
        $this->assertStringContainsString("'details'", $renderer);
        $this->assertStringContainsString('m13d-technical', $css);
    }
}
