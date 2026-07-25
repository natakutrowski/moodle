<?php

namespace local_subscriptions\commerce\domain\purchase;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\domain\value\CommerceCustomerSnapshot;
use local_subscriptions\commerce\domain\value\CommercePurchaseId;
use local_subscriptions\commerce\domain\value\CommercePurchaseItem;
use local_subscriptions\commerce\domain\value\CommercePurchaseReference;
use local_subscriptions\commerce\domain\value\CommercePurchaseSnapshot;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;

/** Native provider-independent Commerce purchase. */
final class NativePurchase extends CommercePurchase {
    private readonly string $type;

    public function __construct(
        string $type,
        CommercePurchaseId $purchaseid,
        CommercePurchaseReference $reference,
        CommerceCustomerSnapshot $customer,
        array $items,
        CommercePurchaseSnapshot $snapshot,
        string $status,
        array $payments = [],
        array $fulfillments = [],
        ?int $createdat = null,
        ?int $updatedat = null,
        array $metadata = []
    ) {
        $normalisedtype = strtolower(trim($type));
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $normalisedtype)) {
            throw new \coding_exception('Invalid native Commerce purchase type.');
        }
        $this->type = $normalisedtype;

        parent::__construct(
            $reference->get_value(),
            null,
            null,
            $customer->get_user_id(),
            $customer->get_email(),
            $status,
            $createdat,
            $updatedat,
            $metadata,
            $purchaseid,
            $reference,
            null,
            $customer,
            $items,
            $snapshot,
            $payments,
            $fulfillments
        );
    }

    public function get_type(): string {
        return $this->type;
    }
}