<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n117b_identity_merge_layout_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_identity_panel_uses_two_semantic_columns(): void {
        $renderer = $this->file(
            'classes/crm/user360/identity/User360IdentityGraphRenderer.php'
        );

        self::assertStringContainsString(
            'crm-user360-identity-grid',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-identity-column-main',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-identity-column-potential',
            $renderer
        );
    }

    public function test_potential_account_exposes_merge_shortcut(): void {
        $renderer = $this->file(
            'classes/crm/user360/identity/User360IdentityGraphRenderer.php'
        );

        self::assertStringContainsString(
            "'/local/subscriptions/admin/users/merge.php'",
            $renderer
        );
        self::assertStringContainsString(
            'crm_user360_n117b_merge',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-identity-merge-link',
            $renderer
        );
    }

    public function test_n117b_strings_exist_in_all_languages(): void {
        foreach (['en', 'fr', 'ru'] as $lang) {
            $strings = $this->file(
                'lang/' . $lang . '/local_subscriptions.php'
            );

            foreach ([
                'crm_user360_n117b_merge',
                'crm_user360_n117b_potential_column_title',
            ] as $key) {
                self::assertStringContainsString(
                    '$string[\'' . $key . '\']',
                    $strings
                );
            }
        }
    }
}
