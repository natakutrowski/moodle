<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\order\presentation\CommerceOrderItemPresentation;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;
use local_subscriptions\commerce\order\presentation\CommercePostPaymentStateResolver;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionPolicy;
use local_subscriptions\commerce\purchase\policy\CommercePurchaseEntitlementExpectationPolicy;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseCustomer;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

/** 7.98B.3.1 — entitlement-free Donation purchases have a terminal post-payment UX. */
final class commerce_798b31_donation_postpayment_ux_test extends \advanced_testcase {
    public function test_paid_fulfilled_donation_is_success_without_accesses(): void {
        $policy = new CommercePurchaseEntitlementExpectationPolicy();
        $order = $this->order([
            $this->item('DONATION.ALF.B31', 'service', 'Donation', 'donation'),
        ], 'fulfilled', 'paid');

        self::assertFalse($policy->order_requires_fulfillment($order));
        self::assertTrue($policy->order_is_donation_only($order));

        $state = (new CommercePostPaymentStateResolver($policy))->resolve($order);
        self::assertSame('success', $state->code);
        self::assertFalse($state->showaccesses);
    }

    public function test_mixed_course_and_donation_still_waits_for_course_fulfillment(): void {
        $policy = new CommercePurchaseEntitlementExpectationPolicy();
        $order = $this->order([
            $this->item('COURSE.A1.B31', 'course_access', 'Cours A1', 'course_access'),
            $this->item('DONATION.ALF.B31', 'service', 'Donation', 'donation'),
        ], 'fulfilled', 'paid');

        self::assertTrue($policy->order_requires_fulfillment($order));
        self::assertFalse($policy->order_is_donation_only($order));

        $state = (new CommercePostPaymentStateResolver($policy))->resolve($order);
        self::assertSame('processing', $state->code);
    }

    public function test_paid_donation_not_yet_closed_can_still_use_short_processing_watch(): void {
        $policy = new CommercePurchaseEntitlementExpectationPolicy();
        $order = $this->order([
            $this->item('DONATION.ALF.B31', 'service', 'Donation', 'donation'),
        ], 'paid', 'paid');

        $state = (new CommercePostPaymentStateResolver($policy))->resolve($order);
        self::assertSame('processing', $state->code);
    }

    public function test_admin_actions_do_not_offer_delivery_or_access_mail_for_donation(): void {
        $summary = $this->summary([
            ['label' => 'Donation', 'reference' => 'DONATION.ALF.B31', 'sku' => 'DONATION.ALF.B31', 'catalogue_type' => 'donation'],
        ]);
        $details = new CommercePurchaseDetails(
            $summary,
            [(object)[
                'metadatajson' => json_encode(['catalogue_type' => 'donation'], JSON_THROW_ON_ERROR),
            ]],
            [],
            [],
            null,
            null,
            []
        );

        $policy = new CommercePurchaseActionPolicy();
        self::assertFalse($policy->can_retry_fulfillment($details));
        self::assertFalse($policy->can_retry_summary($summary));
        self::assertFalse($policy->can_resend_access_summary($summary));
        self::assertTrue($policy->can_resend_receipt_summary($summary));
    }

    public function test_admin_dimensions_mark_delivery_and_access_not_applicable(): void {
        $dimensions = CommercePurchasePresentation::status_dimensions(
            100,
            'fulfilled',
            'paid',
            'none',
            false
        );

        self::assertSame(
            get_string('commerce_purchase_order_status_completed', 'local_subscriptions'),
            html_entity_decode(strip_tags($dimensions[1]['value']))
        );
        self::assertSame(
            get_string('commerce_purchase_status_not_applicable', 'local_subscriptions'),
            html_entity_decode(strip_tags($dimensions[2]['value']))
        );
        self::assertSame(
            get_string('commerce_purchase_status_not_applicable', 'local_subscriptions'),
            html_entity_decode(strip_tags($dimensions[3]['value']))
        );
    }

    public function test_read_model_promotes_persisted_catalogue_type_to_donation_business_type(): void {
        global $DB;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user(['email' => 'b31-donation@example.test']);
        $now = time();
        $purchaseid = (int)$DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => '798b3100000043338444555555555555',
            'reference' => 'cmp_798b31_donation',
            'type' => 'checkout',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => (int)$user->id,
            'customeremail' => 'b31-donation@example.test',
            'status' => CommercePurchaseStatus::FULFILLED,
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
        $DB->insert_record(CommercePersistenceSchema::TABLE_ITEM, (object)[
            'purchaseid' => $purchaseid,
            'position' => 0,
            'itemtype' => CommerceItem::TYPE_SERVICE,
            'itemreference' => 'DONATION.ALF.B31',
            'label' => 'Donation Alf',
            'quantity' => 1,
            'currency' => 'EUR',
            'unitminor' => 100,
            'grossminor' => 100,
            'discountminor' => 0,
            'netminor' => 100,
            'pricingjson' => '{}',
            'fulfillmentjson' => '{}',
            'metadatajson' => json_encode([
                'catalogue_type' => CommerceProductType::DONATION,
                'donation_units' => 1,
            ], JSON_THROW_ON_ERROR),
        ]);

        $details = (new CommercePurchaseReadRepository($DB))->find_by_id($purchaseid);
        self::assertNotNull($details);
        self::assertSame(CommerceProductType::DONATION, $details->summary->type);
        self::assertSame(CommerceProductType::DONATION, $details->summary->productitems[0]['catalogue_type']);
    }

    public function test_customer_and_admin_surfaces_have_donation_specific_contracts(): void {
        global $CFG;

        $orderresult = file_get_contents($CFG->dirroot . '/local/subscriptions/order_result.php');
        $mypurchases = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/output/my_purchases/CurrentPresentationRenderer.php'
        );
        $adminpurchase = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        self::assertStringContainsString('commerce_donation_result_title', $orderresult);
        self::assertStringContainsString('commerce_donation_result_next_title', $orderresult);
        self::assertStringContainsString('my-purchases-section--donations', $mypurchases);
        self::assertStringContainsString('commerce_donation_purchases_title', $mypurchases);
        self::assertStringContainsString("\$metadata['catalogue_type']", $adminpurchase);
        self::assertStringContainsString('$fulfillmentapplicable', $adminpurchase);
    }

    private function item(string $reference, string $type, string $label, string $cataloguetype): CommerceOrderItemPresentation {
        return new CommerceOrderItemPresentation(
            $reference,
            $type,
            $label,
            1,
            'EUR',
            100,
            100,
            0,
            100,
            [],
            ['catalogue_type' => $cataloguetype]
        );
    }

    /** @param CommerceOrderItemPresentation[] $items */
    private function order(array $items, string $status, string $paymentstatus): CommerceOrderPresentation {
        return new CommerceOrderPresentation(
            1,
            'uuid-b31',
            'purchase-b31',
            'checkout',
            42,
            'buyer@example.test',
            'EUR',
            count($items) * 100,
            $status,
            $paymentstatus,
            'none',
            'stripe',
            time(),
            time(),
            $items,
            []
        );
    }

    /** @param array<int,array<string,mixed>> $productitems */
    private function summary(array $productitems): CommercePurchaseSummary {
        return new CommercePurchaseSummary(
            1,
            'uuid-b31',
            'purchase-b31',
            'donation',
            new CommercePurchaseCustomer(42, 'buyer@example.test', 'Buyer', 'Test'),
            ['Donation'],
            'EUR',
            100,
            'fulfilled',
            'paid',
            'none',
            'stripe',
            'native',
            time(),
            $productitems,
            'CFR-2026-B31'
        );
    }
}
