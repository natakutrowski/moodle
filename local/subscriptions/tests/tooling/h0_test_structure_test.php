<?php
// This file is part of Moodle - http://moodle.org/

namespace local_subscriptions;

use advanced_testcase;

/**
 * Structural guardrails introduced by phase 7.94H0B.
 *
 * @coversNothing
 */
final class h0_test_structure_test extends advanced_testcase {
    public function test_no_php_tests_remain_at_tests_root(): void {
        $rootfiles = glob(__DIR__ . '/../*.php') ?: [];
        self::assertSame([], $rootfiles, 'All PHP tests must be assigned to a domain folder.');
    }

    public function test_h0a_inventories_are_committed(): void {
        $pluginroot = dirname(__DIR__, 2);
        $files = [
            'docs/tooling/README_H0A_H0B.md',
            'docs/tooling/h0a_test_inventory.json',
            'docs/tooling/h0a_test_inventory.csv',
            'docs/tooling/h0a_cli_inventory.json',
            'docs/tooling/h0a_cli_inventory.csv',
        ];

        foreach ($files as $relativepath) {
            self::assertFileExists($pluginroot . '/' . $relativepath);
        }
    }
}
