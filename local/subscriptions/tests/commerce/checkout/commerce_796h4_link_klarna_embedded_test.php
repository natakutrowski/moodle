<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h4_link_klarna_embedded_test extends \advanced_testcase {

    public function test_payment_method_types_are_derived_from_authorized_express_pool(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        self::assertIsString($js);

        self::assertStringContainsString('const expressPaymentMethodTypes = config =>', $js);
        self::assertStringContainsString("types.push('card')", $js);
        self::assertStringContainsString("types.push('link')", $js);
        self::assertStringContainsString("types.push('klarna')", $js);
    }

}
