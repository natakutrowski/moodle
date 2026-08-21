<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n115f_relation_readability_support_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_relation_panel_keeps_advanced_dashboard_structure(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringContainsString(
            'crm-user360-n115e-main-grid',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n115e-side-column',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n115e-assistant-column',
            $renderer
        );
    }


    public function test_recent_orders_show_datetime_not_date_only(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        self::assertStringContainsString(
            'AdminFormatter::datetime((int)$purchase->timecreated)',
            $renderer
        );
    }

    public function test_course_rows_no_longer_render_xp_scope_badge(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        self::assertStringNotContainsString(
            'xp_scope_badge',
            $renderer
        );
        self::assertStringContainsString(
            'lastactivityname',
            $renderer
        );
    }

    public function test_global_xp_uses_crescent_icon(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        self::assertStringContainsString(
            "'fa fa-moon-o'",
            $renderer
        );
        self::assertStringNotContainsString(
            "'fa fa-trophy'",
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
