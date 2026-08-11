<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_defaults_editor_j15c_test extends \advanced_testcase {
    public function test_builder_does_not_double_escape_block_json(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../admin/commerce/showrooms/edit.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "'data-config' => (string)\$block->configjson",
            $source
        );
        $this->assertStringNotContainsString(
            "'data-config' => s(\$block->configjson)",
            $source
        );
    }

    public function test_builder_can_decode_legacy_escaped_json(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../js/showroom_builder.js'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "textarea.innerHTML = value || ''",
            $source
        );
    }
}
