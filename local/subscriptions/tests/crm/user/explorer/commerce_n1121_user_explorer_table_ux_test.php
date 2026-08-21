<?php
declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1121_user_explorer_table_ux_test extends \advanced_testcase {
    private function plugin_file(string $relative): string {
        $path = __DIR__ . '/../../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_kpi_css_is_real_css_not_literal_escaped_newlines(): void {
        $css = $this->plugin_file('styles.css');

        self::assertStringContainsString('.crm-user-explorer-kpi-grid {', $css);
        self::assertStringContainsString('grid-template-columns: repeat(5', $css);
        self::assertStringNotContainsString(
            '\\n\\n/* ============================================================\\n   Commerce 7.95N11.2',
            $css
        );
    }

    public function test_user_table_has_sortable_asc_desc_headers_and_action_menu(): void {
        $renderer = $this->plugin_file(
            'classes/crm/user/explorer/UserExplorerRenderer.php'
        );
        $sort = $this->plugin_file(
            'classes/crm/user/explorer/UserExplorerSort.php'
        );

        self::assertStringContainsString(
            'render_table_header($column, $result->criteria)',
            $renderer
        );
        self::assertStringContainsString('crm-user-explorer-sort-link', $renderer);
        self::assertStringContainsString('fa fa-sort-up', $renderer);
        self::assertStringContainsString('fa fa-sort-down', $renderer);
        self::assertStringContainsString('crm-sales-row-actions-menu', $renderer);
        self::assertStringContainsString('fa fa-eye', $renderer);
        self::assertStringContainsString('fa fa-ellipsis-h', $renderer);

        self::assertStringContainsString("public const SCORE_ASC = 'score_asc';", $sort);
        self::assertStringContainsString("public const SUBSCRIPTIONS_DESC = 'subscriptions_desc';", $sort);
        self::assertStringContainsString("public const PURCHASES_DESC = 'purchases_desc';", $sort);
        self::assertStringContainsString("public const LAST_ACCESS_ASC = 'last_access_asc';", $sort);
    }

    public function test_filter_and_table_use_crm_cards_and_full_width_layout(): void {
        $css = $this->plugin_file('styles.css');

        self::assertStringContainsString('.crm-user-explorer-filters {', $css);
        self::assertStringContainsString('.crm-user-explorer-filter-grid {', $css);
        self::assertStringContainsString('.crm-user-explorer-table-wrapper {', $css);
        self::assertStringContainsString('.crm-user-explorer-actions {', $css);
        self::assertStringContainsString('.crm-user-explorer-pagination {', $css);
    }

    public function test_new_user_explorer_strings_exist_in_all_languages(): void {
        foreach (['en', 'fr', 'ru'] as $lang) {
            $strings = $this->plugin_file(
                'lang/' . $lang . '/local_subscriptions.php'
            );
            self::assertStringContainsString(
                "\$string['crm_user_more_actions']",
                $strings
            );
            self::assertStringContainsString(
                "\$string['crm_user_sort_subscriptions_desc']",
                $strings
            );
            self::assertStringContainsString(
                "\$string['crm_user_sort_purchases_desc']",
                $strings
            );
        }
    }
}
