<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n113c_user360_relation_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_factory_registers_one_consolidated_relation_surface(): void {
        $factory = $this->file(
            'classes/crm/user360/workspace/User360WorkspaceFactory.php'
        );

        $create = substr(
            $factory,
            strpos($factory, 'public static function create('),
            6500
        );

        self::assertStringContainsString(
            'self::register_relation(',
            $create
        );
        self::assertStringNotContainsString(
            'self::register_customer_success(',
            $create
        );
        self::assertStringNotContainsString(
            'self::register_notes(',
            $create
        );
        self::assertStringNotContainsString(
            'self::register_assistant(',
            $create
        );
        self::assertStringNotContainsString(
            'self::register_work_items(',
            $create
        );
    }

    public function test_relation_renderer_owns_relation_anchor_and_domains(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringContainsString(
            "'id' => 'user360-relation'",
            $renderer
        );
        self::assertStringContainsString(
            'self::intelligence_dashboard($profile)',
            $renderer
        );

        foreach ([
            'self::actions($profile)',
            'self::inbox($profile)',
            'self::assistant($profile)',
            'self::work_items($profile)',
            'self::customer_success($profile)',
        ] as $method) {
            self::assertStringContainsString(
                $method,
                $renderer
            );
        }
    }

    public function test_relation_actions_preserve_note_composer_and_existing_actions(): void {
        $profile = $this->file(
            'classes/output/UserProfileRenderer.php'
        );

        self::assertStringContainsString(
            'public static function render_relation_actions_content',
            $profile
        );
        self::assertStringContainsString(
            'return self::quick_actions($profile);',
            $profile
        );
    }

    public function test_overview_priority_actions_translate_recommendation_keys(): void {
        $overview = $this->file(
            'classes/crm/user360/rendering/User360OverviewRenderer.php'
        );

        self::assertStringContainsString(
            "'crm_intelligence_recommendation_'",
            $overview
        );
        self::assertStringContainsString(
            'get_string_manager()->string_exists',
            $overview
        );
    }

    public function test_relation_strings_exist_in_all_languages(): void {
        foreach (['en', 'fr', 'ru'] as $lang) {
            $strings = $this->file(
                'lang/' . $lang . '/local_subscriptions.php'
            );

            foreach ([
                'crm_user360_n113c_title',
                'crm_user360_n113c_intelligence',
                'crm_user360_n113c_actions',
                'crm_user360_n113c_notes',
                'crm_user360_n113c_inbox',
                'crm_user360_n113c_assistant',
                'crm_user360_n113c_work_items',
            ] as $key) {
                self::assertStringContainsString(
                    '$string[\'' . $key . '\']',
                    $strings
                );
            }
        }
    }

    public function test_n113c_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
