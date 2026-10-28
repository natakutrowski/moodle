<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\audience\CommerceMarketingCoursePurchaseProofService;

#[\PHPUnit\Framework\Attributes\CoversClass(CommerceMarketingCoursePurchaseProofService::class)]
final class commerce_marketing_course_purchase_proof_test extends \advanced_testcase {
    private function create_purchase(\stdClass $user, string $reference, string $status): int {
        global $DB;
        $now = time();
        $id = $DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => md5('proof-fixture-' . random_string(12)),
            'reference' => 'prf-' . random_string(12),
            'type' => 'subscription', 'userid' => (int)$user->id,
            'customeremail' => (string)$user->email,
            'status' => $status, 'currency' => 'EUR',
            'subtotalminor' => 28000, 'discountminor' => 0, 'totalminor' => 28000,
            'customerjson' => '{}', 'snapshotjson' => '{}', 'metadatajson' => '{}',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subscriptions_commerce_purchase_item', (object)[
            'purchaseid' => $id, 'position' => 0, 'itemtype' => 'subscription',
            'itemreference' => $reference, 'label' => 'A1 purchase test',
            'quantity' => 1, 'currency' => 'EUR', 'unitminor' => 28000,
            'grossminor' => 28000, 'discountminor' => 0, 'netminor' => 28000,
            'pricingjson' => '{}', 'fulfillmentjson' => '{}', 'metadatajson' => '{}',
        ]);
        return $id;
    }

    public function test_verified_a1_references_include_historical_plan_and_bundles(): void {
        global $DB;
        $this->resetAfterTest(true);
        $proof = new CommerceMarketingCoursePurchaseProofService($DB);
        $refs = $proof->references_for_course(13);
        foreach (['SUB.PLAN.30','SUBSCRIPTION-PLAN:30','BUNDLE.PACK_A1_A2','BUNDLEA1VERBES'] as $ref) {
            self::assertContains($ref, $refs);
            $user = $this->getDataGenerator()->create_user();
            $this->create_purchase($user, $ref, 'fulfilled');
            self::assertTrue($proof->has_verified_purchase((int)$user->id, (string)$user->email, $refs));
        }
    }

    public function test_pending_captured_and_support_only_are_not_valid_purchase_proof(): void {
        global $DB;
        $this->resetAfterTest(true);
        $proof = new CommerceMarketingCoursePurchaseProofService($DB);
        $refs = $proof->references_for_course(13);
        foreach ([
            ['SUB.PLAN.30', 'payment_pending'],
            ['SUB.PLAN.30', 'captured'],
            ['SUB.PLAN.30', 'fulfillment_pending'],
            ['A1-FR-SUPPORT', 'fulfilled'],
        ] as [$ref, $status]) {
            $user = $this->getDataGenerator()->create_user();
            $this->create_purchase($user, $ref, $status);
            self::assertFalse($proof->has_verified_purchase((int)$user->id, (string)$user->email, $refs));
        }
    }

    public function test_unmapped_course_and_unlinked_user_fail_closed(): void {
        global $DB;
        $this->resetAfterTest(true);
        $proof = new CommerceMarketingCoursePurchaseProofService($DB);
        self::assertSame([], $proof->references_for_course(999999));
        $user = $this->getDataGenerator()->create_user();
        self::assertFalse($proof->has_verified_purchase((int)$user->id, (string)$user->email, []));
        self::assertFalse($proof->has_verified_purchase((int)$user->id, (string)$user->email, ['SUB.PLAN.30']));
    }

    public function test_refund_on_fulfilled_order_cancels_purchase_proof(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $purchaseid = $this->create_purchase($user, 'SUB.PLAN.30', 'fulfilled');
        $proof = new CommerceMarketingCoursePurchaseProofService($DB);
        $refs = $proof->references_for_course(13);
        self::assertTrue($proof->has_verified_purchase((int)$user->id, (string)$user->email, $refs));
        $now = time();
        $paymentid = $DB->insert_record('local_subscriptions_commerce_payment', (object)[
            'purchaseid' => $purchaseid, 'sequence' => 0, 'provider' => 'test',
            'status' => 'paid', 'currency' => 'EUR', 'amountminor' => 28000,
            'metadatajson' => '{}', 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subscriptions_commerce_refund', (object)[
            'paymentid' => $paymentid, 'provider' => 'test',
            'idempotencykey' => 'proof-test-' . random_string(15),
            'status' => 'succeeded', 'currency' => 'EUR', 'amountminor' => 28000,
            'metadatajson' => '{}', 'timecreated' => $now, 'timemodified' => $now,
        ]);
        self::assertFalse($proof->has_verified_purchase((int)$user->id, (string)$user->email, $refs));
    }
}
