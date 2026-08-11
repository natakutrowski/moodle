<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

use html_table;
use html_writer;
use local_subscriptions\commerce\catalog\navigation\CommerceCatalogLinkGenerator;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\statistics\CommerceProductStatisticsRow;

/** Renders currency-safe product performance tables. */
final class CommerceProductStatisticsRenderer {
    /** @param CommerceProductStatisticsRow[] $rows */
    public static function render(array $rows): string {
        global $DB;

        $catalogue = new CommerceCatalogReadRepository($DB);
        if ($rows === []) {
            return html_writer::div(
                get_string('commerce_statistics_products_empty', 'local_subscriptions'),
                'alert alert-info'
            );
        }

        $bycurrency = [];
        foreach ($rows as $row) {
            $bycurrency[$row->currency][] = $row;
        }
        ksort($bycurrency);

        $html = '';
        foreach ($bycurrency as $currency => $currencyrows) {
            $table = new html_table();
            $table->attributes = [
                'class' => 'generaltable table table-hover align-middle',
                'aria-label' => get_string('commerce_statistics_products_table_label', 'local_subscriptions', $currency),
            ];
            $table->head = [
                get_string('commerce_statistics_product', 'local_subscriptions'),
                get_string('commerce_purchase_type', 'local_subscriptions'),
                get_string('commerce_statistics_product_orders', 'local_subscriptions'),
                get_string('commerce_statistics_product_paid_orders', 'local_subscriptions'),
                get_string('commerce_statistics_product_free_orders', 'local_subscriptions'),
                get_string('commerce_statistics_product_quantity', 'local_subscriptions'),
                get_string('commerce_statistics_product_revenue', 'local_subscriptions'),
            ];

            foreach ($currencyrows as $row) {
                $details = $catalogue->find_by_purchase_reference($row->reference);
                $productlabel = format_string($row->label);
                if ($details !== null) {
                    $productlabel = html_writer::link(
                        CommerceCatalogLinkGenerator::view_url($details->get_summary()),
                        $productlabel,
                        ['class' => 'fw-semibold']
                    );
                } else {
                    $productlabel = html_writer::span($productlabel, 'fw-semibold');
                }
                $table->data[] = [
                    $productlabel . html_writer::div(s($row->reference), 'small text-muted font-monospace'),
                    self::sale_type($row->itemtype),
                    $row->orders,
                    $row->paidorders,
                    $row->freeorders,
                    $row->quantity,
                    self::money($row->revenueminor, $currency),
                ];
            }

            $html .= html_writer::tag('section',
                html_writer::tag('h3', s($currency), ['class' => 'h4 mt-4 mb-3'])
                    . html_writer::table($table),
                ['class' => 'commerce-product-statistics-currency mb-5']
            );
        }

        return $html;
    }


    private static function sale_type(string $type): string {
        $normalized = strtolower(trim($type));
        $keys = [
            'subscription' => 'commerce_purchase_type_subscription',
            'course_access' => 'commerce_purchase_type_course_access',
            'digital' => 'commerce_purchase_type_digital',
            'digital_download' => 'commerce_purchase_type_digital_download',
            'bundle' => 'commerce_purchase_type_bundle',
        ];

        if (isset($keys[$normalized])) {
            return get_string($keys[$normalized], 'local_subscriptions');
        }

        return s($type);
    }

    private static function money(int $minor, string $currency): string {
        $major = $minor / 100;
        if (class_exists('NumberFormatter')) {
            $formatter = new \NumberFormatter(current_language(), \NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency($major, $currency);
            if ($formatted !== false) {
                return $formatted;
            }
        }
        return format_float($major, 2) . ' ' . $currency;
    }
}
