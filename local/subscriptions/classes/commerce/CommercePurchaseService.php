<?php

namespace local_subscriptions\commerce;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\legacy\repository\DigitalPurchaseRepository;
use local_subscriptions\commerce\legacy\repository\SubscriptionPurchaseRepository;

/**
 * Unified read-only entry point for Commerce purchases.
 *
 * During Phase 7.93A this service delegates to historical repositories.
 * Future implementations may read from a unified schema without changing
 * callers of this service.
 */
class CommercePurchaseService {

    public function __construct(
        private readonly SubscriptionPurchaseRepository
            $subscriptionrepository =
                new SubscriptionPurchaseRepository(),

        private readonly DigitalPurchaseRepository
            $digitalrepository =
                new DigitalPurchaseRepository()
    ) {
    }

    /**
     * Returns one purchase from its historical family and identifier.
     */
    public function get_purchase(
        string $type,
        int $legacyid
    ): ?CommercePurchase {
        if ($legacyid <= 0) {
            throw new \InvalidArgumentException(
                'Commerce purchase identifier must be greater than zero.'
            );
        }

        return match (
            CommercePurchaseType::normalise($type)
        ) {
            CommercePurchaseType::SUBSCRIPTION =>
                $this->subscriptionrepository
                    ->get_by_subscription_id(
                        $legacyid
                    ),

            CommercePurchaseType::DIGITAL =>
                $this->digitalrepository
                    ->get_by_purchase_id(
                        $legacyid
                    ),
        };
    }

    /**
     * Returns all purchases known for one customer.
     *
     * Results are ordered from newest to oldest.
     *
     * @return CommercePurchase[]
     */
    public function get_customer_purchases(
        int $userid,
        ?string $email = null
    ): array {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Customer identifier must be greater than zero.'
            );
        }

        $subscriptions =
            $this->subscriptionrepository
                ->get_by_user_id(
                    $userid
                );

        $digitalpurchases =
            $this->digitalrepository
                ->get_by_user_id(
                    $userid
                );

        if (
            $email !== null
            && trim($email) !== ''
        ) {
            $digitalpurchases = array_merge(
                $digitalpurchases,
                $this->digitalrepository
                    ->get_by_email(
                        $email
                    )
            );
        }

        return $this->sort_and_deduplicate(
            array_merge(
                $subscriptions,
                $digitalpurchases
            )
        );
    }

    /**
     * Returns recent purchases from every historical Commerce family.
     *
     * @return CommercePurchase[]
     */
    public function get_recent_purchases(
        int $limit = 50
    ): array {
        $limit = max(
            1,
            min(500, $limit)
        );

        $subscriptions =
            $this->subscriptionrepository
                ->get_recent(
                    $limit
                );

        $digitalpurchases =
            $this->digitalrepository
                ->get_recent(
                    $limit
                );

        return array_slice(
            $this->sort_and_deduplicate(
                array_merge(
                    $subscriptions,
                    $digitalpurchases
                )
            ),
            0,
            $limit
        );
    }

    /**
     * @param CommercePurchase[] $purchases
     * @return CommercePurchase[]
     */
    private function sort_and_deduplicate(
        array $purchases
    ): array {
        $unique = [];

        foreach ($purchases as $purchase) {
            if (!$purchase instanceof CommercePurchase) {
                throw new \coding_exception(
                    'CommercePurchaseService received an invalid purchase.'
                );
            }

            $key =
                $purchase->get_type()
                . ':'
                . $purchase->get_reference();

            $unique[$key] = $purchase;
        }

        $purchases = array_values(
            $unique
        );

        usort(
            $purchases,
            static function (
                CommercePurchase $left,
                CommercePurchase $right
            ): int {
                $leftdate =
                    $left->get_created_at() ?? 0;

                $rightdate =
                    $right->get_created_at() ?? 0;

                if ($leftdate === $rightdate) {
                    return strcmp(
                        $right->get_reference(),
                        $left->get_reference()
                    );
                }

                return $rightdate <=> $leftdate;
            }
        );

        return $purchases;
    }
}