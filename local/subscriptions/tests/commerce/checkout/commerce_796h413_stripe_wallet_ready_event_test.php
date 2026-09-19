<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h413_stripe_wallet_ready_event_test extends advanced_testcase {
    public function test_wallet_state_is_resolved_from_current_stripe_availability_event(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        $this->assertStringContainsString("'availablepaymentmethodschange'", $js);
        $this->assertStringContainsString('({paymentMethods})', $js);
        $this->assertStringContainsString('setResolvedState(', $js);
        $this->assertStringNotContainsString("express.on('ready'", $js);
    }
}
