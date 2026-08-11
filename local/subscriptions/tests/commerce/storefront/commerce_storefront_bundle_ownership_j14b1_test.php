<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Static contract for effective bundle ownership in Storefront. */
final class commerce_storefront_bundle_ownership_j14b1_test extends \advanced_testcase {
    public function test_resolver_checks_bundle_components(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/ownership/CommerceStorefrontOwnershipResolver.php');
        $this->assertStringContainsString('owns_bundle_components', $source);
        $this->assertStringContainsString("return 'bundle_components'", $source);
    }
}
