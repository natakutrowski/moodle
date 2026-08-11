<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_upgrade_z2a_test extends \advanced_testcase {
    public function test_showroom_bootstrap_is_created_as_draft_before_prod_configuration(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/db/upgrade.php'
        );

        $start = strpos($source, '$oldversion < 2026080500');
        $end = strpos(
            $source,
            "upgrade_plugin_savepoint(true, 2026080500, 'local', 'subscriptions');",
            $start
        );

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        $block = substr($source, $start, $end - $start);

        $this->assertStringContainsString(
            "'showroomkey' => 'third-group-verbs'",
            $block
        );
        $this->assertStringContainsString(
            "'status' => 'draft'",
            $block
        );
        $this->assertStringNotContainsString(
            "'status' => 'published'",
            $block
        );
    }
}
