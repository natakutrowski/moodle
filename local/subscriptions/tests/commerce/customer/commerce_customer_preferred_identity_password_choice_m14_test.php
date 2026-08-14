<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_customer_preferred_identity_password_choice_m14_test extends advanced_testcase {
    public function test_merge_ui_exposes_explicit_password_owner_and_preserves_it_on_execute(): void {
        global $CFG;
        $merge = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/customer-identities/merge.php');

        $this->assertStringContainsString("optional_param('preferredpassworduserid'", $merge);
        $this->assertStringContainsString("'name' => 'preferredpassworduserid'", $merge);
        $this->assertStringContainsString("'type' => 'hidden'", $merge);
        $this->assertStringContainsString('commerce_identity_merge_preferred_password_choice', $merge);
        $this->assertStringContainsString('commerce_identity_merge_preferred_password_manual_only',
            file_get_contents($CFG->dirroot . '/local/subscriptions/lang/en/local_subscriptions.php'));
    }

    public function test_password_choice_defaults_to_retained_account(): void {
        global $CFG;
        $merge = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/customer-identities/merge.php');
        $this->assertStringContainsString('$preferredpassworduserid = $plan->targetuserid;', $merge);
    }
}
