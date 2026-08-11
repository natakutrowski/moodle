<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\returnflow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use moodle_database;

/** Resolves and validates browser returns exclusively from Native Commerce identity. */
final class CommercePaymentReturnResolver {
    public function __construct(
        private readonly moodle_database $database,
        private readonly CommercePaymentRepository $payments
    ) {
    }

    public function resolve(
        int $paymentid,
        ?string $purchaseuuid = null,
        ?string $provider = null,
        ?string $providerreference = null
    ): CommercePaymentReturnContext {
        $payment = $this->payments->find($paymentid);

        if ($payment === null) {
            throw new \moodle_exception('invalidpaymentid', 'local_subscriptions');
        }

        $expecteduuid = strtolower(trim((string)$purchaseuuid));
        if ($expecteduuid !== '' && !hash_equals($payment->get_purchase_uuid(), $expecteduuid)) {
            throw new \moodle_exception('invalidpaymentid', 'local_subscriptions');
        }

        $expectedprovider = strtolower(trim((string)$provider));
        if ($expectedprovider !== '' && !hash_equals($payment->get_provider(), $expectedprovider)) {
            throw new \moodle_exception('invalidpaymentid', 'local_subscriptions');
        }

        $expectedreference = trim((string)$providerreference);
        $actualreference = $payment->get_provider_reference() ?? $payment->get_provider_order_id() ?? '';
        if ($expectedreference !== '' && !hash_equals($actualreference, $expectedreference)) {
            throw new \moodle_exception('invalidpaymentid', 'local_subscriptions');
        }

        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['purchaseuuid' => $payment->get_purchase_uuid()],
            '*',
            MUST_EXIST
        );

        return new CommercePaymentReturnContext($payment, $purchase);
    }
}
