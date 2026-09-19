<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1_standard_checkout_provider_ux_test extends advanced_testcase {
    public function test_standard_checkout_has_no_legacy_provider_experience_dialog(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        $this->assertStringNotContainsString(
            'data-provider-context="standard"',
            $template
        );
        $this->assertStringNotContainsString(
            '{{> local_subscriptions/checkout/provider_experience}}',
            $template
        );
        $this->assertFileDoesNotExist(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/provider_experience.mustache'
        );
        $this->assertFileDoesNotExist(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/provider_experience.js'
        );
    }

    public function test_standard_checkout_uses_provider_agnostic_submission_state(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );
        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        $this->assertStringContainsString(
            'checkout_submission_state',
            $page
        );
        $this->assertStringContainsString(
            'data-checkout-payment-form',
            $template
        );
        $this->assertStringContainsString(
            'data-processing-label',
            $template
        );
    }
}
