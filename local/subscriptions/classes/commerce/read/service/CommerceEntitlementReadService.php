<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\read\dto\CommerceEntitlementView;

final class CommerceEntitlementReadService {
    public function __construct(private readonly CommercePurchaseReadService $purchases) {
    }

    /** @return CommerceEntitlementView[] */
    public function find_by_legacy_purchase(string $family, int $legacyid): array {
        $purchase = $this->purchases->find_by_legacy_reference($family, $legacyid);
        if ($purchase === null) {
            return [];
        }

        return array_map(
            static fn(array $item): CommerceEntitlementView => new CommerceEntitlementView(
                $purchase->uuid,
                $item['type'],
                $item['reference'],
                $item['label'],
                $item['fulfillment']
            ),
            $purchase->items
        );
    }
}
