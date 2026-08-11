<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutLegacyPaymentRequestBridge;
use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestAdapter;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;

final class commerce_795h42_legacy_transaction_bridge_test extends advanced_testcase {
    public function test_bridge_persists_and_enriches_a_provider_compatible_transaction_mirror(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['email' => 'checkout@example.test']);
        $request = $this->request((int)$user->id, 'stripe', 'EUR', 4900, 'cmp-h42-stripe');

        $enriched = (new CommerceCheckoutLegacyPaymentRequestBridge($DB))->persist_and_enrich($request);
        $metadata = $enriched->get_metadata();

        $this->assertSame(LegacyPaymentRequestContext::TABLE_SUBSCRIPTION, $metadata['legacy_payment_request_table']);
        $this->assertSame(LegacyPaymentRequestContext::CONTEXT_COMMERCE_TRANSACTION, $metadata['legacy_payment_context']);
        $this->assertSame('commerce', $metadata['legacy_order_number_prefix']);

        $record = (new LegacyPaymentRequestAdapter($DB))->load_for_commerce_request($enriched, 'stripe');
        $this->assertSame(4900, (int)$record->amount_minor);
        $this->assertSame(49.0, (float)$record->locked_final_price);
        $this->assertNull($record->planid);
    }

    public function test_bridge_is_idempotent_for_the_same_commerce_reference(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['email' => 'checkout2@example.test']);
        $request = $this->request((int)$user->id, 'alfa', 'RUB', 120000, 'cmp-h42-alfa');
        $bridge = new CommerceCheckoutLegacyPaymentRequestBridge($DB);

        $first = $bridge->persist_and_enrich($request);
        $second = $bridge->persist_and_enrich($request);

        $this->assertSame(
            $first->get_metadata_value('legacy_payment_request_id'),
            $second->get_metadata_value('legacy_payment_request_id')
        );
        $this->assertSame(1, $DB->count_records('subscription_payment_request'));
    }

    private function request(int $userid, string $provider, string $currency, int $amountminor, string $reference): CommercePaymentRequest {
        return new CommercePaymentRequest(
            $reference,
            new CommercePaymentCustomer($userid, $provider === 'alfa' ? 'checkout2@example.test' : 'checkout@example.test', 'Nata', 'CampusFR', ['language' => 'fr']),
            [new CommercePaymentLine('A1-FULL', 'CampusFR', 1, $amountminor, $currency)],
            $currency,
            $amountminor,
            $provider,
            'https://example.test/success',
            'https://example.test/cancel',
            ['cart_subtotal_minor' => $amountminor, 'promotion_codes' => []],
            123456
        );
    }
}
