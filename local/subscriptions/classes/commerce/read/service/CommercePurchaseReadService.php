<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\read\dto\CommercePurchaseView;
use local_subscriptions\commerce\read\repository\NativeEntitlementReadRepository;
use local_subscriptions\commerce\read\repository\NativeFulfillmentReadRepository;
use local_subscriptions\commerce\read\repository\NativePaymentReadRepository;
use local_subscriptions\commerce\read\repository\NativePurchaseReadRepository;

final class CommercePurchaseReadService {
    public function __construct(
        private readonly NativePurchaseReadRepository $purchases,
        private readonly NativePaymentReadRepository $payments,
        private readonly NativeFulfillmentReadRepository $fulfillments,
        private readonly NativeEntitlementReadRepository $entitlements,
        private readonly CommerceReadModelMapper $mapper
    ) {
    }

    public function find_by_legacy_reference(string $family, int $legacyid): ?CommercePurchaseView {
        return $this->map($this->purchases->find_record_by_legacy_reference($family, $legacyid));
    }

    public function find_by_reference(string $reference): ?CommercePurchaseView {
        return $this->map($this->purchases->find_record_by_reference($reference));
    }

    /** @return CommercePurchaseView[] */
    public function find_by_customer(int $userid, ?string $email = null): array {
        return array_values(array_filter(array_map(
            fn(\stdClass $record): ?CommercePurchaseView => $this->map($record),
            $this->purchases->find_records_by_customer($userid, $email)
        )));
    }

    private function map(?\stdClass $purchase): ?CommercePurchaseView {
        if ($purchase === null) {
            return null;
        }

        $purchaseid = (int)$purchase->id;
        return $this->mapper->map_purchase(
            $purchase,
            $this->entitlements->find_items_by_purchase_id($purchaseid),
            $this->payments->find_by_purchase_id($purchaseid),
            $this->fulfillments->find_by_purchase_id($purchaseid)
        );
    }
}
