<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Static contract for J14B.1 smart legal and stable product routes. */
final class commerce_smart_urls_j14b1_test extends \advanced_testcase {
    public function test_router_contains_smart_legal_routes(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/public_router.php');
        $this->assertStringContainsString("['terms', 'privacy']", $source);
        $this->assertStringContainsString('Region::policyUrls()', $source);
    }
}
