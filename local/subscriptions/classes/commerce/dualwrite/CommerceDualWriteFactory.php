<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\dualwrite;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

final class CommerceDualWriteFactory {
    public static function create(): CommerceDualWriteService {
        return new CommerceDualWriteService(
            CommerceLegacyMigrationFactory::create_source_registry(),
            new CommerceLegacySnapshotFactory(),
            CommercePurchaseSqlRepositoryFactory::create(),
            new CommerceLegacyNativeComparator(),
            new CommerceDualWriteFeatureToggle(),
            new CommerceDualWriteLogger()
        );
    }
}
