<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\course\certification\CommerceMyCoursesCertificationFinding;
use local_subscriptions\commerce\course\certification\CommerceMyCoursesCertificationReport;
use local_subscriptions\commerce\course\certification\CommerceMyCoursesCertificationService;

final class commerce_my_courses_certification_test extends advanced_testcase {
    public function test_report_strict_mode_treats_warnings_as_failures(): void {
        $report = new CommerceMyCoursesCertificationReport();
        $report->add(new CommerceMyCoursesCertificationFinding(
            CommerceMyCoursesCertificationFinding::OK,
            'Core contract'
        ));
        $report->add(new CommerceMyCoursesCertificationFinding(
            CommerceMyCoursesCertificationFinding::WARNING,
            'Optional configuration'
        ));

        $this->assertTrue($report->is_certified(false));
        $this->assertFalse($report->is_certified(true));
        $this->assertSame(1, $report->count(CommerceMyCoursesCertificationFinding::OK));
        $this->assertSame(1, $report->count(CommerceMyCoursesCertificationFinding::WARNING));
    }

    public function test_current_codebase_satisfies_static_certification_contracts(): void {
        global $DB;
        $this->resetAfterTest();

        // The database may not contain commercial upgrade fixtures in a fresh PHPUnit schema.
        // This is intentionally a warning in non-strict mode; structural errors remain blocking.
        $report = (new CommerceMyCoursesCertificationService($DB))->certify();

        $this->assertSame(0, $report->count(CommerceMyCoursesCertificationFinding::ERROR));
        $this->assertTrue($report->is_certified(false));
        $this->assertGreaterThanOrEqual(6, $report->count(CommerceMyCoursesCertificationFinding::OK));
    }
}
