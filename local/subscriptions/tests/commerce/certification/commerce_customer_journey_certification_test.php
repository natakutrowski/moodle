<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\certification\CommerceCustomerJourneyCertificationFinding;
use local_subscriptions\commerce\certification\CommerceCustomerJourneyCertificationReport;
use local_subscriptions\commerce\certification\CommerceCustomerJourneyCertificationService;

/** Final certification contract for the complete Commerce customer journey. */
final class commerce_customer_journey_certification_test extends \advanced_testcase {
    public function test_report_strict_mode_treats_warnings_as_blocking(): void {
        $report = new CommerceCustomerJourneyCertificationReport();
        $report->add(new CommerceCustomerJourneyCertificationFinding(
            'ok',
            CommerceCustomerJourneyCertificationFinding::OK,
            'OK'
        ));
        $report->add(new CommerceCustomerJourneyCertificationFinding(
            'warning',
            CommerceCustomerJourneyCertificationFinding::WARNING,
            'Warning'
        ));

        $this->assertTrue($report->is_certified(false));
        $this->assertFalse($report->is_certified(true));
        $this->assertSame(1, $report->count(CommerceCustomerJourneyCertificationFinding::OK));
        $this->assertSame(1, $report->count(CommerceCustomerJourneyCertificationFinding::WARNING));
    }

    public function test_final_certification_has_no_structural_errors(): void {
        global $DB;
        $this->resetAfterTest();

        $report = (new CommerceCustomerJourneyCertificationService($DB))->certify(time());
        $errors = array_values(array_filter(
            $report->get_findings(),
            static fn(CommerceCustomerJourneyCertificationFinding $finding): bool =>
                $finding->get_severity() === CommerceCustomerJourneyCertificationFinding::ERROR
        ));

        $this->assertSame([], array_map(
            static fn(CommerceCustomerJourneyCertificationFinding $finding): string =>
                $finding->get_label() . ': ' . $finding->get_detail(),
            $errors
        ));
    }
}
