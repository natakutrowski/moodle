<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h9_stripe_express_intent_method_test extends \advanced_testcase {

    public function test_express_intent_uses_exact_selected_stripe_rail(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        self::assertIsString($js);

        self::assertStringContainsString('selectedExpressMethod(', $js);
        self::assertStringContainsString("body.set(\n        'paymentmethod',\n        method", $js);
        self::assertStringContainsString("allowed(\n                        config,\n                        method", $js);
    }

}
