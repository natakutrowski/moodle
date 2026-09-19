<?php
declare(strict_types=1);
namespace local_subscriptions;
use advanced_testcase;
defined('MOODLE_INTERNAL') || die();

final class commerce_796h41_wallet_splash_test extends advanced_testcase {
    public function test_historical_hourglass_splash_is_used_on_main_checkout(): void {
        global $CFG;
        $page = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache');

        $this->assertStringContainsString('payment-provider-transition__hourglass', $page);
        $this->assertStringContainsString('payment-provider-transition__orbit', $page);
        $this->assertStringContainsString('data-checkout-wallet-splash', $page);
    }
}
