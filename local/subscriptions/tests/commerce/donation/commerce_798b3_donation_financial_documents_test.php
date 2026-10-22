<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\donation\CommerceDonationAmountPolicy;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\context\CommercePurchaseMailContextFactory;
use local_subscriptions\commerce\mail\presentation\CommerceMailPurchasePresentation;
use local_subscriptions\commerce\order\creditnote\CommerceCreditNoteIssuer;
use local_subscriptions\commerce\order\invoice\CommerceInvoicePdfService;
use local_subscriptions\commerce\order\presentation\CommerceOrderItemPresentation;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRepository;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundResult;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\revocation\CommercePurchaseRightsRevocationService;

/** 7.98B.3 — Donation remains financially visible without becoming an entitlement. */
final class commerce_798b3_donation_financial_documents_test extends \advanced_testcase {
    public function test_receipt_presentation_uses_donation_semantics_instead_of_service(): void {
        $this->resetAfterTest(true);

        $order = $this->synthetic_order([
            new CommerceOrderItemPresentation(
                'DONATION.ALF.B3',
                'service',
                'Soutien pour Alf',
                1,
                'EUR',
                2500,
                2500,
                0,
                2500,
                [],
                [
                    'catalogue_type' => 'donation',
                    CommerceDonationAmountPolicy::METADATA_UNITS => 25,
                ]
            ),
        ], 2500);

        $factory = CommercePurchaseMailContextFactory::create();
        $raw = $factory->context_from_order($order, 'Buyer Snapshot', 'fr');
        self::assertSame('donation', $raw['items'][0]['type']);
        self::assertSame('Soutien pour Alf', $raw['items'][0]['title']);
        self::assertSame(2500, $raw['items'][0]['netminor']);
        self::assertNotSame('', $raw['items'][0]['totalformatted']);

        $presentation = CommerceMailPurchasePresentation::from_context(
            new CommerceMailContext($raw)
        )->export();
        self::assertTrue($presentation['items'][0]['isdonation']);
        self::assertFalse($presentation['items'][0]['isservice']);
        self::assertTrue($presentation['hasonlydonations']);
        self::assertNotSame('', $presentation['items'][0]['totalformatted']);
    }

    public function test_invoice_pdf_supports_mixed_course_and_donation_purchase(): void {
        global $DB;
        $this->resetAfterTest(true);

        $order = $this->synthetic_order([
            new CommerceOrderItemPresentation(
                'COURSE.A1.B3',
                'course_access',
                'Cours A1',
                1,
                'EUR',
                2500,
                2500,
                0,
                2500
            ),
            new CommerceOrderItemPresentation(
                'DONATION.ALF.B3',
                'service',
                'Soutien pour Alf',
                1,
                'EUR',
                1000,
                1000,
                0,
                1000,
                [],
                [
                    'catalogue_type' => 'donation',
                    CommerceDonationAmountPolicy::METADATA_UNITS => 10,
                ]
            ),
        ], 3500);

        $document = (new CommerceInvoicePdfService($DB))->generate($order);
        self::assertSame('application/pdf', $document->get_mimetype());
        self::assertStringStartsWith('%PDF-', $document->get_content());
    }

    public function test_credit_note_preserves_donation_nature_and_units_without_rights_revocation(): void {
        global $DB;
        $this->resetAfterTest(true);

        [$purchaseid, $paymentid, $reference] = $this->persist_paid_donation_purchase();

        $rights = CommercePurchaseRightsRevocationService::create($DB);
        self::assertSame([], $rights->preview($reference));
        self::assertFalse($rights->has_revocable_rights($reference));

        $refunds = new CommercePaymentRefundRepository($DB);
        $pending = $refunds->create_pending(
            $paymentid,
            'stripe',
            '798b3-donation-refund',
            'EUR',
            2500,
            'Donation refund',
            ['source' => '798b3'],
            null
        );
        $refund = $refunds->complete(
            $pending->get_id(),
            new CommercePaymentRefundResult(
                'stripe',
                're_798b3_donation',
                CommercePaymentRefundResult::STATUS_SUCCEEDED,
                'EUR',
                2500,
                []
            )
        );

        $note = (new CommerceCreditNoteIssuer($DB))->issue_if_eligible($refund);
        self::assertNotNull($note);
        self::assertSame($purchaseid, $note->purchaseid);
        self::assertSame(2500, $note->financial['refund_minor']);

        $items = $note->metadata['original_purchase']['items'] ?? [];
        self::assertCount(1, $items);
        self::assertSame('donation', $items[0]['catalogue_type']);
        self::assertSame(25, $items[0]['donation_units']);
        self::assertSame(2500, $items[0]['net_minor']);

        // REFUND remains purely financial: no grant is created or revoked.
        self::assertSame(0, $DB->count_records('local_subs_commerce_grant', [
            'purchasereference' => $reference,
        ]));
        self::assertSame([], $rights->preview($reference));
    }

    public function test_post_purchase_surfaces_explicitly_render_donation_type(): void {
        global $CFG;
        $this->resetAfterTest(true);

        $receipt = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/commerce/mail/components/receipt_item.mustache'
        );
        $orderdetails = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_details.php'
        );
        $ordertemplate = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/order_details/page.mustache'
        );
        $receipttemplate = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/CommercePurchaseReceiptTemplate.php'
        );
        $refundadmin = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/refund.php'
        );

        self::assertStringContainsString('{{#isdonation}}{{receipt_donation_type_label}}{{/isdonation}}', $receipt);
        self::assertStringContainsString("['catalogue_type']", $orderdetails);
        self::assertStringContainsString("commerce_product_type_donation", $orderdetails);
        self::assertStringContainsString('commerce-order-item-detail--donation', $ordertemplate);
        self::assertStringContainsString("!empty(\$context['hasonlydonations'])", $receipttemplate);
        self::assertStringContainsString('commerce_mail_view_order', $receipttemplate);

        // Refund + revoke remains opt-in and is only offered when real revocable grants exist.
        self::assertStringContainsString('if ($revocable !== [])', $refundadmin);
        self::assertStringContainsString("'revokeaccess'", $refundadmin);
    }

    /** @param CommerceOrderItemPresentation[] $items */
    private function synthetic_order(array $items, int $totalminor): CommerceOrderPresentation {
        $now = 1789876800;
        $seller = $this->seller_snapshot('EUR', 'stripe', $now);
        $customer = $this->customer_snapshot();

        return new CommerceOrderPresentation(
            7983001,
            '798b3000000043338444555555555555',
            'cmp_798b3_donation',
            'service',
            null,
            'buyer-b3@example.test',
            'EUR',
            $totalminor,
            'completed',
            'paid',
            'fulfilled',
            'stripe',
            $now,
            $now - 60,
            $items,
            [],
            [],
            [],
            null,
            ['metadata' => ['legal_entity_snapshot' => $seller]],
            $customer
        );
    }

    /** @return array{0:int,1:int,2:string} */
    private function persist_paid_donation_purchase(): array {
        global $DB;
        $now = time();
        $reference = 'cmp_798b3_' . bin2hex(random_bytes(6));
        $uuid = bin2hex(random_bytes(16));
        $seller = $this->seller_snapshot('EUR', 'stripe', $now);
        $customer = $this->customer_snapshot();

        $purchaseid = (int)$DB->insert_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            (object)[
                'purchaseuuid' => $uuid,
                'reference' => $reference,
                'type' => 'service',
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => null,
                'customeremail' => 'buyer-b3@example.test',
                'status' => 'paid',
                'currency' => 'EUR',
                'subtotalminor' => 2500,
                'discountminor' => 0,
                'totalminor' => 2500,
                'customerjson' => json_encode($customer, JSON_THROW_ON_ERROR),
                'snapshotjson' => json_encode([
                    'metadata' => ['legal_entity_snapshot' => $seller],
                ], JSON_THROW_ON_ERROR),
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $DB->insert_record(CommercePersistenceSchema::TABLE_ITEM, (object)[
            'purchaseid' => $purchaseid,
            'position' => 0,
            'itemtype' => 'service',
            'itemreference' => 'DONATION.ALF.B3',
            'label' => 'Soutien pour Alf',
            'quantity' => 1,
            'currency' => 'EUR',
            'unitminor' => 2500,
            'grossminor' => 2500,
            'discountminor' => 0,
            'netminor' => 2500,
            'pricingjson' => '{}',
            'fulfillmentjson' => '{}',
            'metadatajson' => json_encode([
                'catalogue_type' => 'donation',
                CommerceDonationAmountPolicy::METADATA_UNITS => 25,
            ], JSON_THROW_ON_ERROR),
        ]);

        $paymentid = (int)$DB->insert_record(
            CommercePersistenceSchema::TABLE_PAYMENT,
            (object)[
                'purchaseid' => $purchaseid,
                'sequence' => 0,
                'provider' => 'stripe',
                'providerreference' => 'pi_798b3',
                'providerorderid' => null,
                'status' => 'paid',
                'currency' => 'EUR',
                'amountminor' => 2500,
                'transactionid' => 'txn_798b3',
                'legacyrequestid' => null,
                'paidat' => $now,
                'metadatajson' => '{}',
                'paymenturl' => null,
                'providerpayload' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        return [$purchaseid, $paymentid, $reference];
    }

    /** @return array<string,mixed> */
    private function seller_snapshot(string $currency, string $provider, int $now): array {
        return [
            'version' => 1,
            'legal_entity_key' => 'fr_main',
            'registered_country' => 'FR',
            'name' => 'CampusFR France',
            'address' => 'Snapshot address',
            'legal' => 'Snapshot legal',
            'registration' => 'REG-B3',
            'tax_identifier' => '',
            'email' => 'legal@example.test',
            'phone' => '',
            'website' => '',
            'tax_notice' => '',
            'footer' => '',
            'market_country' => 'FR',
            'resolution_rule' => 'market_row',
            'resolved_at' => $now,
            'currency' => $currency,
            'provider' => $provider,
        ];
    }

    /** @return array<string,mixed> */
    private function customer_snapshot(): array {
        return [
            'userid' => null,
            'email' => 'buyer-b3@example.test',
            'firstname' => 'Buyer',
            'lastname' => 'Snapshot',
            'fullname' => 'Buyer Snapshot',
            'country' => 'FR',
            'language' => 'fr',
            'metadata' => [],
        ];
    }
}
