<?php
declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1126_user_explorer_sales_table_test extends \advanced_testcase {
    private function file(string $relative): string {
        $path = __DIR__ . '/../../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_user_table_reuses_sales_table_card_and_toolbar(): void {
        $renderer = $this->file(
            'classes/crm/user/explorer/UserExplorerRenderer.php'
        );

        foreach ([
            'crm-sales-table-card crm-user-explorer-table-card',
            'crm-sales-table-toolbar crm-user-explorer-table-toolbar',
            'crm-sales-table-count',
            'crm-sales-table-scroll',
            'generaltable crm-sales-table crm-user-explorer-table',
            'crm-sales-perpage-select',
        ] as $primitive) {
            self::assertStringContainsString($primitive, $renderer);
        }
    }

    public function test_result_toolbar_contains_count_filter_pills_and_perpage(): void {
        $renderer = $this->file(
            'classes/crm/user/explorer/UserExplorerRenderer.php'
        );

        self::assertStringContainsString(
            'crm_user_explorer_found',
            $renderer
        );
        self::assertStringContainsString(
            'render_active_filter_pills($result)',
            $renderer
        );
        self::assertStringContainsString(
            'render_perpage_selector($result)',
            $renderer
        );
        self::assertSame(
            1,
            substr_count($renderer, "'crm_user_per_page'")
        );
    }

    public function test_filter_toolbar_buttons_have_icons(): void {
        $renderer = $this->file(
            'classes/crm/user/explorer/UserExplorerRenderer.php'
        );

        self::assertStringContainsString('fa fa-download', $renderer);
        self::assertStringContainsString('fa fa-bookmark-o', $renderer);
        self::assertStringContainsString('fa fa-columns', $renderer);
        self::assertStringContainsString(
            'crm-user-utility-button',
            $renderer
        );
    }

    public function test_context_menu_is_left_aligned_by_scoped_css(): void {
        $styles = $this->file('styles.css');

        self::assertStringContainsString(
            '.crm-user-explorer-actions .crm-sales-row-menu',
            $styles
        );
        self::assertStringContainsString('text-align: left;', $styles);
        self::assertStringContainsString(
            'justify-content: flex-start;',
            $styles
        );
    }

    public function test_legacy_toolbar_contract_stays_explicit(): void {
        $renderer = $this->file(
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
    }
}
