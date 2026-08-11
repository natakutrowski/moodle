<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_block_toggle_j15a1_test extends \advanced_testcase {
    public function test_ajax_casts_param_bool_before_repository_call(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../admin/commerce/showrooms/ajax.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "\$enabled = required_param('enabled', PARAM_BOOL);",
            $source
        );
        $this->assertStringContainsString('(bool)$enabled', $source);
    }
}
