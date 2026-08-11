<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\certification\guest\CommerceGuestCheckoutCertifier;

final class commerce_795h54_guest_checkout_certification_test extends \advanced_testcase {
    public function test_guest_checkout_integration_is_certifiable(): void {
        global $DB;
        $this->resetAfterTest(true);

        $result = (new CommerceGuestCheckoutCertifier($DB, dirname(__DIR__, 3)))->certify();
        $this->assertTrue($result['certified']);
        $this->assertNotContains('FAIL', array_column($result['checks'], 'status'));
    }

    public function test_certification_cli_exists(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/cli/commerce/certification/certify_guest_checkout.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('GUEST CHECKOUT CERTIFIED', $source);
        $this->assertStringContainsString("'session' => ''", $source);
    }
}
