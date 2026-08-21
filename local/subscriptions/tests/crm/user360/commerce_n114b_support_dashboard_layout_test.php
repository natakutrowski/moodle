<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n114b_support_dashboard_layout_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_support_dashboard_uses_three_semantic_columns(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        self::assertStringContainsString(
            'crm-user360-n114b-dashboard-grid',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n114b-column-left',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n114b-column-centre',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n114b-column-right',
            $renderer
        );

        $start = strpos($renderer, 'public static function render(');
        self::assertNotFalse($start);
        $end = strpos($renderer, 'private static function purchases', $start);
        self::assertNotFalse($end);
        $method = substr($renderer, $start, $end - $start);

        self::assertStringContainsString(
            '$left = self::purchases($profile)',
            $method
        );
        self::assertStringContainsString(
            '. self::recent_activity($profile);',
            $method
        );
        self::assertStringContainsString(
            '$centre = self::learning($profile)',
            $method
        );
        self::assertStringContainsString(
            '. self::digital_products($profile)',
            $method
        );
        self::assertStringContainsString(
            '. self::recent_notes($profile);',
            $method
        );
        self::assertStringContainsString(
            '$right = self::communication($profile)',
            $method
        );
        self::assertStringContainsString(
            '. self::support_actions($profile);',
            $method
        );
    }

    public function test_main_header_has_identity_and_kpis_but_no_quick_actions(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );
        $factory = $this->file(
            'classes/crm/user360/workspace/User360WorkspaceFactory.php'
        );

        $start = strpos($renderer, 'public static function render_hero');
        self::assertNotFalse($start);
        $end = strpos($renderer, 'public static function render_kpis', $start);
        self::assertNotFalse($end);
        $hero = substr($renderer, $start, $end - $start);

        self::assertStringContainsString(
            'crm-user360-n114b-hero-kpis',
            $hero
        );
        self::assertStringNotContainsString(
            'support_actions(',
            $hero
        );
        self::assertStringContainsString(
            'User360SupportOverviewRenderer::render_hero(',
            $factory
        );
    }

    public function test_dashboard_has_distinct_section_color_system(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );
        $styles = $this->file('styles.css');

        foreach (['pink', 'violet', 'blue', 'purple', 'orange', 'green'] as $tone) {
            self::assertStringContainsString(
                "'{$tone}'",
                $renderer
            );
            self::assertStringContainsString(
                ".crm-user360-n114b-card.is-{$tone}",
                $styles
            );
        }
    }

    public function test_learning_keeps_progress_and_levelup_scope_visible(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        self::assertStringContainsString(
            'progresspercentage',
            $renderer
        );
        self::assertStringContainsString(
            'completedactivities',
            $renderer
        );
        self::assertStringContainsString(
            'trackedactivities',
            $renderer
        );
        self::assertStringNotContainsString(
            'xp_scope_badge',
            $renderer
        );
        self::assertStringContainsString(
            'lastactivityname',
            $renderer
        );
        self::assertStringContainsString(
            "'site'",
            $renderer
        );
        self::assertStringContainsString(
            "'course'",
            $renderer
        );
    }

    public function test_exchange_card_uses_recent_inbox_threads(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        self::assertStringContainsString(
            '$inbox->recentthreads',
            $renderer
        );
        self::assertStringContainsString(
            'admin_inbox_thread_page()',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n114b-thread-row',
            $renderer
        );
    }

    public function test_quick_actions_are_a_dedicated_green_card(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        $start = strpos($renderer, 'private static function support_actions');
        self::assertNotFalse($start);
        $end = strpos($renderer, 'private static function hero_metric', $start);
        self::assertNotFalse($end);
        $method = substr($renderer, $start, $end - $start);

        self::assertStringContainsString(
            'crm-user360-n114b-action-grid',
            $method
        );
        self::assertStringContainsString(
            "'green'",
            $method
        );
    }

    public function test_n114b_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
