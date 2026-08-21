<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n116b1_context_menu_runtime_fix_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_legacy_subscription_actions_match_sectioned_context_menu_api(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            "\$sections = [",
            $renderer
        );
        self::assertStringContainsString(
            "'order' => [",
            $renderer
        );
        self::assertStringContainsString(
            "'communication' => []",
            $renderer
        );
        self::assertStringContainsString(
            'return self::context_menu($sections);',
            $renderer
        );
        self::assertStringNotContainsString(
            'menu_separator(',
            $renderer
        );
    }

    public function test_plugin_version_is_unchanged(): void {
        $version = $this->file('version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
