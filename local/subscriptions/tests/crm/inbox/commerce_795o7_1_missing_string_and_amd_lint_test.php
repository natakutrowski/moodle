<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o7_1_missing_string_and_amd_lint_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_back_to_inbox_string_exists_in_all_plugin_languages(): void {
        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = $this->file(
                'lang/' . $lang . '/local_subscriptions.php'
            );

            self::assertStringContainsString(
                "$" . "string['crm_inbox_back_to_inbox']",
                $source
            );
        }
    }

    public function test_legacy_format_command_handler_is_not_left_unused(): void {
        $js = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringNotContainsString(
            'var handleFormatCommand = function',
            $js
        );

        self::assertStringNotContainsString(
            'handleFormatCommand',
            $js
        );
    }
}
