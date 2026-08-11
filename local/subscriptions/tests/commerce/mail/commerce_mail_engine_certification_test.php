<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\certification\CommerceMailCertificationFinding;
use local_subscriptions\commerce\mail\certification\CommerceMailCertificationReport;
use local_subscriptions\commerce\mail\certification\CommerceMailEngineCertificationService;

final class commerce_mail_engine_certification_test extends \advanced_testcase {

    public function test_report_distinguishes_warnings_from_errors(): void {
        $report = new CommerceMailCertificationReport();
        $report->add(new CommerceMailCertificationFinding('one', CommerceMailCertificationFinding::OK, 'One'));
        $report->add(new CommerceMailCertificationFinding('two', CommerceMailCertificationFinding::WARNING, 'Two'));

        $this->assertTrue($report->is_certified());
        $this->assertFalse($report->is_certified(true));
        $this->assertSame(1, $report->count(CommerceMailCertificationFinding::OK));
        $this->assertSame(1, $report->count(CommerceMailCertificationFinding::WARNING));
        $this->assertSame(0, $report->count(CommerceMailCertificationFinding::ERROR));
    }

    public function test_installed_engine_passes_structural_certification(): void {
        global $DB;
        $this->resetAfterTest();

        $report = (new CommerceMailEngineCertificationService($DB))->certify(time());
        $findings = [];
        foreach ($report->get_findings() as $finding) {
            $findings[$finding->get_code()] = $finding;
        }

        $this->assertArrayHasKey('schema.local_subs_commerce_mail', $findings);
        $this->assertArrayHasKey('schema.local_subs_commerce_mail_tpl', $findings);
        $this->assertArrayHasKey('runtime.templates', $findings);
        $this->assertArrayHasKey('templates.defaults', $findings);
        $this->assertArrayHasKey('delivery.events', $findings);
        $this->assertArrayHasKey('components.required', $findings);

        foreach ([
            'schema.local_subs_commerce_mail',
            'schema.local_subs_commerce_mail_tpl',
            'runtime.templates',
            'templates.defaults',
            'delivery.events',
            'components.required',
        ] as $code) {
            $this->assertSame(
                CommerceMailCertificationFinding::OK,
                $findings[$code]->get_severity(),
                $code . ': ' . $findings[$code]->get_detail()
            );
        }
    }
}
