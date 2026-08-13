<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\domain\purchase\NativePurchase;
use local_subscriptions\commerce\domain\value\CommerceCustomerSnapshot;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\domain\value\CommercePurchaseId;
use local_subscriptions\commerce\domain\value\CommercePurchaseItem;
use local_subscriptions\commerce\domain\value\CommercePurchaseReference;
use local_subscriptions\commerce\domain\value\CommercePurchaseSnapshot;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceMapper;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepository;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;

/** Persists the immutable checkout transaction before any provider call. */
final class CommerceCheckoutPurchasePersister {
    public function __construct(
        private readonly CommercePurchaseSqlRepository $repository,
        private readonly CommercePurchasePersistenceMapper $mapper = new CommercePurchasePersistenceMapper(),
        private readonly ?CommercePaymentRepository $payments = null
    ) {}

    public function persist(CommercePurchaseRequest $request): int {
        return $this->persist_with_result($request)->get_purchase_id();
    }

    public function persist_with_result(
        CommercePurchaseRequest $request
    ): CommerceCheckoutPersistenceResult {
        $existing = $this->repository->find_by_reference($request->get_reference());

        if ($existing !== null) {
            $this->assert_resume_matches($existing, $request);

            return new CommerceCheckoutPersistenceResult(
                0,
                $this->create_payment_attempt(
                    $existing->get_purchase()->get_purchase_uuid(),
                    $request
                )
            );
        }

        $purchase = $this->map_request($request);
        $purchaseid = $this->repository->save($this->mapper->map($purchase));

        return new CommerceCheckoutPersistenceResult(
            $purchaseid,
            $this->create_payment_attempt(
                $purchase->get_purchase_id()->get_value(),
                $request
            )
        );
    }


    private function assert_resume_matches(
        CommercePurchasePersistenceSnapshot $snapshot,
        CommercePurchaseRequest $request
    ): void {
        $purchase = $snapshot->get_purchase();
        $purchaserecord = $purchase->to_record();

        if ((string)$purchaserecord->status !== CommercePurchaseStatus::PAYMENT_PENDING) {
            throw new CommerceInterruptedCheckoutResumeMismatchException(
                'Interrupted purchase is no longer payment_pending.'
            );
        }

        $requestcustomer = $request->get_customer();
        $customerjson = json_decode(
            (string)($purchaserecord->customerjson ?? '{}'),
            true
        );
        $customerjson = is_array($customerjson) ? $customerjson : [];

        $existinguserid = isset($purchaserecord->userid)
            && $purchaserecord->userid !== null
                ? (int)$purchaserecord->userid
                : (int)($customerjson['userid'] ?? 0);
        $existingemail = \core_text::strtolower(trim(
            (string)($purchaserecord->customeremail
                ?? $customerjson['email']
                ?? '')
        ));

        if (
            $existinguserid !== (int)($requestcustomer->get_user_id() ?? 0)
            || $existingemail !== \core_text::strtolower($requestcustomer->get_email())
        ) {
            throw new CommerceInterruptedCheckoutResumeMismatchException(
                'Interrupted purchase customer does not match the checkout customer.'
            );
        }

        if (
            strtoupper((string)$purchaserecord->currency) !== $request->get_currency()
            || (int)$purchaserecord->totalminor !== $request->get_total_amount_minor()
        ) {
            throw new CommerceInterruptedCheckoutResumeMismatchException(
                'Interrupted purchase total does not match the current checkout.'
            );
        }

        $existingitems = array_values($snapshot->get_items());
        usort(
            $existingitems,
            static fn($left, $right): int =>
                $left->get_position() <=> $right->get_position()
        );

        $requestitems = array_values($request->get_items());
        if (count($existingitems) !== count($requestitems)) {
            throw new CommerceInterruptedCheckoutResumeMismatchException(
                'Interrupted purchase item count does not match.'
            );
        }

        foreach ($existingitems as $index => $existingitem) {
            $persisted = $existingitem->to_record();
            $requestitem = $requestitems[$index];

            if (
                (string)$persisted->itemreference
                    !== $requestitem->get_item()->get_reference()
                || (int)$persisted->quantity !== $requestitem->get_quantity()
                || strtoupper((string)$persisted->currency)
                    !== $requestitem->get_currency()
                || (int)$persisted->netminor
                    !== $requestitem->get_total_amount_minor()
            ) {
                throw new CommerceInterruptedCheckoutResumeMismatchException(
                    'Interrupted purchase lines do not match the current checkout.'
                );
            }
        }
    }

    private function create_payment_attempt(
        string $purchaseuuid,
        CommercePurchaseRequest $request
    ): ?CommercePaymentAttempt {
        if ($this->payments === null) {
            return null;
        }

        return $this->payments->create(
            $purchaseuuid,
            $request->get_preferred_provider(),
            $request->get_total_amount_minor(),
            $request->get_currency(),
            [
                'request_reference' => $request->get_reference(),
                'source' => 'unified_checkout',
            ]
        );
    }

    private function map_request(CommercePurchaseRequest $request): NativePurchase {
        $purchaseid = CommercePurchaseId::generate();
        $reference = CommercePurchaseReference::from_string($request->get_reference());
        $customer = $request->get_customer();
        $items = [];

        foreach ($request->get_items() as $requestitem) {
            $quantity = $requestitem->get_quantity();
            $metadata = $requestitem->get_metadata();
            $nettotal = $requestitem->get_total_amount_minor();
            $unitamount = $requestitem->get_unit_amount_minor();

            if (($unitamount * $quantity) !== $nettotal) {
                throw new \RuntimeException('Checkout payable amount cannot be allocated across the requested quantity.');
            }

            $items[] = new CommercePurchaseItem(
                $requestitem->get_item(),
                $quantity,
                CommerceMoney::from_minor($unitamount, $request->get_currency()),
                CommerceMoney::zero($request->get_currency()),
                [
                    'source' => 'unified_checkout',
                    'locked_payable_unit_minor' => $unitamount,
                    'locked_payable_total_minor' => $nettotal,
                    'cart_subtotal_minor' => $metadata['locked_subtotal_minor'] ?? $nettotal,
                ],
                $requestitem->get_item()->get_metadata(),
                $metadata
            );
        }

        $firstitem = $request->get_items()[0]->get_item();
        $now = time();

        return new NativePurchase(
            $this->resolve_purchase_type($items),
            $purchaseid,
            $reference,
            new CommerceCustomerSnapshot(
                $customer->get_user_id(),
                $customer->get_email(),
                $customer->get_first_name(),
                $customer->get_last_name(),
                null,
                $customer->get_metadata()['language'] ?? null,
                $customer->get_metadata()
            ),
            $items,
            new CommercePurchaseSnapshot(
                $firstitem->get_reference(),
                count($items) === 1 ? $firstitem->get_name() : 'Unified checkout',
                null,
                [
                    'currency' => $request->get_currency(),
                    'total_amount_minor' => $request->get_total_amount_minor(),
                ],
                [],
                [],
                $request->get_metadata()
            ),
            CommercePurchaseStatus::PAYMENT_PENDING,
            $this->payments === null ? [new CommercePayment(
                $request->get_total_amount_minor(),
                $request->get_currency(),
                CommercePayment::STATUS_PENDING,
                $request->get_preferred_provider(),
                null,
                null,
                null,
                ['request_reference' => $request->get_reference()]
            )] : [],
            [],
            $now,
            $now,
            array_merge($request->get_metadata(), [
                'checkout_provider' => $request->get_preferred_provider(),
                'checkout_return_url' => $request->get_return_url(),
                'checkout_cancel_url' => $request->get_cancel_url(),
                'checkout_persisted_at' => $now,
            ])
        );
    }
    /** @param CommercePurchaseItem[] $items */
    private function resolve_purchase_type(array $items): string {
        $types = array_values(array_unique(array_map(
            static fn(CommercePurchaseItem $item): string => $item->get_item_type(),
            $items
        )));

        if (count($types) === 1) {
            return $types[0];
        }

        return 'bundle';
    }

}
