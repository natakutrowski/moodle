<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use xmldb_field;
use xmldb_index;
use xmldb_table;

/**
 * Certifies the native Commerce persistence database schema.
 *
 * @coversNothing
 */
final class commerce_persistence_database_schema_test extends advanced_testcase {
    /**
     * The native Commerce tables must exist.
     */
    public function test_commerce_tables_exist(): void {
        global $DB;

        $dbman = $DB->get_manager();

        $tables = [
            'local_subscriptions_commerce_purchase',
            'local_subscriptions_commerce_purchase_item',
            'local_subscriptions_commerce_payment',
            'local_subscriptions_commerce_fulfillment',
        ];

        foreach ($tables as $tablename) {
            $this->assertTrue(
                $dbman->table_exists(new xmldb_table($tablename)),
                "Missing Commerce table: {$tablename}"
            );
        }
    }

    /**
     * The purchase aggregate root schema must remain stable.
     */
    public function test_purchase_table_contract(): void {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table(
            'local_subscriptions_commerce_purchase'
        );

        $this->assertFieldsExist(
            $table,
            [
                'id',
                'purchaseuuid',
                'reference',
                'type',
                'legacyfamily',
                'legacyid',
                'userid',
                'customeremail',
                'status',
                'currency',
                'subtotalminor',
                'discountminor',
                'totalminor',
                'customerjson',
                'snapshotjson',
                'metadatajson',
                'snapshotversion',
                'timecreated',
                'timemodified',
            ]
        );

        $this->assertTrue(
            $dbman->index_exists(
                $table,
                new xmldb_index(
                    'purchaseuuid_uix',
                    XMLDB_INDEX_UNIQUE,
                    ['purchaseuuid']
                )
            )
        );

        $this->assertTrue(
            $dbman->index_exists(
                $table,
                new xmldb_index(
                    'reference_uix',
                    XMLDB_INDEX_UNIQUE,
                    ['reference']
                )
            )
        );

        $this->assertTrue(
            $dbman->index_exists(
                $table,
                new xmldb_index(
                    'legacy_reference_uix',
                    XMLDB_INDEX_UNIQUE,
                    [
                        'legacyfamily',
                        'legacyid',
                    ]
                )
            )
        );
    }

    /**
     * Purchase items must preserve stable ordering.
     */
    public function test_purchase_item_table_contract(): void {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table(
            'local_subscriptions_commerce_purchase_item'
        );

        $this->assertFieldsExist(
            $table,
            [
                'id',
                'purchaseid',
                'position',
                'itemtype',
                'itemreference',
                'label',
                'quantity',
                'currency',
                'unitminor',
                'grossminor',
                'discountminor',
                'netminor',
                'pricingjson',
                'fulfillmentjson',
                'metadatajson',
            ]
        );

        $this->assertTrue(
            $dbman->index_exists(
                $table,
                new xmldb_index(
                    'purchase_position_uix',
                    XMLDB_INDEX_UNIQUE,
                    [
                        'purchaseid',
                        'position',
                    ]
                )
            )
        );
    }

    /**
     * Payments must preserve stable ordering per purchase.
     */
    public function test_payment_table_contract(): void {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table(
            'local_subscriptions_commerce_payment'
        );

        $this->assertFieldsExist(
            $table,
            [
                'id',
                'purchaseid',
                'sequence',
                'provider',
                'providerreference',
                'status',
                'currency',
                'amountminor',
                'transactionid',
                'legacyrequestid',
                'paidat',
                'metadatajson',
            ]
        );

        $this->assertTrue(
            $dbman->index_exists(
                $table,
                new xmldb_index(
                    'purchase_sequence_uix',
                    XMLDB_INDEX_UNIQUE,
                    [
                        'purchaseid',
                        'sequence',
                    ]
                )
            )
        );
    }

    /**
     * Fulfillments must enforce idempotence.
     */
    public function test_fulfillment_table_contract(): void {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table(
            'local_subscriptions_commerce_fulfillment'
        );

        $this->assertFieldsExist(
            $table,
            [
                'id',
                'purchaseid',
                'sequence',
                'reference',
                'fulfillmentkey',
                'idempotencykey',
                'status',
                'metadatajson',
            ]
        );

        $this->assertTrue(
            $dbman->index_exists(
                $table,
                new xmldb_index(
                    'purchase_sequence_uix',
                    XMLDB_INDEX_UNIQUE,
                    [
                        'purchaseid',
                        'sequence',
                    ]
                )
            )
        );

        $this->assertTrue(
            $dbman->index_exists(
                $table,
                new xmldb_index(
                    'idempotencykey_uix',
                    XMLDB_INDEX_UNIQUE,
                    ['idempotencykey']
                )
            )
        );

        $this->assertTrue(
            $dbman->index_exists(
                $table,
                new xmldb_index(
                    'purchase_reference_uix',
                    XMLDB_INDEX_UNIQUE,
                    [
                        'purchaseid',
                        'reference',
                    ]
                )
            )
        );
    }

    /**
     * Assert that all required fields exist.
     *
     * @param xmldb_table $table Table definition.
     * @param string[] $fieldnames Required field names.
     */
    private function assertFieldsExist(
        xmldb_table $table,
        array $fieldnames
    ): void {
        global $DB;

        $dbman = $DB->get_manager();

        foreach ($fieldnames as $fieldname) {
            $this->assertTrue(
                $dbman->field_exists(
                    $table,
                    new xmldb_field($fieldname)
                ),
                "Missing field {$table->getName()}.{$fieldname}"
            );
        }
    }
}