<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

final class commerce_persistence_schema_test extends \advanced_testcase {
    public function test_native_table_names_are_unique_and_plugin_scoped(): void {
        $tables = CommercePersistenceSchema::table_names();

        $this->assertNotEmpty($tables);
        $this->assertCount(count($tables), array_unique($tables));
        foreach ($tables as $table) {
            $isfullprefix = str_starts_with(
                $table,
                'local_subscriptions_commerce_'
            );
            $iscompactprefix = str_starts_with(
                $table,
                'local_subs_commerce_'
            );

            $this->assertTrue(
                $isfullprefix || $iscompactprefix,
                'Native Commerce table is not scoped to local_subscriptions: ' . $table
            );
            $this->assertLessThanOrEqual(55, strlen($table));
        }
    }

    public function test_identity_lengths_match_domain_contract(): void {
        $this->assertSame(32, CommercePersistenceSchema::PURCHASE_ID_LENGTH);
        $this->assertSame(28, CommercePersistenceSchema::PURCHASE_REFERENCE_LENGTH);
        $this->assertSame(1, CommercePersistenceSchema::SNAPSHOT_VERSION);
    }
}
