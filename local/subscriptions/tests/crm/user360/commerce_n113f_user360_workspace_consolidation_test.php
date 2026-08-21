<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n113f_user360_workspace_consolidation_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_overview_no_longer_duplicates_inbox_and_work_items(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360OverviewRenderer.php'
        );

        $start = strpos($renderer, 'public static function render_overview');
        self::assertNotFalse($start);
        $end = strpos($renderer, 'private static function current_situation', $start);
        self::assertNotFalse($end);
        $method = substr($renderer, $start, $end - $start);

        self::assertStringContainsString(
            '$sidebar = self::priority_actions($profile);',
            $method
        );
        self::assertStringNotContainsString(
            'self::inbox_summary($profile)',
            $method
        );
        self::assertStringNotContainsString(
            'self::work_items_summary($profile)',
            $method
        );
    }

    public function test_relation_heading_no_longer_duplicates_intelligence_scores(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringNotContainsString(
            'crm-user360-n113c-metrics',
            $renderer
        );
        self::assertStringContainsString(
            'self::intelligence_dashboard($profile)',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n115e-main-grid',
            $renderer
        );
    }

    public function test_relation_remains_single_owner_of_detailed_crm_domains(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringContainsString(
            'self::intelligence_dashboard($profile)',
            $renderer
        );
        self::assertSame(
            1,
            substr_count(
                $renderer,
                'UserProfileRenderer::render_work_items_panel('
            )
        );
        self::assertSame(
            1,
            substr_count(
                $renderer,
                'UserProfileRenderer::render_customer_success_panel('
            )
        );
        self::assertSame(
            1,
            substr_count(
                $renderer,
                'render_assistant_recommendations_content('
            )
        );
    }

    public function test_assistant_is_collapsed_by_default(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringContainsString(
            'private static function assistant(',
            $renderer
        );
        self::assertStringContainsString(
            'render_assistant_recommendations_content(',
            $renderer
        );
        self::assertStringContainsString(
            'render_assistant_conversation_content(',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n115d-assistant-layout',
            $renderer
        );
    }

    public function test_n113f_strings_exist_in_all_languages(): void {
        foreach (['en', 'fr', 'ru'] as $lang) {
            $strings = $this->file(
                'lang/' . $lang . '/local_subscriptions.php'
            );

            self::assertStringContainsString(
                '$string[\'crm_user360_n113f_assistant_open\']',
                $strings
            );
        }
    }

    public function test_n113f_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
