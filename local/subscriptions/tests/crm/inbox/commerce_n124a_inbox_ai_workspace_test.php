<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n124a_inbox_ai_workspace_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_ai_workspace_is_registered_in_main_reading_zone(): void {
        $factory = $this->file(
            'classes/crm/inbox/workspace/InboxThreadWorkspaceFactory.php'
        );

        $aistart = strpos(
            $factory,
            'private static function register_ai('
        );
        self::assertNotFalse($aistart);

        $aichunk = substr(
            $factory,
            $aistart
        );

        self::assertStringContainsString(
            'zone: self::ZONE_READING',
            $aichunk
        );

        $overviewstart = strpos(
            $factory,
            'private static function register_overview('
        );
        self::assertNotFalse($overviewstart);

        $overviewend = strpos(
            $factory,
            'private static function register_contact(',
            $overviewstart
        );
        self::assertNotFalse($overviewend);

        $overviewchunk = substr(
            $factory,
            $overviewstart,
            $overviewend - $overviewstart
        );

        self::assertStringContainsString(
            'zone: self::ZONE_CONTEXT',
            $overviewchunk
        );
    }

    public function test_ai_panel_exposes_translation_analysis_and_reply_tools(): void {
        $renderer = $this->file(
            'classes/crm/inbox/ai/rendering/InboxAiPanelRenderer.php'
        );

        foreach ([
            "'translate'",
            "'analyse'",
            "'reply'",
            'crm-inbox-ai-analysis-grid',
            'crm-inbox-ai-translation-text',
        ] as $expected) {
            self::assertStringContainsString(
                $expected,
                $renderer
            );
        }
    }

    public function test_ai_action_supports_translation(): void {
        $action = $this->file(
            'admin/inbox/ai_action.php'
        );

        self::assertStringContainsString(
            "case 'translate':",
            $action
        );
        self::assertStringContainsString(
            '$service->translate(',
            $action
        );
    }

    public function test_local_language_fallback_detects_cyrillic_script(): void {
        $provider = $this->file(
            'classes/crm/inbox/ai/providers/fallback/FallbackInboxAiProvider.php'
        );

        self::assertStringContainsString(
            "'/\\p{Cyrillic}/u'",
            $provider
        );
        self::assertStringContainsString(
            "['language' => 'ru']",
            $provider
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
