<?php

declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();
use advanced_testcase;
use local_subscriptions\commerce\rollout\CommerceRolloutCertification;
final class commerce_i10e_certification_test extends advanced_testcase {
    public function test_checklist_covers_payment_providers_admin_and_cron(): void {
        $items = (new CommerceRolloutCertification())->checklist();
        $this->assertArrayHasKey('digital_stripe', $items);
        $this->assertArrayHasKey('digital_alfa', $items);
        $this->assertArrayHasKey('manual_admin', $items);
        $this->assertArrayHasKey('cron', $items);
        $this->assertArrayHasKey('duplicate_callback', $items);
    }
}
