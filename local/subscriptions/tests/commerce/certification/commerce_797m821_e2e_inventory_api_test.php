<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacitySnapshot;

/** Guards the read-only M8.2 inventory against drifting from the real 7.97 APIs. */
final class commerce_797m821_e2e_inventory_api_test extends advanced_testcase {
    public function test_inventory_uses_real_capacity_snapshot_api(): void {
        global $CFG;

        $script = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/cli/commerce/certification/m82_e2e_inventory.php'
        );

        self::assertIsString($script);
        self::assertTrue(method_exists(CommercePedagogicalCapacitySnapshot::class, 'is_pedagogically_linked'));
        self::assertTrue(method_exists(CommercePedagogicalCapacitySnapshot::class, 'are_sales_open'));
        self::assertStringContainsString('$snapshot->is_pedagogically_linked()', $script);
        self::assertStringContainsString('$snapshot->are_sales_open()', $script);
        self::assertStringNotContainsString('$snapshot->is_linked()', $script);
        self::assertStringNotContainsString('$snapshot->sales_are_open()', $script);
    }

    public function test_inventory_accepts_legacy_and_current_product_type_aliases(): void {
        global $CFG;

        $script = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/cli/commerce/certification/m82_e2e_inventory.php'
        );

        self::assertIsString($script);
        self::assertStringContainsString("['course_access', 'subscription', 'course']", $script);
        self::assertStringContainsString("['digital', 'digital_download']", $script);
        self::assertStringContainsString("\$rawtype === 'bundle'", $script);
    }
}
