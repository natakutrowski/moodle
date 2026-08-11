<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\statistics\CommerceStatisticsSeries;

/** Renders accessible Moodle core charts with textual fallbacks. */
final class CommerceStatisticsChartRenderer {
    /** @param array<string,CommerceStatisticsSeries> $revenue @param array<string,CommerceStatisticsSeries> $orders */
    public static function dashboard(\renderer_base $output, array $revenue, array $orders, array $health, array $products, bool $cumulative = false): string {
        $currencies = array_values(array_unique(array_merge(array_keys($revenue), array_keys($orders), array_keys($health), array_keys($products))));
        sort($currencies);
        $html = '';
        foreach ($currencies as $currency) {
            $charts = '';
            if (isset($revenue[$currency])) {
                $revenueseries = $cumulative ? self::cumulative($revenue[$currency]) : $revenue[$currency];
                $revenuetitle = get_string($cumulative ? 'commerce_statistics_chart_revenue_cumulative' : 'commerce_statistics_chart_revenue', 'local_subscriptions');
                $charts .= self::line($output, $revenueseries, $revenuetitle, $currency, true);
            }
            if (isset($orders[$currency])) { $charts .= self::bar($output, $orders[$currency], get_string('commerce_statistics_chart_orders', 'local_subscriptions'), null, false); }
            if (isset($products[$currency])) { $charts .= self::products($output, $products[$currency], $currency); }
            if (isset($health[$currency])) { $charts .= self::health($output, $health[$currency]); }
            if ($charts !== '') {
                $html .= html_writer::tag('section', html_writer::tag('h2', s($currency), ['class' => 'h4 mb-3']) . html_writer::div($charts, 'crm-commerce-statistics-chart-grid'), ['class' => 'crm-commerce-statistics-charts mb-5']);
            }
        }
        return $html;
    }

    public static function product(
        \renderer_base $output,
        array $revenuebycurrency,
        array $ordersbycurrency = [],
        bool $cumulative = false
    ): string {
        $html = '';
        $currencies = array_values(array_unique(array_merge(array_keys($revenuebycurrency), array_keys($ordersbycurrency))));
        sort($currencies);
        foreach ($currencies as $currency) {
            if (isset($revenuebycurrency[$currency])) {
                $series = $cumulative ? self::cumulative($revenuebycurrency[$currency]) : $revenuebycurrency[$currency];
                $title = get_string(
                    $cumulative ? 'commerce_statistics_chart_product_revenue_cumulative' : 'commerce_statistics_chart_product_revenue',
                    'local_subscriptions'
                );
                $html .= self::line($output, $series, $title, $currency, true);
            }
            if (isset($ordersbycurrency[$currency])) {
                $html .= self::bar(
                    $output,
                    $ordersbycurrency[$currency],
                    get_string('commerce_statistics_chart_product_orders', 'local_subscriptions'),
                    null,
                    false
                );
            }
        }
        return html_writer::div($html, 'crm-commerce-statistics-chart-grid');
    }

    private static function cumulative(CommerceStatisticsSeries $series): CommerceStatisticsSeries {
        $total = 0;
        $points = [];
        foreach ($series->points() as $point) {
            $total += (int)$point['value'];
            $point['value'] = $total;
            $points[] = $point;
        }
        return new CommerceStatisticsSeries(
            $series->key() . '_cumulative',
            $series->currency(),
            $series->granularity(),
            $points
        );
    }

    private static function line(\renderer_base $output, CommerceStatisticsSeries $series, string $title, ?string $currency, bool $money): string {
        $chart = new \core\chart_line();
        $chart->set_title($title);
        $chart->set_labels($series->labels());
        $values = $money ? array_map(static fn($v) => ((float)$v) / 100, $series->values()) : $series->values();
        $chart->add_series(new \core\chart_series($currency ?? $title, $values));
        return self::wrap($output->render($chart), $title, self::table($series->labels(), $values, $currency, $title));
    }

    private static function bar(\renderer_base $output, CommerceStatisticsSeries $series, string $title, ?string $currency, bool $money): string {
        $chart = new \core\chart_bar();
        $chart->set_title($title);
        $chart->set_labels($series->labels());
        $values = $money ? array_map(static fn($v) => ((float)$v) / 100, $series->values()) : $series->values();
        $chart->add_series(new \core\chart_series($currency ?? $title, $values));
        return self::wrap($output->render($chart), $title, self::table($series->labels(), $values, $currency, $title));
    }

    private static function products(\renderer_base $output, array $rows, string $currency): string {
        $chart = new \core\chart_bar();
        $title = get_string('commerce_statistics_chart_top_products', 'local_subscriptions');
        $chart->set_title($title);
        $chart->set_horizontal(true);
        $labels = array_map(static fn($row) => format_string($row['label']), $rows);
        $values = array_map(static fn($row) => $row['revenue_minor'] / 100, $rows);
        $chart->set_labels($labels);
        $chart->add_series(new \core\chart_series($currency, $values));
        return self::wrap($output->render($chart), $title, self::table($labels, $values, $currency, $title));
    }

    private static function health(\renderer_base $output, array $values): string {
        $title = get_string('commerce_statistics_chart_payment_health', 'local_subscriptions');
        $labels = [get_string('commerce_statistics_payment_successful', 'local_subscriptions'), get_string('commerce_statistics_payment_failed', 'local_subscriptions'), get_string('commerce_statistics_payment_refunded', 'local_subscriptions')];
        $data = [(int)($values['successful'] ?? 0), (int)($values['failed'] ?? 0), (int)($values['refunded'] ?? 0)];
        $chart = new \core\chart_pie();
        $chart->set_title($title);
        $chart->set_labels($labels);
        $chart->add_series(new \core\chart_series($title, $data));
        return self::wrap($output->render($chart), $title, self::table($labels, $data, null, $title));
    }

    private static function wrap(string $chart, string $title, string $fallback): string {
        return html_writer::tag('article', html_writer::tag('h3', s($title), ['class' => 'h5']) . $chart . html_writer::tag('details', html_writer::tag('summary', get_string('commerce_statistics_accessible_table', 'local_subscriptions')) . $fallback), ['class' => 'card card-body crm-commerce-statistics-chart']);
    }

    private static function table(array $labels, array $values, ?string $currency, string $caption): string {
        $rows = '';
        foreach ($labels as $i => $label) {
            $value = format_float((float)($values[$i] ?? 0), $currency ? 2 : 0) . ($currency ? ' ' . $currency : '');
            $rows .= html_writer::tag('tr', html_writer::tag('th', s((string)$label), ['scope' => 'row']) . html_writer::tag('td', s($value)));
        }
                $head = html_writer::tag(
            'thead',
            html_writer::tag(
                'tr',
                html_writer::tag('th', get_string('commerce_statistics_table_period', 'local_subscriptions'), ['scope' => 'col'])
                    . html_writer::tag('th', get_string('commerce_statistics_table_value', 'local_subscriptions'), ['scope' => 'col'])
            )
        );
        $body = html_writer::tag('tbody', $rows);
        $captionhtml = html_writer::tag('caption', s($caption), ['class' => 'sr-only']);

        return html_writer::tag(
            'table',
            $captionhtml . $head . $body,
            ['class' => 'table table-sm mt-2']
        );
    }
}
