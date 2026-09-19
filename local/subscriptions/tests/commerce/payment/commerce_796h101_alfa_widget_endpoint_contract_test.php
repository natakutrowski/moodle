<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\provider\alfa\AlfaWidgetConfiguration;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h101_alfa_widget_endpoint_contract_test extends advanced_testcase {
    public function test_test_environment_uses_official_alfa_widget_endpoint(): void {
        $this->resetAfterTest();

        set_config('alfa_env', 'test', 'local_subscriptions');

        $this->assertSame(
            'https://testpay.alfabank.ru/assets/alfa-payment.js',
            AlfaWidgetConfiguration::script_url()
        );
        $this->assertSame(
            'test',
            AlfaWidgetConfiguration::gateway()
        );
    }

    public function test_live_environment_uses_official_alfa_widget_endpoint(): void {
        $this->resetAfterTest();

        set_config('alfa_env', 'live', 'local_subscriptions');

        $this->assertSame(
            'https://acspayzonaecom.com/assets/alfa-payment.js',
            AlfaWidgetConfiguration::script_url()
        );
        $this->assertSame(
            'payment',
            AlfaWidgetConfiguration::gateway()
        );
    }
}
