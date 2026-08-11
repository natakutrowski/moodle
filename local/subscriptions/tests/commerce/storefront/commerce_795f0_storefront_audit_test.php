<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\storefront\audit\CommerceStorefrontBaselineAuditor;

final class commerce_795f0_storefront_audit_test extends \advanced_testcase {
    public function test_baseline_auditor_detects_current_public_surface(): void {
        $pluginroot = dirname(__DIR__, 3);
        $report = (new CommerceStorefrontBaselineAuditor())->audit($pluginroot);

        self::assertGreaterThanOrEqual(6, $report['publicfilecount']);
        self::assertTrue($report['hasunifiedcatalogue']);
        self::assertTrue($report['hasstorefrontreadmodel']);
        self::assertArrayHasKey('checkout.php', $report['publicfiles']);
        self::assertNotEmpty($report['legacyreferences']);
    }
}
