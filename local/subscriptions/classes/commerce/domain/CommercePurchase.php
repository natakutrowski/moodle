<?php

namespace local_subscriptions\commerce\domain;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\value\CommerceCustomerSnapshot;
use local_subscriptions\commerce\domain\value\CommerceLegacyPurchaseReference;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\domain\value\CommercePurchaseId;
use local_subscriptions\commerce\domain\value\CommercePurchaseItem;
use local_subscriptions\commerce\domain\value\CommercePurchaseReference;
use local_subscriptions\commerce\domain\value\CommercePurchaseSnapshot;
use local_subscriptions\commerce\domain\value\CommercePurchaseTotals;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;

/**
 * Aggregate root representing a Commerce purchase.
 *
 * The Legacy constructor shape remains supported during the incremental
 * migration. Internally, every purchase now owns a native identity, one or
 * more immutable purchase lines, zero or more payments, and zero or more
 * fulfillment operations.
 */
abstract class CommercePurchase {

    private CommercePurchaseId $purchaseid;
    private CommercePurchaseReference $purchasereference;
    private ?CommerceLegacyPurchaseReference $legacyreference;
    private CommerceCustomerSnapshot $customer;
    /** @var CommercePurchaseItem[] */
    private array $items;
    private CommercePurchaseTotals $totals;
    /** @var CommercePayment[] */
    private array $payments;
    /** @var CommerceFulfillmentOperation[] */
    private array $fulfillments;
    private CommercePurchaseSnapshot $snapshot;
    private string $status;
    private string $lifecyclestatus;

    /**
     * @param string $reference Stable Legacy-compatible domain reference.
     * @param CommerceItem $item Historical single item.
     * @param CommercePayment $payment Historical single payment.
     * @param int|null $userid Moodle user identifier, when known.
     * @param string|null $customeremail Customer email, including guest buyers.
     * @param string $status Purchase status.
     * @param int|null $createdat Purchase creation timestamp.
     * @param int|null $updatedat Last known update timestamp.
     * @param array $metadata Additional domain information.
     * @param CommercePurchaseId|null $purchaseid Native Commerce identity.
     * @param CommercePurchaseReference|null $purchasereference Public reference.
     * @param CommerceLegacyPurchaseReference|null $legacyreference Legacy link.
     * @param CommerceCustomerSnapshot|null $customer Customer snapshot.
     * @param CommercePurchaseItem[]|null $items Purchase lines.
     * @param CommercePurchaseSnapshot|null $snapshot Commercial snapshot.
     * @param CommercePayment[]|null $payments Payments.
     * @param CommerceFulfillmentOperation[] $fulfillments Fulfillment operations.
     */
    public function __construct(
        private readonly string $reference,
        ?CommerceItem $item,
        ?CommercePayment $payment,
        ?int $userid,
        ?string $customeremail,
        string $status,
        private readonly ?int $createdat = null,
        private readonly ?int $updatedat = null,
        private readonly array $metadata = [],
        ?CommercePurchaseId $purchaseid = null,
        ?CommercePurchaseReference $purchasereference = null,
        ?CommerceLegacyPurchaseReference $legacyreference = null,
        ?CommerceCustomerSnapshot $customer = null,
        ?array $items = null,
        ?CommercePurchaseSnapshot $snapshot = null,
        ?array $payments = null,
        array $fulfillments = []
    ) {
        if (trim($reference) === '') {
            throw new \coding_exception('A Commerce purchase reference cannot be empty.');
        }
        if ($userid !== null && $userid <= 0) {
            throw new \coding_exception('A Commerce purchase user identifier must be positive.');
        }
        if ($customeremail !== null && trim($customeremail) === '') {
            throw new \coding_exception('A Commerce purchase customer email cannot be empty.');
        }

        $normalisedstatus = CommercePurchaseStatus::normalise($status, $payment);
        if (!CommercePurchaseStatus::is_valid($normalisedstatus)) {
            throw new \coding_exception('Unsupported Commerce purchase status: ' . $status);
        }

        // A deterministic identity keeps Legacy projections stable until native persistence exists.
        $purchaseid ??= CommercePurchaseId::from_string(md5('legacy-commerce|' . trim($reference)));
        $purchasereference ??= CommercePurchaseReference::from_purchase_id($purchaseid);
        $customer ??= new CommerceCustomerSnapshot(
            $userid,
            $customeremail,
            self::nullable_metadata_string($metadata, 'firstname'),
            self::nullable_metadata_string($metadata, 'lastname'),
            self::nullable_metadata_string($metadata, 'country'),
            self::nullable_metadata_string($metadata, 'language')
        );

        if ($items === null) {
            if ($item === null) {
                throw new \coding_exception('A Commerce purchase must provide an item or native purchase lines.');
            }
            $currency = $payment?->get_currency()
                ?? self::nullable_metadata_string($metadata, 'currency')
                ?? 'EUR';
            $amountminor = $payment?->get_amount_minor() ?? 0;
            $legacyline = new CommercePurchaseItem(
                $item,
                1,
                CommerceMoney::from_minor($amountminor, $currency),
                null,
                ['source' => 'legacy_projection'],
                $item->get_metadata(),
                ['legacy_reference' => $reference]
            );
            $items = [$legacyline];
        }
        self::assert_items($items);

        $payments ??= $payment === null ? [] : [$payment];
        self::assert_payments($payments);
        self::assert_fulfillments($fulfillments);

        $firstitem = $items[0]->get_item();
        $snapshot ??= new CommercePurchaseSnapshot(
            $firstitem->get_reference(),
            $firstitem->get_name(),
            null,
            ['source' => 'legacy_projection'],
            $firstitem->get_metadata(),
            [],
            $metadata
        );

        $this->purchaseid = $purchaseid;
        $this->purchasereference = $purchasereference;
        $this->legacyreference = $legacyreference;
        $this->customer = $customer;
        $this->items = array_values($items);
        $this->totals = CommercePurchaseTotals::from_items($this->items);
        $this->payments = array_values($payments);
        $this->fulfillments = array_values($fulfillments);
        $this->snapshot = $snapshot;
        $this->status = strtolower(trim($status));
        $this->lifecyclestatus = $normalisedstatus;
    }

    abstract public function get_type(): string;

    /** Legacy-compatible reference. */
    public function get_reference(): string { return $this->reference; }
    public function get_purchase_id(): CommercePurchaseId { return $this->purchaseid; }
    public function get_purchase_reference(): CommercePurchaseReference { return $this->purchasereference; }
    public function get_legacy_reference(): ?CommerceLegacyPurchaseReference { return $this->legacyreference; }
    public function get_customer(): CommerceCustomerSnapshot { return $this->customer; }

    /** @return CommercePurchaseItem[] */
    public function get_items(): array { return $this->items; }
    public function get_totals(): CommercePurchaseTotals { return $this->totals; }
    public function get_snapshot(): CommercePurchaseSnapshot { return $this->snapshot; }

    /** @return CommercePayment[] */
    public function get_payments(): array { return $this->payments; }
    /** @return CommerceFulfillmentOperation[] */
    public function get_fulfillments(): array { return $this->fulfillments; }

    /** Historical single-item accessor. */
    public function get_item(): CommerceItem { return $this->items[0]->get_item(); }
    /** Historical single-payment accessor. */
    public function get_payment(): CommercePayment {
        if ($this->payments === []) {
            throw new \coding_exception('This Commerce purchase has no payment yet.');
        }
        return $this->payments[0];
    }

    public function get_user_id(): ?int { return $this->customer->get_user_id(); }
    public function get_customer_email(): ?string { return $this->customer->get_email(); }
    public function get_status(): string { return $this->status; }
    public function get_lifecycle_status(): string { return $this->lifecyclestatus; }
    public function get_created_at(): ?int { return $this->createdat; }
    public function get_updated_at(): ?int { return $this->updatedat; }
    public function get_metadata(): array { return $this->metadata; }
    public function get_metadata_value(string $key, mixed $default = null): mixed { return $this->metadata[$key] ?? $default; }

    public function contains_multiple_items(): bool { return count($this->items) > 1; }
    public function has_multiple_payments(): bool { return count($this->payments) > 1; }
    public function requires_multiple_fulfillments(): bool { return count($this->fulfillments) > 1; }

    public function is_paid(): bool {
        if (CommercePurchaseStatus::is_financially_successful($this->lifecyclestatus)) {
            return true;
        }
        foreach ($this->payments as $payment) {
            if ($payment->is_successful()) {
                return true;
            }
        }
        return false;
    }

    public function add_item(CommercePurchaseItem $item): void {
        if (!CommercePurchaseStatus::is_editable($this->lifecyclestatus)) {
            throw new \coding_exception('Purchase items can only be added while the purchase is in draft.');
        }
        if ($item->get_currency() !== $this->totals->get_currency()) {
            throw new \coding_exception('Every purchase item must use the purchase currency.');
        }
        $this->items[] = $item;
        $this->totals = CommercePurchaseTotals::from_items($this->items);
    }

    public function add_payment(CommercePayment $payment): void {
        if ($payment->get_currency() !== $this->totals->get_currency()) {
            throw new \coding_exception('Every purchase payment must use the purchase currency.');
        }
        $this->payments[] = $payment;
    }

    public function add_fulfillment(CommerceFulfillmentOperation $operation): void {
        foreach ($this->fulfillments as $existing) {
            if ($existing->get_idempotency_key() === $operation->get_idempotency_key()) {
                throw new \coding_exception('A Commerce fulfillment operation must be idempotently unique.');
            }
        }
        $this->fulfillments[] = $operation;
    }

    public function transition_to(string $status): void {
        $target = CommercePurchaseStatus::normalise($status);
        if (!CommercePurchaseStatus::can_transition($this->lifecyclestatus, $target)) {
            throw new \coding_exception(
                'Invalid Commerce purchase transition from ' . $this->lifecyclestatus . ' to ' . $target . '.'
            );
        }
        $this->lifecyclestatus = $target;
        $this->status = $target;
    }

    public function prepare(): void { $this->transition_to(CommercePurchaseStatus::PREPARED); }
    public function mark_payment_pending(): void { $this->transition_to(CommercePurchaseStatus::PAYMENT_PENDING); }
    public function mark_paid(): void { $this->transition_to(CommercePurchaseStatus::PAID); }
    public function mark_fulfillment_pending(): void { $this->transition_to(CommercePurchaseStatus::FULFILLMENT_PENDING); }
    public function mark_fulfilled(): void { $this->transition_to(CommercePurchaseStatus::FULFILLED); }
    public function complete(): void { $this->transition_to(CommercePurchaseStatus::COMPLETED); }
    public function cancel(): void { $this->transition_to(CommercePurchaseStatus::CANCELLED); }
    public function refund(): void { $this->transition_to(CommercePurchaseStatus::REFUNDED); }

    private static function assert_items(array $items): void {
        if ($items === []) {
            throw new \coding_exception('A Commerce purchase must contain at least one purchase item.');
        }
        foreach ($items as $item) {
            if (!$item instanceof CommercePurchaseItem) {
                throw new \coding_exception('Every purchase line must be a CommercePurchaseItem.');
            }
        }
    }

    private static function assert_payments(array $payments): void {
        foreach ($payments as $payment) {
            if (!$payment instanceof CommercePayment) {
                throw new \coding_exception('Every purchase payment must be a CommercePayment.');
            }
        }
    }

    private static function assert_fulfillments(array $fulfillments): void {
        foreach ($fulfillments as $fulfillment) {
            if (!$fulfillment instanceof CommerceFulfillmentOperation) {
                throw new \coding_exception('Every purchase fulfillment must be a CommerceFulfillmentOperation.');
            }
        }
    }

    private static function nullable_metadata_string(array $metadata, string $key): ?string {
        if (!isset($metadata[$key])) {
            return null;
        }
        $value = trim((string)$metadata[$key]);
        return $value === '' ? null : $value;
    }
}
