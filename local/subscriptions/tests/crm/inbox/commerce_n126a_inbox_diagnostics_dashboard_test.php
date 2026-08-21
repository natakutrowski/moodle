<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n126a_inbox_diagnostics_dashboard_test extends \advanced_testcase {

    public function test_diagnostics_page_uses_dashboard_renderer(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../admin/inbox/diagnostics.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'InboxDiagnosticsRenderer::render(',
            $source
        );
        $this->assertStringNotContainsString(
            "'table table-striped'",
            $source
        );
    }

    public function test_dashboard_exposes_human_readable_sections(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/crm/inbox/rendering/InboxDiagnosticsRenderer.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString('status_banner(', $source);
        $this->assertStringContainsString('health_card(', $source);
        $this->assertStringContainsString('technical_checks(', $source);
        $this->assertStringContainsString("'imap_connection'", $source);
        $this->assertStringContainsString("'smtp_connection'", $source);
    }
}
