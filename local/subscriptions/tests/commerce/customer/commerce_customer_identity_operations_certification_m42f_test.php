<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\certification\CommerceCustomerIdentityOperationsCertificationService;

/**
 * @covers \local_subscriptions\commerce\customer\certification\CommerceCustomerIdentityOperationsCertificationService
 */
final class commerce_customer_identity_operations_certification_m42f_test extends advanced_testcase {
    public function test_identity_operations_contract_is_green(): void {
        $report = (
            new CommerceCustomerIdentityOperationsCertificationService()
        )->certify();

        $failures = array_values(array_filter(
            $report['checks'],
            static fn(array $check): bool => !$check['ok']
        ));

        self::assertSame(
            [],
            $failures,
            "Identity Operations certification failures:\n"
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
