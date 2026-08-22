<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Regression coverage for Commerce 7.95O16.3.6 diagnostics UX polish.
 *
 * @coversNothing
 */
final class commerce_795o16_3_6_diagnostics_ux_test extends \advanced_testcase {

    public function test_diagnostics_page_has_no_redundant_back_link(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/inbox/diagnostics.php');

        $this->assertIsString($source);
        $this->assertStringNotContainsString('CrmBackLinkRenderer::render', $source);
        $this->assertStringNotContainsString('use local_subscriptions\\crm\\navigation\\CrmBackLinkRenderer;', $source);
    }

    public function test_static_technical_check_messages_are_localised(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/crm/inbox/services/InboxDiagnosticsService.php');

        $this->assertIsString($source);
        $this->assertStringNotContainsString("'PHP IMAP enabled'", $source);
        $this->assertStringNotContainsString("'Account enabled'", $source);
        $this->assertStringNotContainsString("'Credentials available'", $source);
        $this->assertStringNotContainsString("'IMAP connection successful'", $source);
        $this->assertStringNotContainsString("'SMTP connection successful'", $source);
        $this->assertStringContainsString('crm_inbox_diagnostics_check_table_available', $source);
        $this->assertStringContainsString('crm_inbox_diagnostics_check_imap_connection_ok', $source);
        $this->assertStringContainsString('crm_inbox_diagnostics_check_smtp_connection_ok', $source);
    }

    public function test_diagnostics_summary_has_explicit_spacing(): void {
        $css = file_get_contents(__DIR__ . '/../../../styles.css');

        $this->assertIsString($css);
        $this->assertStringContainsString('.crm-inbox-diagnostics-check-summary', $css);
        $this->assertStringContainsString('margin-left: .5rem;', $css);
    }

    public function test_diagnostics_check_strings_exist_in_all_languages(): void {
        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(__DIR__ . '/../../../lang/' . $lang . '/local_subscriptions.php');
            $this->assertIsString($source);
            $this->assertStringContainsString("$" . "string['crm_inbox_diagnostics_check_imap_extension_ok']", $source);
            $this->assertStringContainsString("$" . "string['crm_inbox_diagnostics_check_table_available']", $source);
            $this->assertStringContainsString("$" . "string['crm_inbox_diagnostics_check_account_enabled']", $source);
            $this->assertStringContainsString("$" . "string['crm_inbox_diagnostics_check_credentials_available']", $source);
            $this->assertStringContainsString("$" . "string['crm_inbox_diagnostics_check_imap_connection_ok']", $source);
            $this->assertStringContainsString("$" . "string['crm_inbox_diagnostics_check_smtp_connection_ok']", $source);
        }
    }
}
