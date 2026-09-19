<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1285_currency_card_network_logos_test extends \advanced_testcase {
    public function test_checkout_uses_mir_as_third_network_for_rub_only(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            "\$currency === 'RUB'\n            ? '/local/subscriptions/pix/providers/mir.svg'",
            $checkout
        );
        self::assertStringContainsString(
            ": '/local/subscriptions/pix/providers/card.svg'",
            $checkout
        );
        self::assertStringContainsString(
            "'cardnetworkthirdlabel' => \$currency === 'RUB' ? 'MIR' : 'CB'",
            $checkout
        );
    }

    public function test_card_action_card_renders_currency_aware_third_network(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            'aria-label="Visa, Mastercard, {{cardnetworkthirdlabel}}"',
            $template
        );
        self::assertStringContainsString(
            '<img src="{{cardnetworkthirdiconurl}}" alt="{{cardnetworkthirdlabel}}"',
            $template
        );
        self::assertStringNotContainsString(
            'aria-label="Visa, Mastercard, CB"',
            $template
        );
    }

    public function test_provider_card_icon_is_not_changed(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            "'cardiconurl' => (new moodle_url('/local/subscriptions/pix/providers/card.svg'))",
            $checkout
        );
    }
}
