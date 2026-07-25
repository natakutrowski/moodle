<?php

declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();
use advanced_testcase;
use local_subscriptions\commerce\rollout\CommerceRuntimePathRegistry;
final class commerce_i10e_runtime_path_registry_test extends advanced_testcase {
    public function test_registry_contains_critical_payment_paths(): void {
        $keys = array_map(static fn($path): string => $path->get_key(), (new CommerceRuntimePathRegistry())->all());
        $this->assertContains('digital_checkout', $keys);
        $this->assertContains('digital_postpayment', $keys);
        $this->assertContains('subscription_postpayment', $keys);
        $this->assertContains('repair_job', $keys);
    }
}
