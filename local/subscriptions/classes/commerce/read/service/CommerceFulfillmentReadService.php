<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\read\dto\CommerceFulfillmentView;

final class CommerceFulfillmentReadService {
    public function __construct(private readonly CommercePurchaseReadService $purchases) {
    }

    /** @return CommerceFulfillmentView[] */
    public function find_by_legacy_purchase(string $family, int $legacyid): array {
        return $this->purchases->find_by_legacy_reference($family, $legacyid)?->fulfillments ?? [];
    }
}
