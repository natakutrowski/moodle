<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceMapper;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;

/** Converts the established Legacy Commerce projection into a native persistence snapshot. */
final class CommerceLegacySnapshotFactory {
    public function __construct(
        private readonly CommercePurchasePersistenceMapper $mapper = new CommercePurchasePersistenceMapper()
    ) {
    }

    public function create(CommercePurchase $purchase): CommercePurchasePersistenceSnapshot {
        $legacy = $purchase->get_legacy_reference();
        if ($legacy === null) {
            throw new \coding_exception('A Legacy migration purchase must expose a Legacy reference.');
        }
        return $this->mapper->map($purchase);
    }
}