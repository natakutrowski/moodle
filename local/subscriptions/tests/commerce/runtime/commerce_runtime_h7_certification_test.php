<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\CommerceShadowSource;
use local_subscriptions\commerce\shadow\runtime\CommerceShadowTriggerContext;
use local_subscriptions\commerce\shadow\runtime\CommerceShadowTriggerPolicy;
use local_subscriptions\commerce\shadow\certification\CommerceRuntimeH7CertificationAuditor;

final class commerce_runtime_h7_certification_test extends \advanced_testcase {
    public function test_dualwrite_trigger_context_is_precise_for_checkout_and_repair_paths(): void {
        $digital = CommerceShadowTriggerContext::from_dualwrite('digital', 'digital_checkout_persisted');
        $this->assertSame(CommerceShadowSource::CHECKOUT_DIGITAL, $digital->get_source());
        $this->assertSame('checkout.digital.digital_checkout_persisted', $digital->get_entrypoint());

        $subscription = CommerceShadowTriggerContext::from_dualwrite(
            'subscription',
            'subscription_postpayment_completed'
        );
        $this->assertSame(CommerceShadowSource::CHECKOUT_SUBSCRIPTION, $subscription->get_source());
        $this->assertSame(
            'checkout.subscription.subscription_postpayment_completed',
            $subscription->get_entrypoint()
        );

        $repair = CommerceShadowTriggerContext::from_dualwrite('subscription', 'paid_payment_request_repair');
        $this->assertSame(CommerceShadowSource::REPAIR_JOB, $repair->get_source());
        $this->assertSame('cli.repair.subscription', $repair->get_entrypoint());
    }

    public function test_shadow_trigger_policy_skips_pre_payment_digital_projection(): void {
        $this->assertFalse(CommerceShadowTriggerPolicy::should_observe('digital', 'digital_checkout_persisted'));
        $this->assertFalse(CommerceShadowTriggerPolicy::should_observe('digital', 'legacy_digital_payment_failed'));
        $this->assertTrue(CommerceShadowTriggerPolicy::should_observe('digital', 'legacy_digital_checkout_completed'));
        $this->assertTrue(CommerceShadowTriggerPolicy::should_observe('digital', 'digital_postpayment_completed'));
        $this->assertTrue(CommerceShadowTriggerPolicy::should_observe('subscription', 'subscription_postpayment_completed'));
    }

    public function test_h7_auditor_requires_both_families_and_accepts_equivalent_results(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('commerce_runtime_mode', 'shadow', 'local_subscriptions');
        set_config('commerce_fulfillment_shadow_enabled', 1, 'local_subscriptions');
        $since = time() - 10;

        $subscription = $this->insert_purchase('subscription', 91, 'cmp-h7-subscription');
        $digital = $this->insert_purchase('digital', 92, 'cmp-h7-digital');
        $this->insert_shadow($subscription, CommerceShadowSource::CHECKOUT_SUBSCRIPTION, 'checkout.subscription.test');
        $this->insert_shadow($digital, CommerceShadowSource::CHECKOUT_DIGITAL, 'checkout.digital.test');

        $report = (new CommerceRuntimeH7CertificationAuditor())->audit($since, null);

        $this->assertTrue($report['checks']['subscription_observed']);
        $this->assertTrue($report['checks']['digital_observed']);
        $this->assertTrue($report['checks']['no_business_difference']);
        $this->assertTrue($report['checks']['no_duplicate_purchase']);
        $this->assertTrue($report['checks']['precise_entrypoints']);
        $this->assertTrue($report['certified']);
    }

    private function insert_purchase(string $family, int $legacyid, string $reference): string {
        global $DB;
        $now = time();
        $DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => md5($reference),
            'reference' => $reference,
            'type' => $family,
            'legacyfamily' => $family,
            'legacyid' => $legacyid,
            'userid' => 113,
            'customeremail' => 'h7@example.com',
            'status' => 'fulfilled',
            'currency' => 'EUR',
            'subtotalminor' => 100,
            'discountminor' => 0,
            'totalminor' => 100,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        return $reference;
    }

    private function insert_shadow(string $reference, string $source, string $entrypoint): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_subs_commerce_shadow', (object)[
            'executionreference' => md5($reference . $source),
            'purchasereference' => $reference,
            'source' => $source,
            'entrypoint' => $entrypoint,
            'comparisonstatus' => 'equivalent',
            'classification' => 'representation_only',
            'legacyjson' => '{}',
            'nativejson' => '{}',
            'differencesjson' => '[]',
            'timestarted' => $now,
            'timefinished' => $now,
            'timecreated' => $now,
        ]);
    }
}
