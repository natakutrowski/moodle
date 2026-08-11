<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B14 Checkout cleanup and post-payment stepper. */
final class commerce_order_result_steps_j67b14_test
        extends \advanced_testcase {

    public function test_checkout_no_longer_renders_commerce_eyebrow(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        $this->assertIsString($template);
        $this->assertStringNotContainsString(
            '{{eyebrow}}',
            $template
        );

        $this->assertIsString($source);
        $this->assertStringNotContainsString(
            "'eyebrow' =>",
            $source
        );
    }

    public function test_order_result_renders_shared_four_step_template(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/order_result.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "'local_subscriptions/checkout/steps'",
            $source
        );
        $this->assertStringContainsString(
            'CommercePurchaseFlow::result_steps',
            $source
        );
        $this->assertStringContainsString(
            "'failed', 'cancelled' => 'is-failed'",
            $source
        );
        $this->assertStringContainsString(
            "'success' => 'is-complete'",
            $source
        );
    }

    public function test_order_result_css_supports_failed_confirmation(): void {
        global $CFG;

        $styles = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/order_result.css'
        );

        $this->assertIsString($styles);
        $this->assertStringContainsString(
            '.commerce-checkout-steps__item.is-failed',
            $styles
        );
        $this->assertStringContainsString(
            'content: "!"',
            $styles
        );
    }
}
