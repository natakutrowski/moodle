<?php
namespace local_subscriptions\payment;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\payment\stripe\StripeConfiguration;

/**
 * @covers \local_subscriptions\payment\stripe\StripeConfiguration
 */
final class stripe_configuration_test extends advanced_testcase {
    public function test_legacy_live_value_maps_to_live_ei(): void {
        $this->assertSame(StripeConfiguration::LIVE_EI, StripeConfiguration::normalise('live'));
        $this->assertSame('stripe_live', StripeConfiguration::config_prefix('live'));
    }

    public function test_profiles_use_expected_configuration_prefixes(): void {
        $this->assertSame('stripe_test', StripeConfiguration::config_prefix('test'));
        $this->assertSame('stripe_live', StripeConfiguration::config_prefix('live_ei'));
        $this->assertSame('stripe_live_sas', StripeConfiguration::config_prefix('live_sas'));
    }

    public function test_active_profile_reads_selected_configuration(): void {
        $this->resetAfterTest();

        set_config('stripe_env', 'live_sas', 'local_subscriptions');
        set_config('stripe_live_sas_secret', 'sk_live_sas_example', 'local_subscriptions');
        set_config('stripe_live_sas_webhook_secret', 'whsec_sas_example', 'local_subscriptions');

        $config = StripeConfiguration::get();

        $this->assertSame('live_sas', $config['profile']);
        $this->assertSame('live', $config['mode']);
        $this->assertSame('sk_live_sas_example', $config['secret_key']);
        $this->assertSame('whsec_sas_example', $config['webhook_secret']);
    }
}
