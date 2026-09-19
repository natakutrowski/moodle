<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1272_unified_action_card_rendering_test extends \advanced_testcase {
    public function test_template_uses_new_action_card_buckets(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString('{{#primaryactioncards}}', $template);
        self::assertStringContainsString('{{#secondaryactioncards}}', $template);
        self::assertStringContainsString('commerce-checkout-action-card--primary', $template);
        self::assertStringContainsString('commerce-checkout-action-card--secondary', $template);
        self::assertStringNotContainsString('{{#primarymethods}}', $template);
        self::assertStringNotContainsString('{{#quickmethods}}', $template);
    }

    public function test_radios_are_hidden_but_remain_form_state(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $intent = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_intent.js'
        );

        self::assertStringContainsString('class="visually-hidden"', $template);
        self::assertStringContainsString('name="paymentmethod"', $template);
        self::assertStringContainsString('data-payment-action-card="{{key}}"', $template);
        self::assertStringContainsString('const syncCards = form => {', $intent);
    }

    public function test_primary_and_secondary_cards_have_distinct_layouts(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        self::assertMatchesRegularExpression(
            '/\\.commerce-checkout-action-cards--primary\\s*\\{[^}]*grid-template-columns:\\s*1fr;/s',
            $css
        );
        self::assertMatchesRegularExpression(
            '/\\.commerce-checkout-action-cards--secondary\\s*\\{[^}]*grid-template-columns:\\s*repeat\\(2,\\s*minmax\\(0,\\s*1fr\\)\\);/s',
            $css
        );
        self::assertStringContainsString(
            'background: rgba(var(--bs-primary-rgb), .07);',
            $css
        );
    }

    public function test_fast_execution_hooks_are_preserved_on_secondary_cards(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString('data-quick-payment-action="{{key}}"', $template);
        self::assertStringContainsString('data-quick-payment-radio="{{key}}"', $template);
    }
}
