<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\analytics\CommerceCustomerAnalyticsRepository;
use local_subscriptions\dashboard\repositories\DashboardStatsRepository;
use local_subscriptions\dashboard\funnel\DashboardFunnelRepository;

final class commerce_customer_analytics_test extends \advanced_testcase {
    public function test_native_dashboard_metrics_deduplicate_successful_attempts_and_classify_products(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $user = $this->getDataGenerator()->create_user(['email' => 'analytics@example.test']);

        $course = $this->purchase('analytics-course', (int)$user->id, $user->email, 'course_access', 'fulfilled', $now - 300);
        $this->item($course, 'subscription', 'COURSE.A1', []);
        $this->payment($course, 0, 'failed', 10000, 'EUR', $now - 290, null);
        $this->payment($course, 1, 'paid', 10000, 'EUR', $now - 280, $now - 280);

        $digital = $this->purchase('analytics-digital', null, 'guest-analytics@example.test', 'digital', 'fulfilled', $now - 200, ['guest' => true]);
        $this->item($digital, 'digital', 'DIGITAL.GUIDE', []);
        $this->payment($digital, 0, 'completed', 490, 'EUR', $now - 190, $now - 190);

        $upgrade = $this->purchase('analytics-upgrade', (int)$user->id, $user->email, 'subscription', 'fulfilled', $now - 100);
        $this->item($upgrade, 'subscription', 'SUB.PLAN.32', ['operation' => 'upgrade']);
        $this->payment($upgrade, 0, 'paid', 7000, 'EUR', $now - 90, $now - 90);

        $snapshot = (new CommerceCustomerAnalyticsRepository($DB))->snapshot($now - 600, $now + 60);
        $this->assertSame(3, $snapshot->purchasecount);
        $this->assertSame(3, $snapshot->successfulpurchasecount);
        $this->assertSame(17490, $snapshot->revenuebycurrency['EUR']);
        $this->assertSame(10000, $snapshot->revenuebytypecurrency['course']['EUR']);
        $this->assertSame(490, $snapshot->revenuebytypecurrency['digital']['EUR']);
        $this->assertSame(7000, $snapshot->revenuebytypecurrency['upgrade']['EUR']);
        $this->assertSame(1, $snapshot->purchasesbytype['upgrade']);
        $this->assertSame(1, $snapshot->digitalbuyercount);
        $this->assertSame(1, $snapshot->guestpurchasecount);
        $this->assertSame(2, $snapshot->newcustomercount);

        $stats = new DashboardStatsRepository(new CommerceCustomerAnalyticsRepository($DB));
        $this->assertSame(2, $stats->count_new_customers($now - 600, $now + 60));
        $this->assertSame(1, $stats->count_digital_purchases($now - 600, $now + 60));
        $revenues = $stats->get_revenue_by_currency($now - 600, $now + 60);
        $this->assertSame(174.9, $revenues['EUR']->total());

        $funnel = new DashboardFunnelRepository(new CommerceCustomerAnalyticsRepository($DB));
        $funnelsnapshot = $funnel->snapshot($now - 600, $now + 60);
        $this->assertSame(2, $funnelsnapshot->newcustomers);
        $this->assertSame(1, $funnelsnapshot->digitalbuyers);
    }

    private function purchase(string $reference, ?int $userid, string $email, string $type, string $status, int $timecreated, array $customer = []): int {
        global $DB;
        $customer += ['userid' => $userid, 'email' => $email];
        return (int)$DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => substr(hash('sha256', $reference), 0, 32), 'reference' => $reference, 'type' => $type,
            'legacyfamily' => null, 'legacyid' => null, 'userid' => $userid, 'customeremail' => $email,
            'status' => $status, 'currency' => 'EUR', 'subtotalminor' => 0, 'discountminor' => 0, 'totalminor' => 0,
            'customerjson' => json_encode($customer, JSON_THROW_ON_ERROR), 'snapshotjson' => '{}', 'metadatajson' => '{}',
            'snapshotversion' => 1, 'timecreated' => $timecreated, 'timemodified' => $timecreated,
        ]);
    }

    private function item(int $purchaseid, string $type, string $reference, array $metadata): void {
        global $DB;
        $DB->insert_record('local_subscriptions_commerce_purchase_item', (object)[
            'purchaseid' => $purchaseid, 'position' => 0, 'itemtype' => $type, 'itemreference' => $reference,
            'label' => $reference, 'quantity' => 1, 'currency' => 'EUR', 'unitminor' => 0, 'grossminor' => 0,
            'discountminor' => 0, 'netminor' => 0, 'pricingjson' => '{}', 'fulfillmentjson' => '{}',
            'metadatajson' => json_encode($metadata, JSON_THROW_ON_ERROR),
        ]);
    }

    private function payment(int $purchaseid, int $sequence, string $status, int $amountminor, string $currency, int $timecreated, ?int $paidat): void {
        global $DB;
        $DB->insert_record('local_subscriptions_commerce_payment', (object)[
            'purchaseid' => $purchaseid, 'sequence' => $sequence, 'provider' => 'stripe',
            'providerreference' => 'provider-' . $purchaseid . '-' . $sequence, 'providerorderid' => null,
            'status' => $status, 'currency' => $currency, 'amountminor' => $amountminor,
            'transactionid' => $paidat ? 'tx-' . $purchaseid . '-' . $sequence : null, 'legacyrequestid' => null,
            'paidat' => $paidat, 'metadatajson' => '{}', 'paymenturl' => null, 'providerpayload' => null,
            'timecreated' => $timecreated, 'timemodified' => $timecreated,
        ]);
    }
}
