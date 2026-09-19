<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_797j15_availability_install_order_test extends advanced_testcase {
    public function test_availability_install_hook_tolerates_local_plugin_not_installed_yet(): void {
        $moodleroot = dirname(__DIR__, 5);
        $source = file_get_contents(
            $moodleroot . '/availability/condition/campusfr/db/install.php'
        );

        self::assertStringContainsString(
            "new xmldb_table('local_subs_commerce_course_cfg')",
            $source
        );
        self::assertStringContainsString(
            'if (!$manager->table_exists($table))',
            $source
        );
        self::assertStringContainsString(
            'return;',
            $source
        );
    }
}
