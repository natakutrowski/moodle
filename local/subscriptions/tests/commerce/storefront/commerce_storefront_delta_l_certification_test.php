<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\certification\CommerceStorefrontDeltaLCertificationService;

/**
 * @covers \local_subscriptions\commerce\storefront\certification\CommerceStorefrontDeltaLCertificationService
 */
final class commerce_storefront_delta_l_certification_test extends advanced_testcase {
    public function test_delta_l_contract_is_fully_green(): void {
        $report = (new CommerceStorefrontDeltaLCertificationService())->certify();

        $failures = array_values(array_filter(
            $report['checks'],
            static fn(array $check): bool => !$check['ok']
        ));

        self::assertSame(
            [],
            $failures,
            "Delta L certification failures:\n"
                . implode(
                    "\n",
                    array_map(
                        static fn(array $check): string =>
                            '[' . $check['scope'] . '] '
                                . $check['label']
                                . ': '
                                . $check['detail'],
                        $failures
                    )
                )
        );
        self::assertSame(0, $report['errors']);
        self::assertSame('GREEN', $report['status']);
    }
}
