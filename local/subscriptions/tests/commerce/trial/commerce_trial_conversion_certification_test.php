<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\trial\CommerceTrialConversionCertificationService;

/** @covers \local_subscriptions\commerce\trial\CommerceTrialConversionCertificationService */
final class commerce_trial_conversion_certification_test extends \advanced_testcase {
    public function test_certification_has_no_structural_errors(): void {
        $this->resetAfterTest(true);

        $findings = (new CommerceTrialConversionCertificationService())->certify();
        $errors = array_values(array_filter(
            $findings,
            static fn(array $finding): bool => $finding['status'] === 'error'
        ));

        $this->assertSame([], $errors);
    }
}
