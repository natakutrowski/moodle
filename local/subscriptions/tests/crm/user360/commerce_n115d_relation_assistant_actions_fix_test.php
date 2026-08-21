<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n115d_relation_assistant_actions_fix_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_assistant_has_explicit_left_and_right_columns(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );
        $assistant = $this->file(
            'classes/crm/assistant/rendering/UserAssistantSection.php'
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
        self::assertStringContainsString(
            'render_recommendations(',
            $assistant
        );
        self::assertStringContainsString(
            'render_conversation(',
            $assistant
        );
    }

    public function test_generic_profile_actions_do_not_include_purchase_resend(): void {
        $builder = $this->file(
            'classes/crm/user/UserProfileActionBuilder.php'
        );
        $commerce = $this->file(
            'classes/output/UserProfileRenderer.php'
        );

        self::assertStringNotContainsString(
            'purchase_resend_',
            $builder
        );
        self::assertStringContainsString(
            'digital_purchase_resend_email_admin_page()',
            $commerce
        );
        self::assertStringContainsString(
            "'sesskey' => sesskey()",
            $commerce
        );
    }

    public function test_reset_password_breadcrumb_uses_existing_language_key(): void {
        $page = $this->file(
            'admin/users/reset_password.php'
        );
        $fr = $this->file(
            'lang/fr/local_subscriptions.php'
        );

        self::assertStringContainsString(
            "'crm_users'",
            $page
        );
        self::assertStringNotContainsString(
            "'admin_users'",
            $page
        );
        self::assertStringContainsString(
            "\$string['crm_users']",
            $fr
        );
    }

    public function test_course_rows_show_latest_activity_without_per_course_xp_badge(): void {
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

    public function test_n115d_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
