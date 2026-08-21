<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n115b_relation_dashboard_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_relation_uses_approved_advanced_dashboard_grid(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringContainsString(
            'crm-user360-n115b-intelligence-grid',
            $renderer
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

    public function test_intelligence_is_split_into_summary_factors_and_signals(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringContainsString(
            'intelligence_dashboard($profile)',
            $renderer
        );
        self::assertStringContainsString(
            'self::explanations(',
            $renderer
        );
        self::assertStringContainsString(
            'self::recommendation_list(',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n115b-score-ring',
            $renderer
        );
    }

    public function test_assistant_is_limited_to_two_recommendations(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );
        $profile = $this->file(
            'classes/output/UserProfileRenderer.php'
        );
        $assistant = $this->file(
            'classes/crm/assistant/rendering/UserAssistantSection.php'
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
            '$profile,' . "\n" . '2',
            preg_replace('/[ \t]+/', '', $renderer)
        );
        self::assertStringContainsString(
            'int $recommendationlimit = 10',
            $profile
        );
        self::assertStringContainsString(
            'int $recommendationlimit = 10',
            $assistant
        );
        self::assertStringContainsString(
            'user_recommendations(',
            $assistant
        );
    }

    public function test_relation_cards_have_distinct_explanatory_identity(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        foreach ([
            "'actions'",
            "'inbox'",
            "'assistant'",
            "'work-items'",
            "'customer-success'",
        ] as $key) {
            self::assertStringContainsString(
                $key,
                $renderer
            );
        }
    }

    public function test_inbox_is_capped_and_recent_notes_are_not_duplicated(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringContainsString(
            'array_slice(',
            $renderer
        );
        self::assertStringContainsString(
            '$inbox->recentthreads',
            $renderer
        );
        self::assertStringNotContainsString(
            'private static function notes(',
            $renderer
        );
    }

    public function test_n115b_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
