<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n119e_provisioning_legacy_quality_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_provisioning_screen_has_readable_candidate_actions(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/provisioning.php'
        );

        foreach ([
            'crm_identity_provisioning_diagnostic',
            'crm_identity_provisioning_recommendation',
            'crm-identity-provisioning-candidate',
            'crm-identity-provisioning-table-actions',
            "'/local/subscriptions/admin/users/view.php'",
        ] as $expected) {
            self::assertStringContainsString(
                $expected,
                $page
            );
        }
    }

    public function test_legacy_quality_links_moodle_clients_and_prioritises_corrections(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/legacy-quality.php'
        );

        foreach ([
            'crm_identity_legacy_quality_results_title',
            'crm_identity_legacy_quality_history',
            'crm_identity_legacy_quality_correct',
            "'/local/subscriptions/admin/users/view.php'",
            'crm-identity-legacy-quality-row-invalid',
            'crm-identity-legacy-quality-row-suspect',
        ] as $expected) {
            self::assertStringContainsString(
                $expected,
                $page
            );
        }
    }

    public function test_plugin_version_is_unchanged(): void {
        $version = $this->file('version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
