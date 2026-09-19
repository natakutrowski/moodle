<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f33_paypal_checkout_presentation_test extends advanced_testcase {
    public function test_paypal_checkout_labels_exist_in_all_languages(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $lang) {
            $contents = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/lang/'
                . $lang
                . '/local_subscriptions.php'
            );

            $this->assertStringContainsString(
                "commerce_checkout_provider_paypal",
                $contents
            );
            $this->assertStringContainsString(
                "commerce_checkout_provider_paypal_desc",
                $contents
            );
        }
    }

    public function test_checkout_uses_central_provider_icons(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/presentation/'
            . 'CommerceCheckoutPresenter.php'
        );

        $this->assertStringContainsString(
            "/pix/providers/",
            $contents
        );
        $this->assertStringContainsString(
            ".svg",
            $contents
        );
        $this->assertStringNotContainsString(
            "/pix/email/' . \$key . '.png",
            $contents
        );
    }
}
