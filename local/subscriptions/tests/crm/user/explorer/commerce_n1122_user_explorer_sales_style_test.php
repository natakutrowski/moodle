<?php
declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1122_user_explorer_sales_style_test extends \advanced_testcase {
    private function plugin_file(string $relative): string {
        $path = __DIR__ . '/../../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_user_explorer_reuses_sales_filter_panel_and_context_menu(): void {
        $renderer = $this->plugin_file(
            'classes/crm/user/explorer/UserExplorerRenderer.php'
        );
        $index = $this->plugin_file('admin/users/index.php');

        self::assertStringContainsString('crm-sales-filter-panel', $renderer);
        self::assertStringContainsString('crm-sales-filter-panel-summary', $renderer);
        self::assertStringContainsString('crm-sales-filter-card-collapsible', $renderer);
        self::assertStringContainsString('crm-sales-search-control', $renderer);
        self::assertStringContainsString('crm-sales-filter-advanced', $renderer);
        self::assertStringContainsString('crm-sales-filter-advanced-grid', $renderer);
        self::assertStringContainsString('form-select', $renderer);
        self::assertStringContainsString('crm-sales-row-actions-menu', $renderer);
        self::assertStringContainsString('crm-sales-row-menu-section', $renderer);
        self::assertStringContainsString('crm-sales-row-menu-link', $renderer);
        self::assertStringContainsString(
            "'local_subscriptions/crm_context_menus'",
            $index
        );
    }

    public function test_top_result_summary_is_removed_and_utilities_are_inside_filter_panel(): void {
        $renderer = $this->plugin_file(
            'classes/crm/user/explorer/UserExplorerRenderer.php'
        );

        self::assertStringNotContainsString(
            '$out .= self::render_summary($result);',
            $renderer
        );
        self::assertStringNotContainsString(
            '$out .= self::render_workspace_toolbar($result);',
            $renderer
        );
        self::assertStringContainsString(
            '$utilities = self::render_workspace_toolbar($result);',
            $renderer
        );
        self::assertStringContainsString('$form . $utilities', $renderer);
    }

    public function test_kpi_strip_has_six_desktop_cards_with_suspended_and_no_moodle(): void {
        $renderer = $this->plugin_file(
            'classes/crm/user/explorer/UserExplorerRenderer.php'
        );
        $service = $this->plugin_file(
            'classes/crm/user/explorer/UserExplorerService.php'
        );
        $css = $this->plugin_file('styles.css');

        self::assertStringContainsString("'key' => 'suspended'", $renderer);
        self::assertStringContainsString("'key' => 'no_moodle'", $renderer);
        self::assertStringContainsString(
            "\$counts['suspended'] = \$this->repository->count(\$suspended);",
            $service
        );
        self::assertStringContainsString(
            "\$counts['no_moodle'] = \$this->legacyguests->count(\$nomoodle);",
            $service
        );
        self::assertStringContainsString(
            'grid-template-columns: repeat(6, minmax(0, 1fr));',
            $css
        );
    }

    public function test_intelligence_pills_define_criteria_from_result(): void {
        $renderer = $this->plugin_file(
            'classes/crm/user/explorer/UserExplorerRenderer.php'
        );

        $methodpos = strpos(
            $renderer,
            'private static function render_intelligence_pills'
        );
        self::assertNotFalse($methodpos);

        $fragment = substr($renderer, $methodpos, 1200);
        self::assertStringContainsString(
            '$criteria = $result->criteria;',
            $fragment
        );
    }

    public function test_saved_view_has_explanatory_help_in_all_languages(): void {
        foreach (['en', 'fr', 'ru'] as $lang) {
            $strings = $this->plugin_file(
                'lang/' . $lang . '/local_subscriptions.php'
            );
            self::assertStringContainsString(
                "\$string['crm_user_save_view_help']",
                $strings
            );
        }
    }
}
