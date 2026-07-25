<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\read;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

/** Composition root for I7 runtime reads. */
final class CommerceRuntimeReadFactory {
    public static function create(?string $mode = null, ?bool $strict = null): CommerceRuntimeReadService {
        return new CommerceRuntimeReadService(
            CommerceLegacyMigrationFactory::create_source_registry(),
            new CommerceLegacySnapshotFactory(),
            CommercePurchaseSqlRepositoryFactory::create(),
            new CommerceLegacyNativeComparator(),
            new CommerceRuntimeReadFeatureToggle($mode, $strict),
            new CommerceRuntimeReadMetrics(),
            new CommerceRuntimeReadLogger()
        );
    }
}
