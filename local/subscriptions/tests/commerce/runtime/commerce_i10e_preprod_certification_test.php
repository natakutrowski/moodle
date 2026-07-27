<?php

declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();
use advanced_testcase;
use local_subscriptions\commerce\rollout\CommerceCertificationEvidence;
use local_subscriptions\commerce\rollout\CommercePreprodCertificationReport;
final class commerce_i10e_preprod_certification_test extends advanced_testcase {
 public function test_report_requires_every_item(): void {
  $report=new CommercePreprodCertificationReport([new CommerceCertificationEvidence('a',true),new CommerceCertificationEvidence('b',false)]);
  $this->assertFalse($report->is_ready(['a','b'])); $this->assertSame(['b'],$report->missing(['a','b']));
 }
}
