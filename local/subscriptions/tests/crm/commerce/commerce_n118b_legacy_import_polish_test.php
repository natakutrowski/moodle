<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n118b_legacy_import_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_import_pages_are_attached_to_offers_and_access(): void {
        foreach ([
            'admin/imports/index.php',
            'admin/imports/process.php',
        ] as $file) {
            $page = $this->file($file);

            self::assertStringContainsString(
                'CommerceSectionNavigationRenderer::OFFERS_ACCESS',
                $page
            );
            self::assertStringContainsString(
                'CommerceOffersAccessNavigationRenderer::OVERVIEW',
                $page
            );
            self::assertStringContainsString(
                'commerce_offers_access_title',
                $page
            );
            self::assertStringNotContainsString(
                'CrmBackLinkRenderer',
                $page
            );
        }
    }

    public function test_offers_access_exposes_legacy_csv_import_entry_point(): void {
        $page = $this->file(
            'admin/commerce/offers-access/index.php'
        );

        self::assertStringContainsString(
            'subscription_config::',
            $page
        );
        self::assertStringContainsString(
            'import_csv_page()',
            $page
        );
        self::assertStringContainsString(
            'crm_legacy_import_entry_action',
            $page
        );
    }

    public function test_import_upload_and_preview_use_dedicated_cards(): void {
        $page = $this->file(
            'admin/imports/index.php'
        );

        self::assertStringContainsString(
            'crm-legacy-import-upload-card',
            $page
        );
        self::assertStringContainsString(
            'crm-legacy-import-preview-card',
            $page
        );
        self::assertStringContainsString(
            'crm_legacy_import_upload_title',
            $page
        );
        self::assertStringContainsString(
            'crm_legacy_import_preview_title',
            $page
        );
    }

    public function test_import_result_has_a_dedicated_summary_card(): void {
        $page = $this->file(
            'admin/imports/process.php'
        );

        self::assertStringContainsString(
            'crm-legacy-import-result-card',
            $page
        );
        self::assertStringContainsString(
            'crm_legacy_import_result_summary_title',
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
