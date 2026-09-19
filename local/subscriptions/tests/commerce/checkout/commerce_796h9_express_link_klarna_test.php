<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h9_express_link_klarna_test extends \advanced_testcase {

    public function test_express_executor_supports_link_and_klarna_natively(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        self::assertIsString($js);

        self::assertStringContainsString("allowed(config, 'link')", $js);
        self::assertStringContainsString("allowed(config, 'klarna')", $js);
        self::assertStringContainsString("types.push('link')", $js);
        self::assertStringContainsString("types.push('klarna')", $js);
        self::assertStringContainsString('selectedExpressMethod(', $js);
    }

}
