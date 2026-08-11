<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

/** Regression coverage for J15H.1I.3.1 activation polish hotfix. */
final class commerce_guest_activation_hotfix_j15h1i31_test extends advanced_testcase {
    public function test_activation_url_contains_anchor_and_required_strings_exist(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/CommerceGuestAccountActivationService.php'
        );
        $this->assertStringContainsString("set_anchor('activation')", $service);

        foreach (['en', 'fr', 'ru'] as $lang) {
            $langfile = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/lang/' . $lang . '/local_subscriptions.php'
            );
            $this->assertStringContainsString("commerce_guest_activation_title_prefix", $langfile);
            $this->assertStringContainsString("commerce_order_result_access_contents", $langfile);
        }
    }
}
