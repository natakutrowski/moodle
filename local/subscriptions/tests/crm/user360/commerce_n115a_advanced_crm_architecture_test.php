<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n115a_advanced_crm_architecture_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_workspace_renders_support_then_advanced_hub(): void {
        $renderer = $this->file(
            'classes/crm/user360/workspace/User360WorkspaceRenderer.php'
        );

        self::assertStringContainsString(
            'User360SupportOverviewRenderer::render(',
            $renderer
        );
        self::assertStringContainsString(
            'User360AdvancedRenderer::render(',
            $renderer
        );
        self::assertStringNotContainsString(
            'crm-user360-n114-advanced-summary',
            $renderer
        );
    }

    public function test_advanced_hub_has_four_subscreens_and_renders_only_active_content(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360AdvancedRenderer.php'
        );

        foreach ([
            "TAB_RELATION = 'relation'",
            "TAB_COMMERCE = 'commerce'",
            "TAB_IDENTITIES = 'identities'",
            "TAB_TIMELINE = 'timeline'",
            "'advancedtab'",
            "array_key_first",
        ] as $expected) {
            self::assertStringContainsString(
                $expected,
                $renderer
            );
        }

        foreach ([
            'User360RelationRenderer::render($profile)',
            'User360CommerceAccessRenderer::render($profile)',
            'User360IdentitiesRenderer::render($profile)',
            'User360TimelineRenderer::render($profile)',
        ] as $expected) {
            self::assertStringContainsString(
                $expected,
                $renderer
            );
        }
    }

    public function test_relation_screen_starts_with_detailed_intelligence(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringContainsString(
            'self::intelligence_dashboard($profile)',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n115b-intelligence-grid',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n115e-main-grid',
            $renderer
        );
    }

    public function test_assistant_workitems_and_customer_success_are_distinct_dashboard_cards(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        foreach ([
            "'assistant'",
            "'work-items'",
            "'customer-success'",
            'crm-user360-n115d-assistant-layout',
        ] as $expected) {
            self::assertStringContainsString(
                $expected,
                $renderer
            );
        }

        self::assertStringNotContainsString(
            "'details'",
            $renderer
        );
    }

    public function test_support_deep_links_target_advanced_subscreens(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        self::assertStringContainsString(
            "advanced_url('commerce')",
            $renderer
        );
        self::assertStringContainsString(
            "advanced_url('timeline')",
            $renderer
        );
        self::assertStringContainsString(
            "advanced_url('relation')",
            $renderer
        );
        self::assertStringContainsString(
            "'crm-user360-advanced'",
            $renderer
        );
    }

    public function test_n115a_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
