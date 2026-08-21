<?php
declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1125_user_explorer_sales_filter_exact_test extends \advanced_testcase {
    private function file(string $relative): string {
        $path = __DIR__ . '/../../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_user_filters_reuse_sales_filter_primitives(): void {
        $renderer = $this->file(
            'classes/crm/user/explorer/UserExplorerRenderer.php'
        );

        foreach ([
            'crm-sales-filter-panel',
            'crm-sales-filter-panel-summary',
            'crm-sales-filter-panel-summary-copy',
            'crm-sales-filter-panel-status',
            'crm-sales-filter-panel-chevron',
            'crm-sales-filter-card crm-sales-filter-card-collapsible',
            'crm-sales-filter-form',
            'crm-sales-filter-grid',
            'crm-sales-filter-field',
            'crm-sales-search-control',
            'crm-sales-search-icon',
            'crm-sales-filter-advanced',
            'crm-sales-filter-advanced-grid',
            'crm-sales-filter-actions',
            'form-label',
            'form-select',
        ] as $class) {
            self::assertStringContainsString($class, $renderer);
        }
    }

    public function test_filter_panel_is_owned_by_render_filters_only(): void {
        $renderer = $this->file(
            'classes/crm/user/explorer/UserExplorerRenderer.php'
        );

        $filters = strpos($renderer, 'private static function render_filters');
        $pills = strpos($renderer, 'private static function render_intelligence_pills');
        self::assertNotFalse($filters);
        self::assertNotFalse($pills);

        $fragment = substr($renderer, $filters, $pills - $filters);
        self::assertStringContainsString(
            'self::render_workspace_toolbar($result)',
            $fragment
        );
        self::assertStringContainsString('crm-sales-filter-panel', $fragment);

        $customer = strpos(
            $renderer,
            'private static function render_customer_success_plans'
        );
        self::assertNotFalse($customer);
        $customerfragment = substr($renderer, $customer, 3500);
        self::assertStringNotContainsString('$panelstatus', $customerfragment);
        self::assertStringNotContainsString(
            'crm-sales-filter-panel',
            $customerfragment
        );
    }

    public function test_n112_kpi_test_matches_current_six_kpi_contract(): void {
        $test = $this->file(
            'tests/crm/user/explorer/commerce_n112_user_explorer_kpi_test.php'
        );

        self::assertStringContainsString(
            'repeat(6, minmax(0, 1fr))',
            $test
        );
        self::assertStringNotContainsString(
            'UserExplorerFilter::COLD_USER',
            $test
        );
        self::assertStringContainsString("'suspended'", $test);
        self::assertStringContainsString("'no_moodle'", $test);
    }
}
