<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

/** Structural certification of the protected I3 access endpoint. */
final class commerce_795i3_access_endpoint_test extends advanced_testcase {
    public function test_endpoint_revalidates_order_and_hides_native_download_token(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/order_access.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('CommerceOrderPresentationService::create()', $source);
        $this->assertStringContainsString('CommerceGuestCheckoutSessionRepository', $source);
        $this->assertStringContainsString("'grantreference' => \$grantreference", $source);
        $this->assertStringContainsString('CommerceNativeDigitalDownloadResolver', $source);
        $this->assertStringNotContainsString("required_param('token'", $source);
    }
}
