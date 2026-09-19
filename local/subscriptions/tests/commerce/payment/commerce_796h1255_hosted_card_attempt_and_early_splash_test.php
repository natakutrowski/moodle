<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1255_hosted_card_attempt_and_early_splash_test extends \advanced_testcase {

    public function test_alfa_widget_uses_official_widget_bootstrap_without_cross_origin_dom_poking(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString('wakeOfficialWidget', $amd);
        self::assertStringContainsString('waitForOfficialButton', $amd);
        self::assertStringNotContainsString('iframe.contentDocument', $amd);
        self::assertStringNotContainsString('button[data-test-id="payByCard"]', $amd);
        self::assertStringNotContainsString('button.click();', $amd);
    }


    public function test_embedded_return_pushes_parent_splash_before_reconciliation(): void {
        global $CFG;

        $return = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/payment/return.php'
        );
        self::assertIsString($return);

        $splash = strpos($return, 'campus-payment-finalizing-overlay');
        $resolver = strpos($return, '$resolver = new CommercePaymentReturnResolver');
        self::assertNotFalse($splash);
        self::assertNotFalse($resolver);
        self::assertLessThan($resolver, $splash);
        self::assertStringContainsString('@flush();', $return);
        self::assertStringContainsString('$embeddedearlysplash = true;', $return);
    }

}
