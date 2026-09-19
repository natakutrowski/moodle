<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\provider\paypal\PayPalGatewayConfiguration;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f1_paypal_configuration_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_sandbox_is_the_safe_default(): void {
        $configuration = new PayPalGatewayConfiguration();

        $this->assertSame(
            PayPalGatewayConfiguration::ENV_SANDBOX,
            $configuration->get_environment()
        );
        $this->assertSame(
            'https://api-m.sandbox.paypal.com',
            $configuration->get_api_base()
        );
        $this->assertFalse(
            $configuration->is_configured()
        );
    }

    public function test_environment_specific_credentials_are_resolved(): void {
        set_config(
            'paypal_env',
            'sandbox',
            'local_subscriptions'
        );
        set_config(
            'paypal_sandbox_client_id',
            'sandbox-client',
            'local_subscriptions'
        );
        set_config(
            'paypal_sandbox_client_secret',
            'sandbox-secret',
            'local_subscriptions'
        );

        $configuration = new PayPalGatewayConfiguration();

        $this->assertTrue($configuration->is_configured());
        $this->assertSame(
            'sandbox-client',
            $configuration->get_client_id()
        );
        $this->assertSame(
            'sandbox-secret',
            $configuration->get_client_secret()
        );

        set_config(
            'paypal_env',
            'live',
            'local_subscriptions'
        );
        set_config(
            'paypal_live_client_id',
            'live-client',
            'local_subscriptions'
        );
        set_config(
            'paypal_live_client_secret',
            'live-secret',
            'local_subscriptions'
        );

        $configuration = new PayPalGatewayConfiguration();

        $this->assertSame(
            PayPalGatewayConfiguration::ENV_LIVE,
            $configuration->get_environment()
        );
        $this->assertSame(
            'https://api-m.paypal.com',
            $configuration->get_api_base()
        );
        $this->assertSame(
            'live-client',
            $configuration->get_client_id()
        );
    }
}
