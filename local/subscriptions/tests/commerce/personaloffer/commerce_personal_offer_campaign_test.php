<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignRequest;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignService;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;

final class commerce_personal_offer_campaign_test extends advanced_testcase {
    public function test_dry_run_then_execute_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest(true);
        $sourceid = $this->product('CARDS', 'Cards');
        $targetid = $this->product('TRAINER', 'Trainer');
        $purchaseid = $this->paid_purchase('buyer@example.com', 'CARDS');
        $request = new CommercePersonalOfferCampaignRequest('trainer-launch', 'CARDS', $targetid, CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]));
        $service = CommercePersonalOfferCampaignService::create($DB);
        $dry = $service->run($request, false);
        $this->assertSame(1, $dry['summary']['eligible']);
        $this->assertSame(0, $DB->count_records('local_subs_commerce_offer'));
        $first = $service->run($request, true);
        $this->assertSame(1, $first['summary']['issued']);
        $this->assertSame($purchaseid, $first['rows'][0]['purchaseid']);
        $this->assertNotEmpty($first['rows'][0]['url']);
        $second = $service->run($request, true);
        $this->assertSame(1, $second['summary']['replayed']);
        $this->assertSame(1, $DB->count_records('local_subs_commerce_offer'));
    }

    public function test_customer_already_owning_target_is_excluded(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->product('CARDS', 'Cards');
        $targetid = $this->product('TRAINER', 'Trainer');
        $this->paid_purchase('buyer@example.com', 'CARDS');
        $this->paid_purchase('buyer@example.com', 'TRAINER');
        $request = new CommercePersonalOfferCampaignRequest('trainer-launch', 'CARDS', $targetid, CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]));
        $result = CommercePersonalOfferCampaignService::create($DB)->run($request, false);
        $this->assertSame(1, $result['summary']['excluded_target_owned']);
        $this->assertSame(0, $result['summary']['eligible']);
    }

    private function product(string $sku, string $name): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku, 'type' => 'digital', 'status' => 'active', 'name' => $name,
            'description' => null, 'metadatajson' => '{}', 'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
    }

    private function paid_purchase(string $email, string $sku): int {
        global $DB;
        $now = time(); $suffix = bin2hex(random_bytes(5));
        $id = (int)$DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => bin2hex(random_bytes(16)), 'reference' => 'TEST-' . strtoupper($suffix), 'type' => 'digital',
            'legacyfamily' => null, 'legacyid' => null, 'userid' => null, 'customeremail' => $email, 'status' => 'completed',
            'currency' => 'EUR', 'subtotalminor' => 1000, 'discountminor' => 0, 'totalminor' => 1000,
            'customerjson' => '{}', 'snapshotjson' => '{}', 'metadatajson' => '{}', 'snapshotversion' => 1,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subscriptions_commerce_purchase_item', (object)[
            'purchaseid' => $id, 'position' => 0, 'itemtype' => 'digital', 'itemreference' => $sku, 'label' => $sku,
            'quantity' => 1, 'currency' => 'EUR', 'unitminor' => 1000, 'grossminor' => 1000, 'discountminor' => 0,
            'netminor' => 1000, 'pricingjson' => '{}', 'fulfillmentjson' => '{}', 'metadatajson' => '{}',
        ]);
        $DB->insert_record('local_subscriptions_commerce_payment', (object)[
            'purchaseid' => $id, 'sequence' => 0, 'provider' => 'test', 'providerreference' => null, 'providerorderid' => null,
            'status' => 'paid', 'currency' => 'EUR', 'amountminor' => 1000, 'transactionid' => 'tx-' . $suffix,
            'legacyrequestid' => null, 'paidat' => $now, 'metadatajson' => '{}', 'paymenturl' => null, 'providerpayload' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        return $id;
    }
}
