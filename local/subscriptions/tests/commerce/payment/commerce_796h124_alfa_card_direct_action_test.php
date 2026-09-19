<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h124_alfa_card_direct_action_test extends \advanced_testcase {
    public function test_card_selection_does_not_hide_a_direct_launch_contract_in_the_template(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringNotContainsString(
            'alfaCardDirectAction',
            $checkout
        );
        self::assertStringNotContainsString(
            'data-alfa-card-direct',
            $template
        );
    }

    public function test_selected_alfa_card_is_launched_by_the_primary_checkout_cta(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        self::assertStringNotContainsString(
            'wireDirectCardAction',
            $amd
        );
        self::assertStringContainsString(
            "selectedMethod(form)\n            === 'card'",
            $amd
        );
        self::assertStringContainsString(
            'setSubmitLabel(',
            $amd
        );
        self::assertStringContainsString(
            'config.openLabel',
            $amd
        );
        self::assertStringContainsString(
            "form.addEventListener(\n        'submit'",
            $amd
        );
        self::assertStringContainsString(
            'form.reportValidity?.();',
            $amd
        );
        self::assertStringContainsString(
            'officialButton.click();',
            $amd
        );
    }
}
