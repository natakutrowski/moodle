<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h10_alfa_widget_gateway_contract_test extends advanced_testcase {
    public function test_gateway_has_non_registering_widget_preparation_contract(): void {
        global $CFG;

        $port = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/alfa/'
            . 'AlfaPaymentGateway.php'
        );
        $bridge = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/alfa/'
            . 'LegacyAlfaPaymentGateway.php'
        );

        $this->assertStringContainsString(
            'public function prepare_widget(',
            $port
        );
        $this->assertStringContainsString(
            'public function prepare_widget(',
            $bridge
        );
        $this->assertStringContainsString(
            "'embedded_type' => 'alfa_widget'",
            $bridge
        );
        $this->assertStringContainsString(
            "'widget_amount_format' => 'kopeyki'",
            $bridge
        );
    }

    public function test_alfa_provider_maps_widget_to_embedded_action(): void {
        global $CFG;

        $provider = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/alfa/'
            . 'AlfaCommercePaymentProvider.php'
        );

        $this->assertStringContainsString(
            'AlfaWidgetConfiguration::is_available()',
            $provider
        );
        $this->assertStringContainsString(
            '$this->gateway->prepare_widget(',
            $provider
        );
        $this->assertStringContainsString(
            'CommercePaymentAction::embedded(',
            $provider
        );
    }
}
