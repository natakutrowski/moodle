<?php

declare(strict_types=1);

namespace local_campus;

final class customer_breadcrumb_j10e32_test extends \advanced_testcase {
    public function test_late_breadcrumb_hook_is_removed(): void {
        $hooks = file_get_contents(
            dirname(__DIR__, 2) . '/db/hooks.php'
        );
        $callbacks = file_get_contents(
            dirname(__DIR__, 2) . '/classes/hooks/output/callbacks.php'
        );

        $this->assertIsString($hooks);
        $this->assertIsString($callbacks);
        $this->assertStringNotContainsString(
            'before_standard_head_html_generation',
            $hooks
        );
        $this->assertStringNotContainsString(
            'before_standard_head_html_generation',
            $callbacks
        );
    }
}
