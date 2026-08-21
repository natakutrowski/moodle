<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n124f1_prompt_language_helper_runtime_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);

        self::assertIsString($content);

        return $content;
    }

    public function test_language_aware_prompt_builders_define_language_name_helper(): void {
        foreach ([
            'classes/crm/inbox/ai/prompts/InboxTranslationPromptBuilder.php',
            'classes/crm/inbox/ai/prompts/InboxSummaryPromptBuilder.php',
            'classes/crm/inbox/ai/prompts/InboxReplyPromptBuilder.php',
        ] as $file) {
            $content = $this->file($file);

            self::assertStringContainsString(
                'private static function language_name(',
                $content
            );

            self::assertStringContainsString(
                "'fr' => 'French'",
                $content
            );

            self::assertStringContainsString(
                "'ru' => 'Russian'",
                $content
            );

            self::assertStringContainsString(
                "'en' => 'English'",
                $content
            );
        }
    }

    public function test_plugin_version_is_unchanged(): void {
        $version = $this->file('version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
