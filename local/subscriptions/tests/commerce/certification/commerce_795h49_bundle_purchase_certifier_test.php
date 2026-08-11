<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\certification\bundle\CommerceBundlePurchaseCertifier;

final class commerce_795h49_bundle_purchase_certifier_test extends advanced_testcase {
    public function test_missing_purchase_is_not_certified(): void {
        global $DB;
        $this->resetAfterTest(true);

        $report = (new CommerceBundlePurchaseCertifier($DB))->certify('cmp_missing');

        $this->assertFalse($report->certified);
        $this->assertSame('unknown', $report->scenario);
        $this->assertSame('purchase', $report->checks[0]['key']);
        $this->assertSame('FAIL', $report->checks[0]['status']);
    }

    public function test_detects_the_three_supported_bundle_scenarios(): void {
        $this->assertSame('mixed', CommerceBundlePurchaseCertifier::detect_scenario([
            'course_access',
            'digital_download',
        ]));
        $this->assertSame('courses', CommerceBundlePurchaseCertifier::detect_scenario([
            'course_access',
            'course_access',
        ]));
        $this->assertSame('digitals', CommerceBundlePurchaseCertifier::detect_scenario([
            'digital_download',
            'digital_download',
        ]));
        $this->assertSame('unknown', CommerceBundlePurchaseCertifier::detect_scenario([
            'course_access',
        ]));
    }
}
