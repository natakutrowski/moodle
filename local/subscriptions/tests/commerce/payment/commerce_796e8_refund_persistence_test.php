<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e8_refund_persistence_test extends advanced_testcase {
    public function test_native_refund_table_is_declared_in_schema_and_install_xml(): void {
        global $CFG, $DB;

        $this->assertSame(
            'local_subscriptions_commerce_refund',
            CommercePersistenceSchema::TABLE_REFUND
        );

        $columns = $DB->get_columns(
            CommercePersistenceSchema::TABLE_REFUND
        );

        foreach ([
            'paymentid',
            'provider',
            'providerrefundid',
            'idempotencykey',
            'status',
            'currency',
            'amountminor',
            'reason',
            'metadatajson',
            'providerpayload',
            'createdby',
        ] as $column) {
            $this->assertArrayHasKey($column, $columns);
        }
    }

    public function test_refund_schema_upgrade_remains_present_after_later_plugin_versions(): void {
        global $CFG;

        $version = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/version.php'
        );
        $upgrade = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/db/upgrade.php'
        );

        $this->assertIsString($version);
        $this->assertMatchesRegularExpression(
            '/\$plugin->version\s*=\s*(\d+);/',
            $version
        );
        preg_match('/\$plugin->version\s*=\s*(\d+);/', $version, $matches);
        $this->assertGreaterThanOrEqual(2026082301, (int)$matches[1]);
        $this->assertStringContainsString(
            'local_subscriptions_commerce_refund',
            $upgrade
        );
        $this->assertStringContainsString(
            'upgrade_plugin_savepoint',
            $upgrade
        );
    }
}
