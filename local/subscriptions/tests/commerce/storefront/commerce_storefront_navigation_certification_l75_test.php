<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\certification\CommerceStorefrontNavigationCertificationService;

/**
 * @covers \local_subscriptions\commerce\storefront\certification\CommerceStorefrontNavigationCertificationService
 */
final class commerce_storefront_navigation_certification_l75_test extends advanced_testcase {
    public function test_l7_navigation_contract_is_green(): void {
        $report = (new CommerceStorefrontNavigationCertificationService())->certify();

        $failures = array_values(array_filter(
            $report['checks'],
            static fn(array $check): bool => !$check['ok']
        ));

        self::assertSame(
            [],
            $failures,
            "L7 navigation certification failures:\n"
                . implode(
                    "\n",
                    array_map(
                        static fn(array $check): string =>
                            $check['label'] . ': ' . $check['detail'],
                        $failures
                    )
                )
        );
        self::assertSame(0, $report['errors']);
        self::assertSame('GREEN', $report['status']);
    }
}
