<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1210a_help_diagnostics_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_inbox_navigation_exposes_ai_diagnostics(): void {
        $registry = $this->file(
            'classes/crm/navigation/CrmNavigationRegistry.php'
        );

        self::assertStringContainsString(
            'admin_inbox_ai_diagnostics_page()',
            $registry
        );
        self::assertStringContainsString(
            'crm_nav_inbox_ai_diagnostics_n1210a',
            $registry
        );
    }

    public function test_help_diagnostics_uses_compact_dashboard_sections(): void {
        $page = $this->file(
            'admin/help/diagnostics.php'
        );

        self::assertStringContainsString(
            'crm-help-diagnostics-overview-grid',
            $page
        );
        self::assertStringContainsString(
            "'details'",
            $page
        );
        self::assertStringContainsString(
            'crm-help-diagnostics-section__count',
            $page
        );
        self::assertStringNotContainsString(
            'CrmBackLinkRenderer::render(',
            $page
        );
    }
}
