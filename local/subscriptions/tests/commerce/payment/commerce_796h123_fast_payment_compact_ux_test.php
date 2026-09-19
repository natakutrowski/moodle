<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h123_fast_payment_compact_ux_test extends \advanced_testcase {

    public function test_fast_payment_methods_use_compact_secondary_action_cards(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        self::assertStringContainsString('commerce-checkout-action-card--secondary', $template);
        self::assertStringContainsString('commerce-checkout-action-card__secondary-content', $template);
        self::assertStringContainsString('data-quick-payment-action="{{key}}"', $template);
        self::assertStringNotContainsString('commerce-checkout-fast-payment__brand', $template);
    }


    public function test_sbp_dedicated_driver_validates_form_before_ajax_prepare(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_sbp.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString('form.checkValidity()', $amd);
        self::assertStringContainsString('form.reportValidity?.();', $amd);
        self::assertStringContainsString('event.stopImmediatePropagation();', $amd);
        self::assertStringContainsString('prepare(', $amd);
    }

}
