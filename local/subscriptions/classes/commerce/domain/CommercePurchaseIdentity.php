<?php

namespace local_subscriptions\commerce\domain;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\CommercePurchaseType;
use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;

/**
 * Stable identity of a Commerce purchase.
 *
 * Commerce purchases are not yet persisted in a dedicated Commerce table.
 * Their identity is therefore derived deterministically from the Legacy
 * purchase type and identifier.
 *
 * Examples:
 *
 * - subscription:41
 * - digital:84
 */
final class CommercePurchaseIdentity {

    /**
     * Prefix used for public Commerce references.
     */
    private const PUBLIC_REFERENCE_PREFIX = 'cmp_';

    /**
     * Number of hexadecimal characters retained from the identity hash.
     */
    private const PUBLIC_REFERENCE_HASH_LENGTH = 24;

    /**
     * @param string $type Commerce purchase type.
     * @param int $legacyid Legacy purchase identifier.
     */
    public function __construct(
        private readonly string $type,
        private readonly int $legacyid
    ) {
        if (!CommercePurchaseType::is_valid($type)) {
            throw new \coding_exception(
                'Unsupported Commerce purchase identity type: '
                    . $type
            );
        }

        if ($legacyid <= 0) {
            throw new \coding_exception(
                'A Commerce purchase Legacy identifier must be positive.'
            );
        }
    }

    /**
     * Build an identity from a Commerce purchase.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @return self
     */
    public static function from_purchase(
        CommercePurchase $purchase
    ): self {
        if ($purchase instanceof SubscriptionPurchase) {
            return self::for_subscription(
                $purchase->get_legacy_subscription_id()
            );
        }

        if ($purchase instanceof DigitalPurchase) {
            return self::for_digital_purchase(
                $purchase->get_legacy_purchase_id()
            );
        }

        $legacyid =
            $purchase->get_metadata_value(
                'legacy_id'
            );

        if (
            is_int($legacyid)
            || (
                is_string($legacyid)
                && ctype_digit($legacyid)
            )
        ) {
            return new self(
                $purchase->get_type(),
                (int)$legacyid
            );
        }

        throw new \coding_exception(
            'Unable to determine the Legacy identity of Commerce purchase: '
                . get_class($purchase)
        );
    }

    /**
     * Build a subscription purchase identity.
     *
     * @param int $subscriptionid Legacy subscription identifier.
     * @return self
     */
    public static function for_subscription(
        int $subscriptionid
    ): self {
        return new self(
            CommercePurchaseType::SUBSCRIPTION,
            $subscriptionid
        );
    }

    /**
     * Build a digital purchase identity.
     *
     * @param int $purchaseid Legacy digital purchase identifier.
     * @return self
     */
    public static function for_digital_purchase(
        int $purchaseid
    ): self {
        return new self(
            CommercePurchaseType::DIGITAL,
            $purchaseid
        );
    }

    /**
     * Return the Commerce purchase type.
     *
     * @return string
     */
    public function get_type(): string {
        return CommercePurchaseType::normalise(
            $this->type
        );
    }

    /**
     * Return the Legacy purchase identifier.
     *
     * @return int
     */
    public function get_legacy_id(): int {
        return $this->legacyid;
    }

    /**
     * Return the stable internal identity key.
     *
     * @return string
     */
    public function get_key(): string {
        return sprintf(
            '%s:%d',
            $this->get_type(),
            $this->get_legacy_id()
        );
    }

    /**
     * Return the stable public Commerce reference.
     *
     * The same Legacy purchase always produces the same public reference.
     *
     * @return string
     */
    public function get_public_reference(): string {
        $hash =
            hash(
                'sha256',
                $this->get_key()
            );

        return self::PUBLIC_REFERENCE_PREFIX
            . substr(
                $hash,
                0,
                self::PUBLIC_REFERENCE_HASH_LENGTH
            );
    }

    /**
     * Whether this identity represents a subscription purchase.
     *
     * @return bool
     */
    public function is_subscription(): bool {
        return $this->get_type()
            === CommercePurchaseType::SUBSCRIPTION;
    }

    /**
     * Whether this identity represents a digital purchase.
     *
     * @return bool
     */
    public function is_digital(): bool {
        return $this->get_type()
            === CommercePurchaseType::DIGITAL;
    }

    /**
     * Export the identity as an array.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'key' =>
                $this->get_key(),

            'public_reference' =>
                $this->get_public_reference(),

            'type' =>
                $this->get_type(),

            'legacy_id' =>
                $this->get_legacy_id(),
        ];
    }
}