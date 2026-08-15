<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_sales_795n3_test extends \advanced_testcase {
    public function test_sales_page_exposes_operational_filters_kpis_and_status_dimensions(): void {
        $page = file_get_contents(__DIR__ . '/../../../admin/commerce/purchases/index.php');
        $this->assertIsString($page);

        foreach ([
            "optional_param('period'",
            "optional_param('commercialstatus'",
            "optional_param('paymentstatus'",
            "optional_param('fulfillmentstatus'",
            "optional_param('provider'",
            "optional_param('currency'",
            'CommerceSalesDashboardRepository',
            'commerce_sales_kpi_invalid_checkouts',
            "technical_status_badge(",
            "'payment',",
            "'fulfillment',",
            'crm-sales-table-card',
        ] as $needle) {
            $this->assertStringContainsString($needle, $page);
        }
    }

    public function test_purchase_repository_reuses_exact_table_semantics_for_sales_kpis(): void {
        $repository = file_get_contents(
            __DIR__ . '/../../../classes/commerce/purchase/readmodel/CommercePurchaseReadRepository.php'
        );
        $this->assertIsString($repository);
        $this->assertStringContainsString('public function summaries_for_metrics', $repository);
        $this->assertStringContainsString('$this->map_summaries_bulk($records)', $repository);
        $this->assertStringContainsString('$summary->commercialstatus === $filter->commercialstatus', $repository);
    }

    public function test_sales_dashboard_uses_successful_payments_for_collected_revenue(): void {
        $repository = file_get_contents(
            __DIR__ . '/../../../classes/crm/commerce/sales/CommerceSalesDashboardRepository.php'
        );
        $this->assertIsString($repository);
        $this->assertStringContainsString(
            "private const SUCCESS_PAYMENT_STATUSES = ['paid', 'succeeded', 'completed', 'captured']",
            $repository
        );
        $this->assertStringContainsString('CommerceCommercialStatus::PAYMENT_FAILED', $repository);
        $this->assertStringContainsString('CommerceCommercialStatus::CANCELLED', $repository);
        $this->assertStringContainsString('CommerceUnfinishedGuestCheckoutCrmService', $repository);
    }

    public function test_sales_nav_visually_marks_workspace_home(): void {
        $renderer = file_get_contents(
            __DIR__ . '/../../../classes/crm/commerce/rendering/CommerceSectionNavigationRenderer.php'
        );
        $styles = file_get_contents(__DIR__ . '/../../../styles.css');
        $this->assertIsString($renderer);
        $this->assertIsString($styles);
        $this->assertStringContainsString('crm-commerce-section-nav-link-overview', $renderer);
        $this->assertStringContainsString(
            '.crm-commerce-section-nav-list-natural > .crm-commerce-section-nav-link-overview::after',
            $styles
        );
    }

    public function test_n32_sales_page_has_compact_tools_sorting_and_contextual_actions(): void {
        $page = file_get_contents(__DIR__ . '/../../../admin/commerce/purchases/index.php');
        $repository = file_get_contents(
            __DIR__ . '/../../../classes/commerce/purchase/readmodel/CommercePurchaseReadRepository.php'
        );
        $this->assertIsString($page);
        $this->assertIsString($repository);

        foreach ([
            'crm-sales-filter-form-compact',
            'commerce_sales_columns',
            'commerce_sales_export',
            'crm-sales-column-picker',
            'crm-sales-sort-link',
            'crm-sales-payment-inline',
            'Provider::icon_url',
            'crm-sales-commercial-summary',
            'crm-sales-row-actions-menu',
            'commerce_purchase_download_invoice',
            'commerce_purchase_open_mail_journal',
        ] as $needle) {
            $this->assertStringContainsString($needle, $page);
        }

        $this->assertStringNotContainsString(
            'commerce_purchase_open_order_details',
            $page
        );
        $this->assertStringContainsString('private function sort_summaries', $repository);
        $this->assertStringContainsString('private function sql_order_by', $repository);
        $this->assertStringContainsString('public function summaries_for_export', $repository);
    }

    public function test_n32_sales_export_endpoint_reuses_filters_and_localised_statuses(): void {
        $export = file_get_contents(__DIR__ . '/../../../admin/commerce/purchases/export.php');
        $this->assertIsString($export);
        $this->assertStringContainsString('new csv_export_writer()', $export);
        $this->assertStringContainsString('summaries_for_export($filter)', $export);
        $this->assertStringContainsString(
            "CommercePurchasePresentation::technical_status_label(",
            $export
        );
        $this->assertStringContainsString(
            'CommercePurchasePresentation::commercial_status_label(',
            $export
        );
    }

    public function test_n32_operational_statuses_have_explicit_translations_in_all_languages(): void {
        $required = [
            'commerce_purchase_payment_status_none',
            'commerce_purchase_payment_status_created',
            'commerce_purchase_payment_status_redirected',
            'commerce_purchase_payment_status_pending',
            'commerce_purchase_payment_status_paid',
            'commerce_purchase_payment_status_succeeded',
            'commerce_purchase_payment_status_completed',
            'commerce_purchase_payment_status_failed',
            'commerce_purchase_payment_status_error',
            'commerce_purchase_payment_status_cancelled',
            'commerce_purchase_payment_status_refunded',
            'commerce_purchase_payment_status_unknown',
            'commerce_purchase_fulfillment_status_none',
            'commerce_purchase_fulfillment_status_planned',
            'commerce_purchase_fulfillment_status_pending',
            'commerce_purchase_fulfillment_status_processing',
            'commerce_purchase_fulfillment_status_fulfilled',
            'commerce_purchase_fulfillment_status_completed',
            'commerce_purchase_fulfillment_status_skipped',
            'commerce_purchase_fulfillment_status_failed',
        ];

        foreach (['en', 'fr', 'ru'] as $language) {
            $catalogue = file_get_contents(
                __DIR__ . '/../../../lang/' . $language . '/local_subscriptions.php'
            );
            $this->assertIsString($catalogue);
            foreach ($required as $key) {
                $this->assertStringContainsString(
                    '$string[\'' . $key . '\']',
                    $catalogue,
                    $language . ' is missing ' . $key
                );
            }
        }
    }

    public function test_n32_subscription_product_type_is_presented_as_course_access(): void {
        $french = file_get_contents(__DIR__ . '/../../../lang/fr/local_subscriptions.php');
        $english = file_get_contents(__DIR__ . '/../../../lang/en/local_subscriptions.php');
        $russian = file_get_contents(__DIR__ . '/../../../lang/ru/local_subscriptions.php');

        $this->assertStringContainsString(
            "\$string['commerce_purchase_type_subscription'] = 'Accès à un cours';",
            $french
        );
        $this->assertStringContainsString(
            "\$string['commerce_purchase_type_subscription'] = 'Course access';",
            $english
        );
        $this->assertStringContainsString(
            "\$string['commerce_purchase_type_subscription'] = 'Доступ к курсу';",
            $russian
        );
    }


    public function test_n33_defaults_hide_technical_columns_and_promote_provider_currency(): void {
        $page = file_get_contents(__DIR__ . '/../../../admin/commerce/purchases/index.php');
        $this->assertIsString($page);

        $this->assertStringContainsString(
            "\$defaultcolumns = [\n    'date', 'reference', 'customer', 'type', 'products', 'amount', 'commercial',",
            $page
        );
        $this->assertStringContainsString(
            "['provider', get_string('commerce_purchase_provider'",
            $page
        );
        $this->assertStringContainsString(
            "['currency', get_string('commerce_sales_currency'",
            $page
        );
        $this->assertStringContainsString(
            "get_string('commerce_purchase_payment_status'",
            $page
        );
        $this->assertStringContainsString(
            "get_string('commerce_purchase_fulfillment_status'",
            $page
        );
        $this->assertStringContainsString(
            "\$advancedopen = \$paymentstatus !== ''",
            $page
        );
    }

    public function test_n33_personal_offer_sales_are_filterable_visible_and_counted(): void {
        $page = file_get_contents(__DIR__ . '/../../../admin/commerce/purchases/index.php');
        $filter = file_get_contents(
            __DIR__ . '/../../../classes/commerce/purchase/readmodel/CommercePurchaseListFilter.php'
        );
        $repository = file_get_contents(
            __DIR__ . '/../../../classes/commerce/purchase/readmodel/CommercePurchaseReadRepository.php'
        );
        $dashboard = file_get_contents(
            __DIR__ . '/../../../classes/crm/commerce/sales/CommerceSalesDashboardRepository.php'
        );

        $this->assertIsString($page);
        $this->assertIsString($filter);
        $this->assertIsString($repository);
        $this->assertIsString($dashboard);

        $this->assertStringContainsString("optional_param('offerorigin'", $page);
        $this->assertStringContainsString('commerce_sales_origin_personal_offer', $page);
        $this->assertStringContainsString('crm-sales-personal-offer-badge', $page);
        $this->assertStringContainsString('crm-sales-row-personal-offer', $page);
        $this->assertStringContainsString('commerce_sales_kpi_personal_offers', $page);
        $this->assertStringContainsString('normalized_offer_origin', $filter);
        $this->assertStringContainsString('"operation":"personaloffer"', $repository);
        $this->assertStringContainsString("'personal_offer_uuid'", $repository);
        $this->assertStringContainsString('$summary->haspersonaloffer', $dashboard);
        $this->assertStringContainsString("'personaloffers' => \$personaloffers", $dashboard);
    }

    public function test_n33_personal_offer_labels_exist_in_all_languages(): void {
        foreach (['en', 'fr', 'ru'] as $language) {
            $catalogue = file_get_contents(
                __DIR__ . '/../../../lang/' . $language . '/local_subscriptions.php'
            );
            $this->assertIsString($catalogue);
            foreach ([
                'commerce_sales_origin',
                'commerce_sales_origin_personal_offer',
                'commerce_sales_origin_standard',
                'commerce_sales_personal_offer_badge',
                'commerce_sales_kpi_personal_offers',
            ] as $key) {
                $this->assertStringContainsString(
                    '$string[\'' . $key . '\']',
                    $catalogue,
                    $language . ' is missing ' . $key
                );
            }
        }
    }


    public function test_n33b_filter_panel_can_be_collapsed_and_reopens_for_active_filters(): void {
        $page = file_get_contents(__DIR__ . '/../../../admin/commerce/purchases/index.php');
        $styles = file_get_contents(__DIR__ . '/../../../styles.css');

        $this->assertIsString($page);
        $this->assertIsString($styles);

        $this->assertStringContainsString('crm-sales-filter-panel', $page);
        $this->assertStringContainsString('crm-sales-filter-panel-summary', $page);
        $this->assertStringContainsString('$filtersareactive', $page);
        $this->assertStringContainsString("'open' => \$filtersareactive ? 'open' : null", $page);
        $this->assertStringContainsString('commerce_sales_filters_toggle', $page);
        $this->assertStringContainsString('.crm-sales-filter-panel[open]', $styles);
    }

}
