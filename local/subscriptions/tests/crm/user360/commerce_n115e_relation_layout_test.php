<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n115e_relation_layout_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_relation_uses_side_column_and_assistant_area(): void {
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

        self::assertMatchesRegularExpression(
            '/self::actions\(\$profile\)\s*'
            . '\.\s*self::inbox\(\$profile\)\s*'
            . '\.\s*self::work_items\(\$profile\)\s*'
            . '\.\s*self::customer_success\(\$profile\)/',
            $renderer
        );
    }

    public function test_assistant_keeps_separate_recommendation_and_question_content(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringContainsString(
            'crm-user360-n115d-assistant-layout',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n115d-assistant-recommendations',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n115d-assistant-question',
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
