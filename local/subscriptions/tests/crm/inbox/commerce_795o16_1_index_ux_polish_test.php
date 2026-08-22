<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o16_1_index_ux_polish_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_header_no_longer_duplicates_refresh_and_diagnostics(): void {
        $index = $this->file(
            'admin/inbox/index.php'
        );

        self::assertStringContainsString(
            "$" . "headeractions = '';",
            $index
        );

        self::assertStringNotContainsString(
            'crm-inbox-refresh-button',
            $index
        );
    }

    public function test_refresh_is_rendered_beside_mail_folders(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        self::assertStringContainsString(
            'crm-inbox-folder-refresh',
            $renderer
        );

        self::assertStringContainsString(
            'admin_inbox_sync_page',
            $renderer
        );
    }

    public function test_filters_are_collapsible_and_auto_open_when_active(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        self::assertStringContainsString(
            "'details'",
            $renderer
        );

        self::assertStringContainsString(
            "$" . "filtercount > 0",
            $renderer
        );

        self::assertStringContainsString(
            "$" . "detailsattributes['open'] = 'open';",
            $renderer
        );
    }

    public function test_workspace_uses_more_viewport_and_unread_rail_is_restored(): void {
        $css = $this->file(
            'styles.css'
        );

        self::assertStringContainsString(
            'calc(100vh - 18.5rem)',
            $css
        );

        self::assertStringContainsString(
            '.crm-inbox-thread-card.crm-inbox-thread-card-unread',
            $css
        );

        self::assertStringContainsString(
            'border-left: 4px solid #6c63dd !important',
            $css
        );
    }

    public function test_bulk_toolbar_is_constrained_inside_list_column(): void {
        $css = $this->file(
            'styles.css'
        );

        self::assertStringContainsString(
            'minmax(8.5rem, 1fr)',
            $css
        );

        self::assertStringContainsString(
            'max-width: 4.8rem',
            $css
        );
    }
}
