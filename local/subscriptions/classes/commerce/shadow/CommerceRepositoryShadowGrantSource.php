<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;

/** Read-only adapter over the Native entitlement ledger. */
final class CommerceRepositoryShadowGrantSource implements CommerceShadowGrantSource {
    public function __construct(
        private readonly CommerceEntitlementGrantRepository $repository,
        private readonly CommerceEntitlementGrantRecordMapper $mapper
    ) {
    }

    public function find_for_purchase(string $purchasereference): array {
        $grants = [];
        foreach ($this->repository->find_by_purchase_reference(trim($purchasereference)) as $record) {
            $grants[] = $this->mapper->from_record($record);
        }
        return $grants;
    }
}
