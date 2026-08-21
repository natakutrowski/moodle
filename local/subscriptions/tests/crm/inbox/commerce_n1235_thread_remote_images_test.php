<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1235_thread_remote_images_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_thread_page_reads_and_passes_loadimages_flag(): void {
        $page = $this->file('admin/inbox/thread.php');

        self::assertStringContainsString(
            "optional_param(\n    'loadimages'",
            $page
        );
        self::assertStringContainsString(
            '$allowremoteimages',
            $page
        );
        self::assertStringContainsString(
            'InboxThreadWorkspaceRenderer::render(',
            $page
        );
    }

    public function test_workspace_propagates_remote_image_flag_to_message_renderer(): void {
        $workspace = $this->file(
            'classes/crm/inbox/workspace/InboxThreadWorkspaceRenderer.php'
        );
        $factory = $this->file(
            'classes/crm/inbox/workspace/InboxThreadWorkspaceFactory.php'
        );

        self::assertStringContainsString(
            'bool $allowremoteimages = false',
            $workspace
        );
        self::assertStringContainsString(
            '$allowremoteimages',
            $factory
        );
        self::assertStringContainsString(
            "render_messages_panel(\n                            \$thread,\n                            \$allowremoteimages",
            $factory
        );
    }

    public function test_inbox_javascript_supports_full_thread_image_opt_in(): void {
        $source = $this->file('amd/src/inbox_ui.js');
        $build = $this->file('amd/build/inbox_ui.min.js');

        foreach ([$source, $build] as $javascript) {
            self::assertMatchesRegularExpression(
                '/searchParams\.set\(\s*["\']loadimages["\']\s*,\s*["\']1["\']\s*\)/',
                $javascript
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
