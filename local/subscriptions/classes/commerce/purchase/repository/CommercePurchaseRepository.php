<?php

namespace local_subscriptions\commerce\purchase\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\CommercePurchaseService;
use local_subscriptions\commerce\CommercePurchaseType;
use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\domain\CommercePurchaseIdentity;
use local_subscriptions\commerce\domain\value\CommercePurchaseId;
use local_subscriptions\commerce\domain\value\CommercePurchaseReference;

/**
 * Unified read-only repository for Commerce purchases.
 *
 * This repository currently delegates to CommercePurchaseService, which
 * itself reads from the historical subscription and digital purchase tables.
 */
final class CommercePurchaseRepository {

    /**
     * @param CommercePurchaseService $purchaseservice Purchase service.
     */
    public function __construct(
        private readonly CommercePurchaseService $purchaseservice
    ) {
    }

    /**
     * Get a purchase from its family and Legacy identifier.
     *
     * @param string $type Purchase type.
     * @param int $legacyid Legacy identifier.
     * @return CommercePurchase|null
     */
    public function get(
        string $type,
        int $legacyid
    ): ?CommercePurchase {
        return $this->purchaseservice->get_purchase(
            CommercePurchaseType::normalise($type),
            $legacyid
        );
    }

    /**
     * Get a purchase from a Commerce identity.
     *
     * @param CommercePurchaseIdentity $identity Purchase identity.
     * @return CommercePurchase|null
     */
    public function get_by_identity(
        CommercePurchaseIdentity $identity
    ): ?CommercePurchase {
        return $this->get(
            $identity->get_type(),
            $identity->get_legacy_id()
        );
    }


    /** Resolve a purchase through its native Commerce identity. */
    public function get_by_purchase_id(
        CommercePurchaseId $purchaseid,
        int $scanlimit = 1000
    ): ?CommercePurchase {
        foreach ($this->get_recent($scanlimit) as $purchase) {
            if ($purchase->get_purchase_id()->equals($purchaseid)) {
                return $purchase;
            }
        }
        return null;
    }

    /** Resolve a purchase through its stable public reference. */
    public function get_by_reference(
        CommercePurchaseReference $reference,
        int $scanlimit = 1000
    ): ?CommercePurchase {
        foreach ($this->get_recent($scanlimit) as $purchase) {
            if ($purchase->get_purchase_reference()->equals($reference)) {
                return $purchase;
            }
        }
        return null;
    }

    /**
     * Get purchases known for a registered customer.
     *
     * @param int $userid Moodle user identifier.
     * @param string|null $email Customer email.
     * @return CommercePurchase[]
     */
    public function get_for_customer(
        int $userid,
        ?string $email = null
    ): array {
        return $this->purchaseservice->get_customer_purchases(
            $userid,
            $email
        );
    }

    /**
     * Get recent purchases from all Commerce families.
     *
     * @param int $limit Maximum number of purchases.
     * @return CommercePurchase[]
     */
    public function get_recent(int $limit = 50): array {
        return $this->purchaseservice->get_recent_purchases(
            $limit
        );
    }
}