<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

/** Composition root for I4D migration services. */
final class CommerceLegacyMigrationFactory {
    public static function create_source_registry(): CommerceLegacyPurchaseSourceRegistry {
        global $DB;
        return new CommerceLegacyPurchaseSourceRegistry([
            new LegacySubscriptionPurchaseSource($DB),
            new LegacyDigitalPurchaseSource($DB),
        ]);
    }

    public static function create_migrator(): CommerceLegacyPurchaseMigrator {
        return new CommerceLegacyPurchaseMigrator(
            self::create_source_registry(),
            new CommerceLegacySnapshotFactory(),
            CommercePurchaseSqlRepositoryFactory::create(),
            new CommerceLegacyNativeComparator()
        );
    }
}
