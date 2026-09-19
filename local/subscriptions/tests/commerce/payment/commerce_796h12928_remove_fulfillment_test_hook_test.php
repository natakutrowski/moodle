<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h12928_remove_fulfillment_test_hook_test extends \advanced_testcase {
    public function test_runtime_no_longer_contains_deterministic_fulfillment_hook(): void {
        global $CFG;

        $result = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );
        $endpoint = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/payment/fulfillment_status.php'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/order_fulfillment_poll.js'
        );

        foreach ([$result, $endpoint, $amd] as $source) {
            self::assertStringNotContainsString(
                'testfulfillmentdelay',
                $source
            );
            self::assertStringNotContainsString(
                'testfulfillmentstartedat',
                $source
            );
            self::assertStringNotContainsString(
                'testdelay',
                $source
            );
            self::assertStringNotContainsString(
                'teststartedat',
                $source
            );
        }
    }

    public function test_real_paid_processing_polling_is_preserved(): void {
        global $CFG;

        $result = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );
        $endpoint = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/payment/fulfillment_status.php'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/order_fulfillment_poll.js'
        );

        self::assertStringContainsString(
            "\$state->code === 'processing' && \$order->is_paid()",
            $result
        );
        self::assertStringContainsString(
            "'local_subscriptions/order_fulfillment_poll'",
            $result
        );
        self::assertStringContainsString(
            "'success' => 'ready'",
            $endpoint
        );
        self::assertStringContainsString(
            "result.status === 'ready'",
            $amd
        );
        self::assertStringContainsString(
            'window.location.reload();',
            $amd
        );
    }

    public function test_validated_processing_hero_ux_is_preserved(): void {
        global $CFG;

        $result = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/order_result.css'
        );

        self::assertStringContainsString(
            'commerce-order-hero__message--processing',
            $result
        );
        self::assertStringContainsString(
            'commerce-order-hero__processing-spinner',
            $result
        );
        self::assertStringContainsString(
            'commerce-order-hero__processing-text',
            $result
        );
        self::assertStringContainsString(
            'commerce-order-fulfillment-watch__refresh',
            $result
        );
        self::assertStringContainsString(
            'gap: .35rem;',
            $css
        );
        self::assertStringContainsString(
            'font-size: .84rem;',
            $css
        );
    }
}
