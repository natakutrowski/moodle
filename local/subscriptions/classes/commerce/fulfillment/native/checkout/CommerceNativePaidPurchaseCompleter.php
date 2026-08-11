<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\checkout;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\bundle\expansion\CommerceBundleExpansionService;
use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantPersister;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\native\batch\CommerceNativePurchaseFulfillmentOrchestrator;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\digital\CommerceDigitalDownloadFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\persistence\CommercePersistentNativeFulfillmentExecutor;
use local_subscriptions\commerce\fulfillment\native\persistence\MoodleCommerceNativeFulfillmentPersistenceRepository;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\mail\service\CommerceTransactionalPurchaseMailService;
use local_subscriptions\commerce\trial\CommerceTrialConversionCompletionService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;
use local_subscriptions\payment\dto\InternalEvent;

/** Completes one paid Native checkout purchase and dispatches its entitlement grants. */
final class CommerceNativePaidPurchaseCompleter {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePaymentRepository $payments,
        private readonly ?CommerceTransactionalPurchaseMailService $transactionalmail = null
    ) {
    }

    public function complete(InternalEvent $event): void {
        $attempt = $this->resolve_attempt($event);

        if ($attempt === null) {
            throw new \RuntimeException(
                'The paid Native Commerce payment attempt could not be resolved.'
            );
        }

        $purchase = $this->db->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['purchaseuuid' => $attempt->get_purchase_uuid()],
            '*',
            MUST_EXIST
        );

        $transactionalmail = $this->transactionalmail
            ?? CommerceTransactionalPurchaseMailService::create();

        // The synchronized checkout event confirms payment. Persist and attempt
        // the receipt before fulfillment, without letting mail affect checkout.
        $transactionalmail->deliver_payment_confirmed_purchase(
            (string)$purchase->reference
        );

        if ((string)$purchase->status === CommercePurchaseStatus::FULFILLED) {
            $transactionalmail->deliver_fulfilled_access(
                (string)$purchase->reference
            );
            return;
        }

        $items = array_values($this->db->get_records(
            CommercePersistenceSchema::TABLE_ITEM,
            ['purchaseid' => (int)$purchase->id],
            'position ASC, id ASC'
        ));

        if ($items === []) {
            throw new \RuntimeException(
                'The paid Native Commerce purchase has no persisted items.'
            );
        }

        $grantplan = (new CommerceNativePurchaseGrantPlanner($this->db))->plan(
            $purchase,
            $items
        );
        $grants = $grantplan->get_grants();
        if ($grants === []) {
            throw new \RuntimeException(
                'The paid Native Commerce purchase produced no entitlement grants.'
            );
        }

        $this->update_purchase_status(
            (int)$purchase->id,
            CommercePurchaseStatus::FULFILLMENT_PENDING
        );

        $mapper = new CommerceEntitlementGrantRecordMapper();
        $repository = new CommerceEntitlementGrantRepository($this->db, $mapper);
        $persister = new CommerceEntitlementGrantPersister($this->db, $repository);
        $persister->persist($grantplan);

        $registry = new CommerceNativeFulfillmentHandlerRegistry([
            new CommerceCourseAccessFulfillmentHandler(),
            new CommerceDigitalDownloadFulfillmentHandler(),
        ]);
        $orchestrator = new CommerceNativePurchaseFulfillmentOrchestrator(
            $repository,
            $mapper,
            new CommercePersistentNativeFulfillmentExecutor(
                $registry,
                new MoodleCommerceNativeFulfillmentPersistenceRepository()
            )
        );

        $batch = $orchestrator->execute_purchase(
            (string)$purchase->reference,
            CommerceNativeFulfillmentContext::runtime(
                'webhook-' . substr(hash(
                    'sha256',
                    $purchase->reference . '|' . $attempt->get_id()
                ), 0, 24),
                time(),
                null,
                $attempt->get_provider() . '_webhook',
                [
                    'purchaseid' => (int)$purchase->id,
                    'paymentid' => (int)$attempt->get_id(),
                    'provider' => $attempt->get_provider(),
                ]
            )
        );

        if ($batch->count() === 0 || !$batch->is_successful()) {
            throw new \RuntimeException(
                'Native Commerce fulfillment did not complete successfully.'
            );
        }

        // Consume the Legacy Trial only after every Native grant has completed.
        // A failed or pending payment therefore keeps the Trial offer available.
        (new CommerceTrialConversionCompletionService($this->db))->complete(
            $purchase,
            $items,
            time()
        );

        // Consume Personal Offers only after payment confirmation and successful Native fulfillment.
        // The UUID stored on the server-built checkout line is sufficient here: the public bearer token
        // is deliberately never persisted in Purchase metadata.
        foreach ($items as $item) {
            $metadata = json_decode((string)($item->metadatajson ?? ''), true);
            if (!is_array($metadata) || strtolower((string)($metadata['operation'] ?? '')) !== 'personaloffer') {
                continue;
            }
            $offeruuid = strtolower(trim((string)($metadata['personal_offer_uuid'] ?? '')));
            if ($offeruuid === '') {
                throw new \RuntimeException('A Personal Offer purchase item lost its offer identifier.');
            }
            CommercePersonalOfferFactory::create($this->db)->redeem_by_offer_uuid(
                $offeruuid,
                (int)$purchase->id,
                time()
            );
        }

        $this->update_purchase_status(
            (int)$purchase->id,
            CommercePurchaseStatus::FULFILLED
        );


        $transactionalmail->deliver_fulfilled_access(
            (string)$purchase->reference
        );
    }

    private function resolve_attempt(InternalEvent $event): ?CommercePaymentAttempt {
        $paymentid = $this->resolve_payment_id($event);
        if ($paymentid > 0) {
            $attempt = $this->payments->find($paymentid);
            if ($attempt !== null) {
                return $attempt;
            }
        }

        $provider = strtolower(trim((string)($event->meta['provider'] ?? '')));
        if ($provider === '') {
            return null;
        }

        foreach (['session', 'provider_payment_id', 'alfa_order_id'] as $key) {
            $reference = trim((string)($event->meta[$key] ?? ''));
            if ($reference === '') {
                continue;
            }

            $attempt = $this->payments->find_by_provider_reference($provider, $reference)
                ?? $this->payments->find_by_provider_order_id($provider, $reference);

            if ($attempt !== null) {
                return $attempt;
            }
        }

        return null;
    }

    private function resolve_product_sku(
        \stdClass $item,
        CommerceProductPriceRepository $prices
    ): string {
        $priceid = $this->extract_price_id($item);
        if ($priceid > 0) {
            $price = $prices->find_by_id($priceid);
            if ($price !== null) {
                return $price->get_product_sku();
            }
        }

        return strtoupper(trim((string)$item->itemreference));
    }

    private function extract_price_id(\stdClass $item): int {
        foreach (['fulfillmentjson', 'metadatajson'] as $field) {
            $decoded = json_decode((string)($item->{$field} ?? ''), true);
            if (is_array($decoded) && !empty($decoded['priceid'])) {
                return (int)$decoded['priceid'];
            }
        }

        return 0;
    }

    private function resolve_payment_id(InternalEvent $event): int {
        $value = $event->meta['commerce_payment_id'] ?? 0;
        return is_numeric($value) ? (int)$value : 0;
    }

    private function update_purchase_status(int $purchaseid, string $status): void {
        $this->db->update_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'id' => $purchaseid,
            'status' => $status,
            'timemodified' => time(),
        ]);
    }
}
