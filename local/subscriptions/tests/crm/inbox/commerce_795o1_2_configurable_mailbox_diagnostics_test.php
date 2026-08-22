<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o1_2_configurable_mailbox_diagnostics_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_account_service_supports_configurable_ovh_mailbox(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxAccountService.php'
        );

        self::assertStringContainsString(
            'public function configure_ovh_account(',
            $service
        );
        self::assertStringContainsString(
            '$credentialkey,',
            $service
        );
    }

    public function test_diagnostics_selects_enabled_account_instead_of_hardcoded_support(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxDiagnosticsService.php'
        );

        self::assertStringContainsString(
            '$this->accounts->get_enabled()',
            $service
        );
        self::assertStringNotContainsString(
            "find_by_email(\n                'support@campusfr.fr'",
            $service
        );
    }

    public function test_configuration_cli_accepts_mailbox_parameters(): void {
        $cli = $this->file(
            'cli/crm/inbox/configure_crm_inbox_account.php'
        );

        self::assertStringContainsString(
            "'email' => 'support@campusfr.fr'",
            $cli
        );
        self::assertStringContainsString(
            "'credentialkey' => 'support_ovh'",
            $cli
        );
        self::assertStringContainsString(
            '--email=noreply@campusfr.fr',
            $cli
        );
    }

    public function test_polished_diagnostics_strings_exist_in_all_languages(): void {
        foreach (['en', 'fr', 'ru'] as $language) {
            $lang = $this->file(
                'lang/' . $language . '/local_subscriptions.php'
            );

            foreach ([
                'crm_inbox_diagnostics_operational',
                'crm_inbox_diagnostics_attention',
                'crm_inbox_diagnostics_account_panel',
                'crm_inbox_diagnostics_technical',
            ] as $key) {
                self::assertStringContainsString(
                    '$string[\'' . $key . '\']',
                    $lang
                );
            }
        }
    }
}
