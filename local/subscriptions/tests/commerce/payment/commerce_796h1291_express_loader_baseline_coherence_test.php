<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1291_express_loader_baseline_coherence_test extends \advanced_testcase {
    public function test_express_loader_contract_is_complete_across_template_css_and_amd(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        self::assertStringContainsString(
            'data-checkout-express-wallet-loading',
            $template
        );
        self::assertStringContainsString(
            '{{expresswalletprobing}}',
            $template
        );
        self::assertStringContainsString(
            '.commerce-checkout-express-wallets__loading',
            $css
        );
        self::assertStringContainsString(
            "'is-probing'",
            $amd
        );
        self::assertStringContainsString(
            "'is-wallet-ready'",
            $amd
        );
        self::assertStringContainsString(
            "'is-wallet-unavailable'",
            $amd
        );
    }
}
