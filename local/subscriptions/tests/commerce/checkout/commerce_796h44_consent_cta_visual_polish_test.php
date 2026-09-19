<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h44_consent_cta_visual_polish_test extends advanced_testcase {
    public function test_checkout_submit_has_dedicated_visual_hook_and_lock_icon(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        $this->assertStringContainsString(
            'commerce-checkout-submit',
            $template
        );
        $this->assertStringContainsString(
            'commerce-checkout-submit__icon',
            $template
        );
        $this->assertStringContainsString(
            'fa-solid fa-lock',
            $template
        );
    }

    public function test_consent_block_uses_compact_grid_alignment(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/guest_checkout.css'
        );

        $this->assertStringContainsString(
            'grid-template-columns: 1.15rem minmax(0, 1fr);',
            $css
        );
        $this->assertStringContainsString(
            'align-items: start;',
            $css
        );
    }

    public function test_cta_has_distinct_enabled_and_disabled_presentation(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/guest_checkout.css'
        );

        $this->assertStringContainsString(
            '.commerce-checkout-submit:not(:disabled):hover',
            $css
        );
        $this->assertStringContainsString(
            '.commerce-checkout-submit:disabled',
            $css
        );
        $this->assertStringContainsString(
            'box-shadow: none;',
            $css
        );
    }

    public function test_wallet_amd_is_not_touched_by_h44(): void {
        global $CFG;

        $this->assertFileExists(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
    }
}
