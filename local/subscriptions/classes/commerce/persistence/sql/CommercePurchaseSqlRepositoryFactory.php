<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\persistence\sql;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds the native Commerce SQL persistence repository.
 */
final class CommercePurchaseSqlRepositoryFactory {
    public static function create(): CommercePurchaseSqlRepository {
        global $DB;

        $hydrator = new CommercePurchaseSqlHydrator();

        return new CommercePurchaseSqlRepository(
            new CommercePurchaseSqlWriter($DB),
            new CommercePurchaseSqlReader(
                $DB,
                $hydrator
            )
        );
    }
}