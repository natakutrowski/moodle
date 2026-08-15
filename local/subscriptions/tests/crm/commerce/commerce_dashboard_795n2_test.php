<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\crm\commerce\dashboard\CommerceDashboardRepository;
use local_subscriptions\crm\commerce\navigation\CommerceSectionNavigationRegistry;

/** Regression coverage for Commerce 7.95N2 dashboard/navigation UX. */
final class commerce_dashboard_795n2_test extends advanced_testcase {
    public function test_dashboard_repository_is_currency_safe_and_empty_install_is_stable(): void {
        global $DB;
        $this->resetAfterTest(true);

        $snapshot = (new CommerceDashboardRepository($DB))->snapshot(1723651200);

        $this->assertSame([], $snapshot['revenue']);
        $this->assertSame(0, $snapshot['orders']);
        $this->assertSame(0, $snapshot['mailssent']);
        $this->assertSame(0, $snapshot['unfinishedcheckouts']);
        $this->assertSame(0.0, $snapshot['conversion']);
        $this->assertSame(['total' => 0, 'pending' => 0, 'failed' => 0], $snapshot['orderstatus']);
        $this->assertSame(0, $snapshot['activeoffers']);
        $this->assertSame([], $snapshot['latestsales']);
        $this->assertSame([], $snapshot['topproducts']);
    }

    public function test_commerce_secondary_navigation_uses_fontawesome_icons(): void {
        $items = (new CommerceSectionNavigationRegistry())->all_items();
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertStringStartsWith('fa-', $item->icon, $item->key . ' must use a FontAwesome icon.');
        }
    }

    public function test_commerce_index_uses_dashboard_and_breadcrumb(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/index.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('CommerceDashboardRepository', $source);
        $this->assertStringContainsString('CommerceDashboardRenderer', $source);
        $this->assertStringContainsString('CrmBreadcrumbRenderer::render', $source);
        $this->assertStringNotContainsString('CommerceWorkspaceRenderer::render', $source);
    }

    public function test_dashboard_repository_loads_full_user_before_fullname(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/crm/commerce/dashboard/CommerceDashboardRepository.php'
        );
        $this->assertIsString($source);
        $this->assertStringContainsString(
            '$this->db->get_record(\'user\', [\'id\' => (int)$row->userid, \'deleted\' => 0], \'*\')',
            $source
        );
        $this->assertStringContainsString('fullname($user)', $source);
    }



    public function test_renderer_does_not_use_nonexistent_html_writer_section(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/crm/commerce/rendering/CommerceDashboardRenderer.php'
        );
        $this->assertIsString($source);
        $this->assertStringNotContainsString('html_writer::section(', $source);
        $this->assertStringContainsString("html_writer::tag('section',", $source);
    }


    public function test_n23_navigation_prioritises_operations_and_removes_identities(): void {
        $items = (new CommerceSectionNavigationRegistry())->all_items();
        $keys = array_map(static fn($item): string => $item->key, $items);
        $this->assertSame([
            CommerceSectionNavigationRegistry::OVERVIEW,
            CommerceSectionNavigationRegistry::PURCHASES,
            CommerceSectionNavigationRegistry::PRODUCTS,
            CommerceSectionNavigationRegistry::SHOWROOMS,
            CommerceSectionNavigationRegistry::PERSONAL_OFFERS,
            CommerceSectionNavigationRegistry::MAIL,
            CommerceSectionNavigationRegistry::STATISTICS,
            CommerceSectionNavigationRegistry::CONFIGURATION,
        ], $keys);
        $this->assertNotContains(CommerceSectionNavigationRegistry::IDENTITIES, $keys);
        $this->assertNotContains(CommerceSectionNavigationRegistry::GRANTS, $keys);
    }

    public function test_n23_dashboard_supports_selectable_periods(): void {
        global $DB;
        $this->resetAfterTest(true);
        $snapshot = (new CommerceDashboardRepository($DB))->snapshot(1723651200, '7');
        $this->assertSame(7, $snapshot['period']['days']);
        $year = (new CommerceDashboardRepository($DB))->snapshot(1723651200, '365');
        $this->assertSame(365, $year['period']['days']);
        $today = (new CommerceDashboardRepository($DB))->snapshot(1723651200, 'today');
        $this->assertSame('today', $today['period']['mode']);
        $fallback = (new CommerceDashboardRepository($DB))->snapshot(1723651200, '123');
        $this->assertSame(30, $fallback['period']['days']);
    }

    public function test_n24_dashboard_excludes_audit_email_and_supports_digital_download_products(): void {
        $repository = file_get_contents(__DIR__ . '/../../../classes/crm/commerce/dashboard/CommerceDashboardRepository.php');
        $this->assertIsString($repository);
        $this->assertStringContainsString('log@campusfr.fr', $repository);
        $this->assertStringContainsString("'digital_download'", $repository);
        $renderer = file_get_contents(__DIR__ . '/../../../classes/crm/commerce/rendering/CommerceDashboardRenderer.php');
        $this->assertIsString($renderer);
        $this->assertStringContainsString("'🥇', '🥈', '🥉'", $renderer);
        $this->assertStringContainsString('commerceDashGradient', $renderer);
    }

    public function test_n25_dashboard_polish_contract(): void {
        $renderer = file_get_contents(
            __DIR__ . '/../../../classes/crm/commerce/rendering/CommerceDashboardRenderer.php'
        );
        $styles = file_get_contents(__DIR__ . '/../../../styles.css');
        $this->assertIsString($renderer);
        $this->assertIsString($styles);
        $this->assertStringContainsString('commerce_dashboard_sales_short', $renderer);
        $this->assertStringContainsString('money_amount_only', $renderer);
        $this->assertStringContainsString('nice_axis', $renderer);
        $this->assertStringContainsString('crm-commerce-dashboard-chart-axis', $renderer);
        $this->assertStringContainsString("html_writer::tag('title'", $renderer);
        $this->assertStringContainsString('commerce_dashboard_action_merge_accounts', $renderer);
        $this->assertStringContainsString('crm-commerce-dashboard-sale-customer-line', $renderer);
        $this->assertStringContainsString('crm-commerce-section-nav-list-natural', $styles);
    }


    public function test_n26_dashboard_cleanup_contract(): void {
        $repository = file_get_contents(
            __DIR__ . '/../../../classes/crm/commerce/dashboard/CommerceDashboardRepository.php'
        );
        $renderer = file_get_contents(
            __DIR__ . '/../../../classes/crm/commerce/rendering/CommerceDashboardRenderer.php'
        );
        $styles = file_get_contents(__DIR__ . '/../../../styles.css');
        $this->assertIsString($repository);
        $this->assertIsString($renderer);
        $this->assertIsString($styles);
        $this->assertStringContainsString("'showrooms' => \$this->showroom_summary()", $repository);
        $this->assertStringContainsString('private function showroom_summary()', $repository);
        $this->assertStringContainsString('product_showroom_summary', $renderer);
        $this->assertStringContainsString('if ($previous <= 0) { return \'\'; }', $renderer);
        $this->assertStringContainsString('background-image: none !important', $styles);
        $this->assertStringContainsString('.crm-app-navigation-submenu-link', $styles);
    }


    public function test_n27_top_five_and_avatar_contract(): void {
        $repository = file_get_contents(
            __DIR__ . '/../../../classes/crm/commerce/dashboard/CommerceDashboardRepository.php'
        );
        $renderer = file_get_contents(
            __DIR__ . '/../../../classes/crm/commerce/rendering/CommerceDashboardRenderer.php'
        );
        $styles = file_get_contents(__DIR__ . '/../../../styles.css');
        $this->assertIsString($repository);
        $this->assertIsString($renderer);
        $this->assertIsString($styles);
        $this->assertStringContainsString('top_products($start, $end, 5)', $repository);
        $this->assertStringContainsString("'🥇', '🥈', '🥉', '4', '5'", $renderer);
        $this->assertStringContainsString('min-width: 46px !important', $styles);
        $this->assertStringContainsString('.crm-commerce-dashboard-product-row.is-rank-5', $styles);
    }


    public function test_n28_canonical_top_products_contract(): void {
        $repository = file_get_contents(
            __DIR__ . '/../../../classes/crm/commerce/dashboard/CommerceDashboardRepository.php'
        );
        $styles = file_get_contents(__DIR__ . '/../../../styles.css');
        $this->assertIsString($repository);
        $this->assertIsString($styles);
        $this->assertStringContainsString('CommerceStatisticsProductCanonicalizer', $repository);
        $this->assertStringContainsString('pi.metadatajson', $repository);
        $this->assertStringContainsString('pay.status {$statussql}', $repository);
        $this->assertStringContainsString(
            '.crm-commerce-dashboard-sale-avatar .userinitials',
            $styles
        );
    }


    public function test_n28b_dashboard_limits_and_label_aggregation_contract(): void {
        $repository = file_get_contents(
            __DIR__ . '/../../../classes/crm/commerce/dashboard/CommerceDashboardRepository.php'
        );
        $this->assertIsString($repository);
        $this->assertStringContainsString("'latestsales' => $this->latest_sales(4)", $repository);
        $this->assertStringContainsString('$this->top_products($start, $end, 5)', $repository);
        $this->assertStringContainsString("|label:' . $displaykey", $repository);
        $this->assertStringContainsString("_priority", $repository);
    }

}
