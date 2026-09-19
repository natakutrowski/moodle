<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g831_alfa_connection_probe_test extends advanced_testcase {
    public function test_connection_probe_treats_missing_synthetic_order_as_authenticated(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/'
            . 'CommercePaymentProviderConnectionTestService.php'
        );

        $this->assertStringContainsString(
            "'заказ не найден'",
            $contents
        );
        $this->assertStringContainsString(
            "'order not found'",
            $contents
        );
        $this->assertStringContainsString(
            "'доступ запрещ'",
            $contents
        );

        $successpos = strpos(
            $contents,
            "'заказ не найден'"
        );
        $failurepos = strpos(
            $contents,
            "'доступ запрещ'"
        );

        $this->assertNotFalse($successpos);
        $this->assertNotFalse($failurepos);
        $this->assertLessThan(
            $failurepos,
            $successpos
        );
    }
}
