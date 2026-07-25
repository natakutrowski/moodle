<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

/** Composition root for I4D.8 certification services. */
final class CommerceNativePersistenceCertificationFactory {
    public static function create(): CommerceNativePersistenceCertificationService {
        return new CommerceNativePersistenceCertificationService(
            CommerceLegacyMigrationFactory::create_source_registry(),
            new CommerceLegacySnapshotFactory(),
            CommercePurchaseSqlRepositoryFactory::create(),
            new CommerceLegacyNativeComparator(),
            new CommercePersistenceSnapshotHasher()
        );
    }
}
