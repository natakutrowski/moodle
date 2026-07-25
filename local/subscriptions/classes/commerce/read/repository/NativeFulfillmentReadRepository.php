<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use moodle_database;

final class NativeFulfillmentReadRepository {
    public function __construct(private readonly moodle_database $database) {
    }

    /** @return \stdClass[] */
    public function find_by_purchase_id(int $purchaseid): array {
        return array_values($this->database->get_records(
            CommercePersistenceSchema::TABLE_FULFILLMENT,
            ['purchaseid' => $purchaseid],
            'sequence ASC, id ASC'
        ));
    }
}
