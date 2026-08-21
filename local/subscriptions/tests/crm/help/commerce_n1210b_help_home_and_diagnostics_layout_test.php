<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1210b_help_home_and_diagnostics_layout_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_help_diagnostics_has_explicit_collapse_chevron(): void {
        $page = $this->file(
            'admin/help/diagnostics.php'
        );

        self::assertStringContainsString(
            'crm-help-diagnostics-section__chevron',
            $page
        );
        self::assertStringContainsString(
            'fa-chevron-down',
            $page
        );
    }

    public function test_help_home_uses_quick_navigation_and_separate_search_panel(): void {
        $page = $this->file(
            'admin/help/index.php'
        );

        self::assertStringContainsString(
            'crm-help-quick-nav',
            $page
        );
        self::assertStringContainsString(
            'HelpRenderer::render_search_hero(',
            $page
        );

        $renderer = $this->file(
            'classes/crm/help/HelpRenderer.php'
        );

        self::assertStringContainsString(
            'public static function render_search_hero(',
            $renderer
        );
    }
}
