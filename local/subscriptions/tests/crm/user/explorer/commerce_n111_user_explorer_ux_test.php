<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Structural regression coverage for Commerce 7.95N11.1 User Explorer UX.
 *
 * @coversNothing
 */
final class commerce_n111_user_explorer_ux_test extends \advanced_testcase {

    private function plugin_file(string $relative): string {
        global $CFG;

        return (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/' . $relative
        );
    }

    public function test_user_explorer_keeps_workspace_actions_together_inside_filter_panel(): void {
        $renderer = $this->plugin_file(
            'classes/crm/user/explorer/UserExplorerRenderer.php'
        );

        self::assertStringContainsString(
            '$utilities = self::render_workspace_toolbar($result);',
            $renderer
        );
        self::assertStringContainsString(
            '$form . $utilities',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user-explorer-filter-utilities',
            $renderer
        );
        self::assertStringContainsString(
            'crm-sales-filter-card crm-sales-filter-card-collapsible',
            $renderer
        );
    }

    public function test_user_explorer_explicitly_breaks_out_of_edly_container_width(): void {
        $css = $this->plugin_file('styles.css');

        self::assertStringContainsString(
            'Commerce 7.95N11.1 — CRM User Explorer workspace refresh',
            $css
        );
        self::assertStringContainsString(
            "body.local-subscriptions-user-explorer-page\n#page.container.bottom-region-main-box",
            $css
        );
        self::assertStringContainsString(
            'max-width: none !important;',
            $css
        );
        self::assertStringContainsString(
            "#region-main-box > .row > [class*=\"col-\"]",
            $css
        );
        self::assertStringContainsString(
            ".crm-user-explorer-workspace-toolbar {\n    display: flex;",
            $css
        );
    }

    public function test_n111_does_not_change_plugin_version(): void {
        $version = $this->plugin_file('version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
