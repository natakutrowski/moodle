<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h5_stripe_card_intent_isolation_test extends \advanced_testcase {

    public function test_inline_card_requests_exact_card_method_only(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_inline_card.js'
        );
        self::assertIsString($js);

        self::assertStringContainsString("'paymentmethod'", $js);
        self::assertStringContainsString("'card'", $js);
        self::assertStringNotContainsString("body.set('paymentmethod', 'link')", preg_replace('/\s+/', ' ', $js));
        self::assertStringNotContainsString("body.set('paymentmethod', 'klarna')", preg_replace('/\s+/', ' ', $js));
    }

}
