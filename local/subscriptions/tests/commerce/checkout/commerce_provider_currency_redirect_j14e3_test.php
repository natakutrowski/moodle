<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_provider_currency_redirect_j14e3_test extends \advanced_testcase {
    public function test_provider_template_contains_currency_redirector(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/provider_experience.mustache'
        );
        $this->assertIsString($template);
        $this->assertStringContainsString('data-provider-currency-dialog', $template);
        $this->assertStringContainsString('switchpurchasecurrency', $template);
        $this->assertStringContainsString('data-provider-currency-choices', $template);
    }

    public function test_provider_javascript_routes_alfa_secondary_action_to_currency_dialog(): void {
        global $CFG;

        $javascript = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/provider_experience.js'
        );
        $this->assertIsString($javascript);
        $this->assertStringContainsString("dialog.dataset.activeProvider === 'alfa'", $javascript);
        $this->assertStringContainsString('openCurrencyDialog(dialog)', $javascript);
        $this->assertStringContainsString('/local/subscriptions/ajax/cart_currencies.php', $javascript);
    }

    public function test_cart_action_supports_express_and_standard_currency_redirects(): void {
        global $CFG;

        $action = file_get_contents($CFG->dirroot . '/local/subscriptions/cart_action.php');
        $this->assertIsString($action);
        $this->assertStringContainsString("\$action === 'switchpurchasecurrency'", $action);
        $this->assertStringContainsString("\$sku !== ''", $action);
        $this->assertStringContainsString('CommerceCartCurrencySwitchService::create()->switch', $action);
    }
}
