<?php

namespace local_subscriptions\commerce\purchase\domain;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\CommercePurchaseType;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;

/**
 * Fluent builder for Commerce purchase domain objects.
 *
 * The builder creates in-memory domain objects only. It does not persist
 * purchases and does not trigger payment or fulfillment operations.
 */
final class CommercePurchaseBuilder {

    private ?string $type = null;

    private ?string $reference = null;

    private ?CommerceItem $item = null;

    private ?CommercePayment $payment = null;

    private ?int $userid = null;

    private ?string $customeremail = null;

    private ?string $status = null;

    private ?int $legacyid = null;

    private ?int $catalogid = null;

    private ?int $createdat = null;

    private ?int $updatedat = null;

    private ?int $startdate = null;

    private ?int $enddate = null;

    private ?string $downloadtoken = null;

    private ?int $downloadtokenexpires = null;

    private array $metadata = [];

    /**
     * Set the purchase type.
     *
     * @param string $type Commerce purchase type.
     * @return self
     */
    public function type(string $type): self {
        $this->type = CommercePurchaseType::normalise($type);

        return $this;
    }

    /**
     * Set the stable purchase reference.
     *
     * @param string $reference Purchase reference.
     * @return self
     */
    public function reference(string $reference): self {
        $reference = trim($reference);

        if ($reference === '') {
            throw new \InvalidArgumentException(
                'A Commerce purchase reference cannot be empty.'
            );
        }

        $this->reference = $reference;

        return $this;
    }

    /**
     * Set the purchased item.
     *
     * @param CommerceItem $item Purchased item.
     * @return self
     */
    public function item(CommerceItem $item): self {
        $this->item = $item;

        return $this;
    }

    /**
     * Set the payment.
     *
     * @param CommercePayment $payment Payment.
     * @return self
     */
    public function payment(CommercePayment $payment): self {
        $this->payment = $payment;

        return $this;
    }

    /**
     * Set the customer.
     *
     * @param int|null $userid Moodle user identifier.
     * @param string|null $email Customer email.
     * @return self
     */
    public function customer(
        ?int $userid,
        ?string $email = null
    ): self {
        if ($userid !== null && $userid <= 0) {
            throw new \InvalidArgumentException(
                'A Commerce customer identifier must be positive.'
            );
        }

        $email = $this->normalise_nullable_string($email);

        $this->userid = $userid;
        $this->customeremail = $email;

        return $this;
    }

    /**
     * Set the purchase status.
     *
     * @param string $status Purchase status.
     * @return self
     */
    public function status(string $status): self {
        $status = trim($status);

        if ($status === '') {
            throw new \InvalidArgumentException(
                'A Commerce purchase status cannot be empty.'
            );
        }

        $this->status = $status;

        return $this;
    }

    /**
     * Set the Legacy purchase identifier.
     *
     * @param int $legacyid Legacy identifier.
     * @return self
     */
    public function legacy_id(int $legacyid): self {
        if ($legacyid <= 0) {
            throw new \InvalidArgumentException(
                'A Legacy purchase identifier must be positive.'
            );
        }

        $this->legacyid = $legacyid;

        return $this;
    }

    /**
     * Set the Legacy catalog identifier.
     *
     * For subscriptions this is the plan identifier.
     * For digital purchases this is the product identifier.
     *
     * @param int $catalogid Catalog identifier.
     * @return self
     */
    public function catalog_id(int $catalogid): self {
        if ($catalogid <= 0) {
            throw new \InvalidArgumentException(
                'A Commerce catalog identifier must be positive.'
            );
        }

        $this->catalogid = $catalogid;

        return $this;
    }

    /**
     * Set the creation timestamp.
     *
     * @param int|null $createdat Creation timestamp.
     * @return self
     */
    public function created_at(?int $createdat): self {
        $this->createdat = $this->normalise_timestamp(
            $createdat,
            'creation'
        );

        return $this;
    }

    /**
     * Set the update timestamp.
     *
     * @param int|null $updatedat Update timestamp.
     * @return self
     */
    public function updated_at(?int $updatedat): self {
        $this->updatedat = $this->normalise_timestamp(
            $updatedat,
            'update'
        );

        return $this;
    }

    /**
     * Set the subscription access period.
     *
     * @param int|null $startdate Start timestamp.
     * @param int|null $enddate End timestamp.
     * @return self
     */
    public function subscription_period(
        ?int $startdate,
        ?int $enddate
    ): self {
        $this->startdate = $this->normalise_timestamp(
            $startdate,
            'subscription start'
        );

        $this->enddate = $this->normalise_timestamp(
            $enddate,
            'subscription end'
        );

        if (
            $this->startdate !== null
            && $this->enddate !== null
            && $this->enddate < $this->startdate
        ) {
            throw new \InvalidArgumentException(
                'The subscription end date cannot precede its start date.'
            );
        }

        return $this;
    }

    /**
     * Set the digital download access information.
     *
     * @param string|null $token Download token.
     * @param int|null $expires Expiration timestamp.
     * @return self
     */
    public function download_access(
        ?string $token,
        ?int $expires = null
    ): self {
        $this->downloadtoken = $this->normalise_nullable_string(
            $token
        );

        $this->downloadtokenexpires = $this->normalise_timestamp(
            $expires,
            'download-token expiration'
        );

        return $this;
    }

    /**
     * Replace all purchase metadata.
     *
     * @param array $metadata Metadata.
     * @return self
     */
    public function metadata(array $metadata): self {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Add or replace one metadata value.
     *
     * @param string $key Metadata key.
     * @param mixed $value Metadata value.
     * @return self
     */
    public function metadata_value(
        string $key,
        mixed $value
    ): self {
        $key = trim($key);

        if ($key === '') {
            throw new \InvalidArgumentException(
                'A Commerce purchase metadata key cannot be empty.'
            );
        }

        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * Build the specialised Commerce purchase.
     *
     * @return CommercePurchase
     */
    public function build(): CommercePurchase {
        $type = $this->require_type();
        $reference = $this->require_reference();
        $item = $this->require_item();
        $payment = $this->require_payment();
        $status = $this->require_status();
        $legacyid = $this->require_legacy_id();
        $catalogid = $this->require_catalog_id();

        if ($item->get_type() !== $type) {
            throw new \coding_exception(
                'The Commerce item type does not match the purchase type.'
            );
        }

        $metadata = $this->metadata;
        $metadata['legacy_id'] ??= $legacyid;

        return match ($type) {
            CommercePurchaseType::SUBSCRIPTION =>
                new SubscriptionPurchase(
                    $reference,
                    $item,
                    $payment,
                    $this->userid,
                    $this->customeremail,
                    $status,
                    $legacyid,
                    $catalogid,
                    $this->startdate,
                    $this->enddate,
                    $this->createdat,
                    $this->updatedat,
                    $metadata
                ),

            CommercePurchaseType::DIGITAL =>
                new DigitalPurchase(
                    $reference,
                    $item,
                    $payment,
                    $this->userid,
                    $this->customeremail,
                    $status,
                    $legacyid,
                    $catalogid,
                    $this->downloadtoken,
                    $this->downloadtokenexpires,
                    $this->createdat,
                    $this->updatedat,
                    $metadata
                ),
        };
    }

    /**
     * Reset the builder so it can be reused.
     *
     * @return self
     */
    public function reset(): self {
        $this->type = null;
        $this->reference = null;
        $this->item = null;
        $this->payment = null;
        $this->userid = null;
        $this->customeremail = null;
        $this->status = null;
        $this->legacyid = null;
        $this->catalogid = null;
        $this->createdat = null;
        $this->updatedat = null;
        $this->startdate = null;
        $this->enddate = null;
        $this->downloadtoken = null;
        $this->downloadtokenexpires = null;
        $this->metadata = [];

        return $this;
    }

    /**
     * Return the configured type.
     *
     * @return string
     */
    private function require_type(): string {
        if ($this->type === null) {
            throw new \coding_exception(
                'The Commerce purchase type has not been configured.'
            );
        }

        return $this->type;
    }

    /**
     * Return the configured reference.
     *
     * @return string
     */
    private function require_reference(): string {
        if ($this->reference === null) {
            throw new \coding_exception(
                'The Commerce purchase reference has not been configured.'
            );
        }

        return $this->reference;
    }

    /**
     * Return the configured item.
     *
     * @return CommerceItem
     */
    private function require_item(): CommerceItem {
        if ($this->item === null) {
            throw new \coding_exception(
                'The Commerce purchase item has not been configured.'
            );
        }

        return $this->item;
    }

    /**
     * Return the configured payment.
     *
     * @return CommercePayment
     */
    private function require_payment(): CommercePayment {
        if ($this->payment === null) {
            throw new \coding_exception(
                'The Commerce purchase payment has not been configured.'
            );
        }

        return $this->payment;
    }

    /**
     * Return the configured status.
     *
     * @return string
     */
    private function require_status(): string {
        if ($this->status === null) {
            throw new \coding_exception(
                'The Commerce purchase status has not been configured.'
            );
        }

        return $this->status;
    }

    /**
     * Return the configured Legacy identifier.
     *
     * @return int
     */
    private function require_legacy_id(): int {
        if ($this->legacyid === null) {
            throw new \coding_exception(
                'The Legacy purchase identifier has not been configured.'
            );
        }

        return $this->legacyid;
    }

    /**
     * Return the configured catalog identifier.
     *
     * @return int
     */
    private function require_catalog_id(): int {
        if ($this->catalogid === null) {
            throw new \coding_exception(
                'The Commerce catalog identifier has not been configured.'
            );
        }

        return $this->catalogid;
    }

    /**
     * Normalise an optional string.
     *
     * @param string|null $value Value.
     * @return string|null
     */
    private function normalise_nullable_string(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * Validate an optional timestamp.
     *
     * @param int|null $timestamp Timestamp.
     * @param string $name Human-readable timestamp name.
     * @return int|null
     */
    private function normalise_timestamp(
        ?int $timestamp,
        string $name
    ): ?int {
        if ($timestamp !== null && $timestamp <= 0) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The Commerce purchase %s timestamp must be positive.',
                    $name
                )
            );
        }

        return $timestamp;
    }
}