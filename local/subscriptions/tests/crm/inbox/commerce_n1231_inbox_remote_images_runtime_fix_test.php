<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1231_inbox_remote_images_runtime_fix_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_remote_image_flag_is_propagated_to_each_message(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxThreadRenderer.php'
        );

        self::assertStringContainsString(
            "self::message(\n                \$message,\n                \$allowremoteimages",
            $renderer
        );

        self::assertStringContainsString(
            "private static function message(\n        object \$message,\n        bool \$allowremoteimages = false",
            $renderer
        );

        self::assertStringContainsString(
            "\$sanitizer->sanitize(\n                (string)\$message->bodyhtml,\n                \$allowremoteimages",
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
