<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n116a1_runtime_and_title_fix_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_advanced_commerce_uses_existing_support_subs_presenter(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );
        $presenter = $this->file(
            'classes/support/SubsPresenter.php'
        );

        self::assertStringContainsString(
            'use local_subscriptions\\support\\SubsPresenter;',
            $renderer
        );
        self::assertStringContainsString(
            'final class SubsPresenter',
            $presenter
        );
        self::assertStringContainsString(
            'SubsPresenter::render_status_badge(',
            $renderer
        );
    }

    public function test_active_user360_page_title_is_customer_name_and_email(): void {
        $page = $this->file('admin/users/view.php');

        self::assertStringContainsString(
            '$displayname = User360OverviewRenderer::display_name($profile);',
            $page
        );
        self::assertStringContainsString(
            '$pagetitle = $displayname;',
            $page
        );
        self::assertStringContainsString(
            '$pagetitle .= \' (\' . $profileemail . \')\';',
            $page
        );
        self::assertStringContainsString(
            'CrmPageHeader::render(',
            $page
        );

        self::assertMatchesRegularExpression(
            '/CrmPageHeader::render\(\s*\$pagetitle,/',
            $page
        );
    }

    public function test_historical_profile_keeps_historical_title(): void {
        $page = $this->file('admin/users/view.php');

        self::assertStringContainsString(
            "'crm_user_history_title'",
            $page
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
