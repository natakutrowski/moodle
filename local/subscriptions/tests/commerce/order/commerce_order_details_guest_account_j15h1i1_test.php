<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_order_details_guest_account_j15h1i1_test extends \advanced_testcase {
    public function test_guest_order_details_and_login_guidance_are_wired(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';
        $page = file_get_contents($root . '/order_details.php');
        $template = file_get_contents($root . '/templates/order_details/page.mustache');
        $dialog = file_get_contents($root . '/templates/commerce/guest_account_dialog.mustache');

        self::assertStringContainsString('CommerceProvisionalGuestAccountContext', $page);
        self::assertStringContainsString('local_subscriptions/commerce/guest_account_dialog', $template);
        self::assertStringContainsString('data-account-dialog-auto-open="{{autoopen}}"', $dialog);
        self::assertStringContainsString('requiresaccountfinalisation', $template);

    }
}
