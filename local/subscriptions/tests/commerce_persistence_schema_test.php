<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

final class commerce_persistence_schema_test extends \advanced_testcase {
    public function test_native_table_names_are_unique_and_plugin_scoped(): void {
        $tables = CommercePersistenceSchema::table_names();

        $this->assertCount(4, $tables);
        $this->assertCount(4, array_unique($tables));
        foreach ($tables as $table) {
            $this->assertStringStartsWith('local_subscriptions_commerce_', $table);
            $this->assertLessThanOrEqual(55, strlen($table));
        }
    }

    public function test_identity_lengths_match_domain_contract(): void {
        $this->assertSame(32, CommercePersistenceSchema::PURCHASE_ID_LENGTH);
        $this->assertSame(28, CommercePersistenceSchema::PURCHASE_REFERENCE_LENGTH);
        $this->assertSame(1, CommercePersistenceSchema::SNAPSHOT_VERSION);
    }
}
