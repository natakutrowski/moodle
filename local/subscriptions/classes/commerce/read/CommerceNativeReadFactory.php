<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

/** Composition root for I6 shadow reads. */
final class CommerceNativeReadFactory {
    public static function create(): CommerceNativeReadService {
        return new CommerceNativeReadService(
            CommerceLegacyMigrationFactory::create_source_registry(),
            new CommerceLegacySnapshotFactory(),
            CommercePurchaseSqlRepositoryFactory::create(),
            new CommerceLegacyNativeComparator(),
            new CommerceNativeReadFeatureToggle(),
            new CommerceNativeReadMetrics(),
            new CommerceNativeReadLogger()
        );
    }
}
