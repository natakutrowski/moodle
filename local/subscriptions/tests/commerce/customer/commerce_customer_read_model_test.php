<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\readmodel\CommerceCustomerReadService;

final class commerce_customer_read_model_test extends \advanced_testcase {
    public function test_native_course_digital_bundle_and_upgrade_are_unified(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user(['email' => 'unified@example.test']);
        $now = time();

        $course = $this->insert_purchase('cmp-course', (int)$user->id, $user->email, 'course_access', 'fulfilled', 10000, $now - 400);
        $this->insert_item($course, 0, 'subscription', 'COURSE.A1', 'A1 Full', 10000);
        $this->insert_payment($course, 0, 'paid', 10000, 'EUR', 'stripe', $now - 390);
        $this->insert_grant('grant-course', 'cmp-course', 'course_access', 'COURSE.A1', (int)$user->id, $user->email, $now - 380);

        $digital = $this->insert_purchase('cmp-digital', (int)$user->id, $user->email, 'digital', 'fulfilled', 490, $now - 300);
        $this->insert_item($digital, 0, 'digital', 'DIGITAL.GUIDE', 'Guide PDF', 490);
        $this->insert_payment($digital, 0, 'completed', 490, 'EUR', 'stripe', $now - 290);
        $this->insert_grant('grant-digital', 'cmp-digital', 'digital_download', 'DIGITAL.GUIDE', (int)$user->id, $user->email, $now - 280);

        $bundle = $this->insert_purchase('cmp-bundle', (int)$user->id, $user->email, 'bundle', 'fulfilled', 12900, $now - 200);
        $this->insert_item($bundle, 0, 'bundle', 'BUNDLE.A2', 'Pack A2', 12900);
        $this->insert_payment($bundle, 0, 'paid', 12900, 'EUR', 'alfa', $now - 190);

        $upgrade = $this->insert_purchase('cmp-upgrade', (int)$user->id, $user->email, 'subscription', 'fulfilled', 7000, $now - 100);
        $this->insert_item($upgrade, 0, 'subscription', 'SUB.PLAN.32', 'A2 Grammar → A2 Full', 7000, ['operation' => 'upgrade']);
        $this->insert_payment($upgrade, 0, 'paid', 7000, 'EUR', 'stripe', $now - 90);

        $snapshot = (new CommerceCustomerReadService($DB))->build_for_user((int)$user->id);

        $this->assertSame(4, $snapshot->metrics->purchasecount);
        $this->assertSame(4, $snapshot->metrics->successfulpurchasecount);
        $this->assertSame(30390, $snapshot->metrics->revenuebycurrency['EUR']);
        $this->assertSame(1, $snapshot->metrics->purchasebytype['course']);
        $this->assertSame(1, $snapshot->metrics->purchasebytype['digital']);
        $this->assertSame(1, $snapshot->metrics->purchasebytype['bundle']);
        $this->assertSame(1, $snapshot->metrics->purchasebytype['upgrade']);
        $this->assertSame('upgrade', $snapshot->latest_purchase()?->type);
        $this->assertCount(2, $snapshot->grants);
    }

    public function test_guest_history_is_merged_when_email_is_later_attached_to_user(): void {
        global $DB;
        $this->resetAfterTest(true);
        $email = 'guest-linked@example.test';
        $now = time();

        $guestpurchase = $this->insert_purchase('cmp-guest', null, $email, 'digital', 'fulfilled', 1200, $now - 200, [
            'customerid' => 'guest-customer-1',
            'email' => $email,
        ]);
        $this->insert_item($guestpurchase, 0, 'digital', 'DIGITAL.GUEST', 'Guest guide', 1200);
        $this->insert_payment($guestpurchase, 0, 'paid', 1200, 'EUR', 'stripe', $now - 190);

        $user = $this->getDataGenerator()->create_user(['email' => $email]);
        $linkedpurchase = $this->insert_purchase('cmp-linked', (int)$user->id, $email, 'course_access', 'fulfilled', 5000, $now - 100);
        $this->insert_item($linkedpurchase, 0, 'subscription', 'COURSE.LINKED', 'Linked course', 5000);
        $this->insert_payment($linkedpurchase, 0, 'paid', 5000, 'EUR', 'stripe', $now - 90);

        $snapshot = (new CommerceCustomerReadService($DB))->build_for_email($email);

        $this->assertSame((int)$user->id, $snapshot->identity->userid);
        $this->assertTrue($snapshot->identity->hasguesthistory);
        $this->assertContains('guest-customer-1', $snapshot->identity->customerids);
        $this->assertSame(2, $snapshot->metrics->purchasecount);
        $this->assertSame(1, $snapshot->metrics->guestpurchasecount);
        $this->assertSame(6200, $snapshot->metrics->revenuebycurrency['EUR']);
    }

    public function test_failed_then_successful_payment_counts_one_purchase_and_only_successful_revenue(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $now = time();
        $purchaseid = $this->insert_purchase('cmp-retry', (int)$user->id, $user->email, 'course_access', 'fulfilled', 7000, $now - 100);
        $this->insert_item($purchaseid, 0, 'subscription', 'COURSE.RETRY', 'Retry course', 7000);
        $this->insert_payment($purchaseid, 0, 'failed', 7000, 'EUR', 'stripe', null, $now - 90);
        $this->insert_payment($purchaseid, 1, 'paid', 7000, 'EUR', 'stripe', $now - 80, $now - 80);

        $snapshot = (new CommerceCustomerReadService($DB))->build_for_user((int)$user->id);

        $this->assertSame(1, $snapshot->metrics->purchasecount);
        $this->assertSame(2, $snapshot->metrics->paymentattemptcount);
        $this->assertSame(1, $snapshot->metrics->successfulpaymentcount);
        $this->assertSame(1, $snapshot->metrics->paymentbystatus['failed']);
        $this->assertSame(1, $snapshot->metrics->paymentbystatus['paid']);
        $this->assertSame(7000, $snapshot->metrics->revenuebycurrency['EUR']);
        $this->assertTrue($snapshot->latest_purchase()?->has_failed_payment());
        $this->assertTrue($snapshot->latest_purchase()?->has_successful_payment());
    }

    public function test_email_only_guest_snapshot_does_not_require_moodle_user(): void {
        global $DB;
        $this->resetAfterTest(true);
        $email = 'pure-guest@example.test';
        $now = time();
        $purchaseid = $this->insert_purchase('cmp-pure-guest', null, $email, 'bundle', 'payment_pending', 9900, $now - 100);
        $this->insert_item($purchaseid, 0, 'bundle', 'BUNDLE.GUEST', 'Guest bundle', 9900);
        $this->insert_payment($purchaseid, 0, 'pending', 9900, 'RUB', 'alfa', null, $now - 90);

        $snapshot = (new CommerceCustomerReadService($DB))->build_for_email($email);

        $this->assertTrue($snapshot->identity->is_guest());
        $this->assertSame($email, $snapshot->identity->email);
        $this->assertSame(1, $snapshot->metrics->purchasecount);
        $this->assertSame(0, $snapshot->metrics->successfulpurchasecount);
        $this->assertSame([], $snapshot->metrics->revenuebycurrency);
    }

    private function insert_purchase(
        string $reference,
        ?int $userid,
        string $email,
        string $type,
        string $status,
        int $totalminor,
        int $timecreated,
        array $customer = []
    ): int {
        global $DB;
        $customer += ['userid' => $userid, 'email' => $email];
        return (int)$DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => substr(hash('sha256', $reference), 0, 32),
            'reference' => $reference,
            'type' => $type,
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => $userid,
            'customeremail' => $email,
            'status' => $status,
            'currency' => 'EUR',
            'subtotalminor' => $totalminor,
            'discountminor' => 0,
            'totalminor' => $totalminor,
            'customerjson' => json_encode($customer, JSON_THROW_ON_ERROR),
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $timecreated,
            'timemodified' => $timecreated,
        ]);
    }

    private function insert_item(
        int $purchaseid,
        int $position,
        string $type,
        string $reference,
        string $label,
        int $netminor,
        array $metadata = []
    ): int {
        global $DB;
        return (int)$DB->insert_record('local_subscriptions_commerce_purchase_item', (object)[
            'purchaseid' => $purchaseid,
            'position' => $position,
            'itemtype' => $type,
            'itemreference' => $reference,
            'label' => $label,
            'quantity' => 1,
            'currency' => 'EUR',
            'unitminor' => $netminor,
            'grossminor' => $netminor,
            'discountminor' => 0,
            'netminor' => $netminor,
            'pricingjson' => '{}',
            'fulfillmentjson' => '{}',
            'metadatajson' => json_encode($metadata, JSON_THROW_ON_ERROR),
        ]);
    }

    private function insert_payment(
        int $purchaseid,
        int $sequence,
        string $status,
        int $amountminor,
        string $currency,
        string $provider,
        ?int $paidat,
        ?int $timecreated = null
    ): int {
        global $DB;
        $timecreated ??= time();
        return (int)$DB->insert_record('local_subscriptions_commerce_payment', (object)[
            'purchaseid' => $purchaseid,
            'sequence' => $sequence,
            'provider' => $provider,
            'providerreference' => 'provider-' . $purchaseid . '-' . $sequence,
            'providerorderid' => null,
            'status' => $status,
            'currency' => $currency,
            'amountminor' => $amountminor,
            'transactionid' => $status === 'paid' || $status === 'completed' ? 'tx-' . $purchaseid . '-' . $sequence : null,
            'legacyrequestid' => null,
            'paidat' => $paidat,
            'metadatajson' => '{}',
            'paymenturl' => null,
            'providerpayload' => null,
            'timecreated' => $timecreated,
            'timemodified' => $timecreated,
        ]);
    }

    private function insert_grant(
        string $grantreference,
        string $purchasereference,
        string $type,
        string $productsku,
        int $userid,
        string $email,
        int $timecreated
    ): int {
        global $DB;
        return (int)$DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => $grantreference,
            'idempotencykey' => 'key-' . $grantreference,
            'purchasereference' => $purchasereference,
            'itemreference' => $productsku,
            'productsku' => $productsku,
            'type' => $type,
            'resourcekey' => $type . ':' . $productsku,
            'quantity' => 1,
            'beneficiaryuserid' => $userid,
            'beneficiaryemail' => $email,
            'validfrom' => $timecreated,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => '{}',
            'metadatajson' => '{}',
            'timecreated' => $timecreated,
            'timemodified' => $timecreated,
        ]);
    }
}
