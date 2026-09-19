<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e3_payment_method_propagation_test extends advanced_testcase {
    public function test_unified_payment_builder_propagates_method_from_purchase_metadata(): void {
        global $CFG;

        $builder = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/'
            . 'CommerceCheckoutPaymentRequestBuilder.php'
        );

        $this->assertStringContainsString(
            "get_metadata_value('payment_method'",
            $builder
        );
    }

    public function test_checkout_page_and_action_both_put_method_in_context_metadata(): void {
        global $CFG;

        foreach ([
            'commerce_checkout.php',
            'commerce_checkout_action.php',
        ] as $file) {
            $contents = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $file
            );
            $this->assertStringContainsString(
                "'payment_method' =>",
                $contents
            );
        }
    }
}
