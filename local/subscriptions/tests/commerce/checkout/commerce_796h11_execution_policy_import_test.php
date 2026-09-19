<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h11_execution_policy_import_test extends advanced_testcase {
    public function test_checkout_imports_execution_policy_before_using_it(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        $import =
            'use local_subscriptions\\commerce\\checkout\\execution\\'
            . 'CommerceCheckoutExecutionPolicy;';

        $this->assertStringContainsString(
            $import,
            $contents
        );

        $importpos = strpos(
            $contents,
            $import
        );
        $usagepos = strpos(
            $contents,
            'CommerceCheckoutExecutionPolicy::is_executable_now('
        );

        $this->assertNotFalse($importpos);
        $this->assertNotFalse($usagepos);
        $this->assertLessThan(
            $usagepos,
            $importpos
        );
    }
}
