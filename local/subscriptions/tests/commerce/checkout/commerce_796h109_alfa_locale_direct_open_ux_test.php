<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h109_alfa_locale_direct_open_ux_test extends advanced_testcase {

    public function test_checkout_locale_is_forwarded_to_alfa_prepare_request(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $this->assertIsString($page);
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );
        $this->assertIsString($action);
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $this->assertIsString($template);
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );
        $this->assertIsString($js);

        $this->assertStringContainsString("'alfawidgetcheckoutlanguage' => current_language()", $page);
        $this->assertStringContainsString("'checkoutlanguage'", $action);
        $this->assertStringContainsString('PARAM_LANG', $action);
        $this->assertStringContainsString('data-checkout-language="{{alfawidgetcheckoutlanguage}}"', $template);
        $this->assertStringContainsString("body.set(\n        'checkoutlanguage'", $js);
        $this->assertStringContainsString('resolveWidgetLanguage(', $js);
    }


    public function test_official_button_uses_campusfr_label_and_opens_directly(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $this->assertIsString($page);
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );
        $this->assertIsString($js);

        $this->assertStringContainsString("'commerce_checkout_alfa_widget_card_cta'", $page);
        $this->assertStringContainsString('localiseOfficialButton(', $js);
        $this->assertStringContainsString("'Opening official Alfa modal directly'", $js);
        $this->assertStringContainsString('officialButton.click();', $js);
    }


    public function test_passive_alfa_cancellation_message_is_suppressed_only_by_known_text(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );
        $this->assertIsString($js);

        $this->assertStringContainsString('isPassiveCancellationMessage', $js);
        $this->assertStringContainsString("'заказ не оплачен'", $js);
        $this->assertStringContainsString("'order not paid'", $js);
        $this->assertStringContainsString('message.hidden = true;', $js);
    }

}
