<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n117c_timeline_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_timeline_renderer_does_not_duplicate_advanced_heading(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360TimelineRenderer.php'
        );

        self::assertStringContainsString(
            'crm-user360-n117c-timeline',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n117c-timeline-metrics',
            $renderer
        );
        self::assertStringContainsString(
            'render_timeline_content($profile)',
            $renderer
        );
        self::assertStringNotContainsString(
            'crm-user360-n113d-heading-copy',
            $renderer
        );
    }

    public function test_timeline_details_have_visible_affordance(): void {
        $renderer = $this->file(
            'classes/output/UserProfileRenderer.php'
        );

        self::assertStringContainsString(
            'crm-timeline-toggle-label',
            $renderer
        );
        self::assertStringContainsString(
            'crm_timeline_view_details',
            $renderer
        );
        self::assertStringContainsString(
            'data-timeline-toggle',
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
