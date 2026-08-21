<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n113a_user360_overview_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_guest_profile_never_calls_fullname_directly_from_view(): void {
        $view = $this->file('admin/users/view.php');

        self::assertStringNotContainsString(
            'trim(fullname($profile->user))',
            $view
        );
        self::assertStringContainsString(
            'User360OverviewRenderer::display_name($profile)',
            $view
        );
    }

    public function test_safe_display_name_handles_commerce_identity_without_moodle_name_fields(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360OverviewRenderer.php'
        );

        self::assertStringContainsString(
            'public static function display_name',
            $renderer
        );
        self::assertStringContainsString(
            "if (empty(\$profile->iscommerceguest))",
            $renderer
        );
        self::assertStringContainsString(
            '$user->{$field} = \'\';',
            $renderer
        );
        self::assertStringContainsString(
            "\$user->firstname ?? ''",
            $renderer
        );
        self::assertStringContainsString(
            "\$user->lastname ?? ''",
            $renderer
        );
    }

    public function test_user360_has_new_hero_kpis_navigation_and_overview(): void {
        $workspace = $this->file(
            'classes/crm/user360/workspace/User360WorkspaceRenderer.php'
        );
        $factory = $this->file(
            'classes/crm/user360/workspace/User360WorkspaceFactory.php'
        );

        self::assertStringContainsString(
            'User360SupportOverviewRenderer::render(',
            $workspace
        );
        self::assertStringContainsString(
            'User360AdvancedRenderer::render(',
            $workspace
        );
        self::assertStringContainsString(
            'User360SupportOverviewRenderer::render_hero',
            $factory
        );
        self::assertStringContainsString(
            'User360SupportOverviewRenderer::render_kpis',
            $factory
        );
    }

    public function test_old_summary_quick_actions_are_not_rendered_above_new_overview(): void {
        $factory = $this->file(
            'classes/crm/user360/workspace/User360WorkspaceFactory.php'
        );

        $createpos = strpos($factory, 'public static function create(');
        self::assertNotFalse($createpos);
        $createfragment = substr($factory, $createpos, 5000);

        self::assertStringNotContainsString(
            'self::register_timeline_summary(',
            $createfragment
        );
        self::assertStringNotContainsString(
            'self::register_quick_actions(',
            $createfragment
        );
    }

    public function test_navigation_targets_existing_detailed_sections(): void {
        $overview = $this->file(
            'classes/crm/user360/rendering/User360OverviewRenderer.php'
        );
        $factory = $this->file(
            'classes/crm/user360/workspace/User360WorkspaceFactory.php'
        );

        foreach ([
            'user360-overview',
            'user360-commerce',
            'user360-identities',
            'crm-user-timeline',
        ] as $target) {
            self::assertStringContainsString($target, $overview);
        }

        $commerce = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );
        self::assertStringContainsString(
            "'id' => 'user360-commerce'",
            $commerce
        );
        self::assertStringContainsString(
            "['id' => 'user360-identities']",
            $factory
        );
    }

    public function test_n113a_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
