<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n113g_user360_structure_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_overview_uses_right_column_for_intelligence(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360OverviewRenderer.php'
        );

        $start = strpos($renderer, 'public static function render_overview');
        self::assertNotFalse($start);
        $end = strpos($renderer, 'private static function current_situation', $start);
        self::assertNotFalse($end);
        $method = substr($renderer, $start, $end - $start);

        self::assertStringContainsString(
            '$main = self::current_situation($profile)',
            $method
        );
        self::assertStringContainsString(
            '. self::recent_activity($profile);',
            $method
        );
        self::assertStringContainsString(
            '$sidebar .= self::intelligence_summary($profile);',
            $method
        );
        self::assertStringContainsString(
            'crm-user360-n113g-overview-grid',
            $method
        );
    }

    public function test_relation_no_longer_duplicates_intelligence_panel(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringContainsString(
            'self::intelligence_dashboard($profile)',
            $renderer
        );
        self::assertStringContainsString(
            'self::actions($profile)',
            $renderer
        );
        self::assertStringContainsString(
            'self::inbox($profile)',
            $renderer
        );
        self::assertStringContainsString(
            'self::work_items($profile)',
            $renderer
        );
        self::assertStringNotContainsString(
            'UserProfileRenderer::render_intelligence_panel(',
            $renderer
        );
    }

    public function test_timeline_uses_raw_content_without_legacy_section_wrapper(): void {
        $profile = $this->file(
            'classes/output/UserProfileRenderer.php'
        );
        $timeline = $this->file(
            'classes/crm/user360/rendering/User360TimelineRenderer.php'
        );

        self::assertStringContainsString(
            'public static function render_timeline_content',
            $profile
        );
        self::assertStringContainsString(
            'return self::timeline_content($profile);',
            $profile
        );
        self::assertStringContainsString(
            'UserProfileRenderer::render_timeline_content($profile)',
            $timeline
        );
        self::assertStringNotContainsString(
            'UserProfileRenderer::render_timeline_panel($profile)',
            $timeline
        );
    }

    public function test_timeline_and_overview_have_structural_polish_css(): void {
        $styles = $this->file('styles.css');

        self::assertStringContainsString(
            '.crm-user360-n113g-overview-grid',
            $styles
        );
        self::assertStringContainsString(
            '.crm-user360-n113g-timeline-body',
            $styles
        );
        self::assertStringContainsString(
            '.crm-user360-n113g-timeline-body .crm-timeline-filter-panel',
            $styles
        );
    }

    public function test_n113g_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
