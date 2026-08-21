<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1232_inbox_email_layout_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_inbox_email_layout_has_specific_nested_table_overrides(): void {
        $styles = $this->file('styles.css');

        self::assertStringContainsString(
            '.crm-inbox-message-html > table',
            $styles
        );

        self::assertStringContainsString(
            '.crm-inbox-message-html table table',
            $styles
        );

        self::assertStringContainsString(
            '.crm-inbox-message-html a[style*="height"]',
            $styles
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
