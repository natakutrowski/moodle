<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n113d_user360_identities_timeline_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_factory_registers_one_identities_domain(): void {
        $factory = $this->file(
            'classes/crm/user360/workspace/User360WorkspaceFactory.php'
        );

        $create = substr(
            $factory,
            strpos($factory, 'public static function create('),
            5000
        );

        self::assertStringContainsString(
            'self::register_identities(',
            $create
        );
        self::assertStringNotContainsString(
            'self::register_identity_graph(',
            $create
        );
        self::assertStringNotContainsString(
            'self::register_guest_checkout_recovery(',
            $create
        );
        self::assertStringNotContainsString(
            'self::register_merge_history(',
            $create
        );
    }

    public function test_identity_renderer_supports_moodle_and_commerce_only_profiles(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360IdentitiesRenderer.php'
        );

        self::assertStringContainsString(
            "'id' => 'user360-identities'",
            $renderer
        );
        self::assertStringContainsString(
            'User360IdentityGraphRenderer::render(',
            $renderer
        );
        self::assertStringContainsString(
            'User360IdentityGraphRenderer::render_email(',
            $renderer
        );
        self::assertStringContainsString(
            'User360GuestCheckoutRecoveryRenderer::',
            $renderer
        );
        self::assertStringContainsString(
            'User360MergeHistoryRenderer::render(',
            $renderer
        );
    }

    public function test_timeline_uses_full_width_domain_renderer(): void {
        $factory = $this->file(
            'classes/crm/user360/workspace/User360WorkspaceFactory.php'
        );
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360TimelineRenderer.php'
        );

        self::assertStringContainsString(
            'User360TimelineRenderer::render(',
            $factory
        );
        self::assertStringContainsString(
            "'id' => 'crm-user-timeline'",
            $renderer
        );
        self::assertStringContainsString(
            'UserProfileRenderer::render_timeline_content($profile)',
            $renderer
        );
        self::assertStringNotContainsString(
            'UserProfileRenderer::render_timeline_panel($profile)',
            $renderer
        );
    }

    public function test_n113a_commerce_anchor_assertion_is_layout_independent(): void {
        $test = $this->file(
            'tests/crm/user360/commerce_n113a_user360_overview_test.php'
        );

        self::assertStringContainsString(
            "\"'id' => 'user360-commerce'\"",
            $test
        );
        self::assertStringNotContainsString(
            "\"['id' => 'user360-commerce'\"",
            $test
        );
    }

    public function test_n113d_strings_exist_in_all_languages(): void {
        foreach (['en', 'fr', 'ru'] as $lang) {
            $strings = $this->file(
                'lang/' . $lang . '/local_subscriptions.php'
            );

            foreach ([
                'crm_user360_n113d_identities_title',
                'crm_user360_n113d_known_identities',
                'crm_user360_n113d_account_linking',
                'crm_user360_n113d_timeline_title',
            ] as $key) {
                self::assertStringContainsString(
                    '$string[\'' . $key . '\']',
                    $strings
                );
            }
        }
    }

    public function test_n113d_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
