<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\rollout\CommerceRuntimeWriteInventory;

final class commerce_i10e_runtime_write_inventory_test extends advanced_testcase {
    public function test_scanner_ignores_tests_and_detects_unapproved_commerce_write(): void {
        $directory = make_request_directory();

        mkdir($directory . '/classes/custom', 0777, true);
        mkdir($directory . '/tests', 0777, true);

        file_put_contents(
            $directory . '/classes/custom/service.php',
            "<?php \$DB->update_record('user_subscription', \$record);"
        );

        file_put_contents(
            $directory . '/tests/test.php',
            "<?php \$DB->update_record('user_subscription', \$record);"
        );

        $inventory = new CommerceRuntimeWriteInventory();
        $findings = $inventory->direct_legacy($inventory->scan($directory));

        $this->assertCount(1, $findings);
        $this->assertSame(
            'classes/custom/service.php',
            $findings[0]->get_file()
        );
    }
}
