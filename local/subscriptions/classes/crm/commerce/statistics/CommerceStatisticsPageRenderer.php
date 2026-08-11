<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\statistics\CommerceStatisticsComparison;
use local_subscriptions\commerce\statistics\CommerceStatisticsMetric;
use local_subscriptions\commerce\statistics\CommerceStatisticsSnapshot;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;

/** Accessible presentation layer for Native Commerce statistics. */
final class CommerceStatisticsPageRenderer {
    /** @var string[] */
    private const PRIMARY_METRICS = [
        'net_paid_minor',
        'orders',
        'average_order_minor',
        'customers',
    ];

    /** @var string[] */
    private const HEALTH_METRICS = [
        'successful_payments',
        'failed_payments',
        'refunded_minor',
        'pending_fulfillments',
    ];

    public static function dashboard(CommerceStatisticsSnapshot $snapshot): string {
        $bycurrency = self::group_by_currency($snapshot);
        if ($bycurrency === []) {
            return CommerceDesignSystemRenderer::empty_state(
                get_string('commerce_statistics_empty_title', 'local_subscriptions'),
                get_string('commerce_statistics_empty_description', 'local_subscriptions')
            );
        }

        $sections = [];
        foreach ($bycurrency as $currency => $metrics) {
            $sections[] = html_writer::tag(
                'section',
                html_writer::tag('h2', s($currency), ['class' => 'h4 mb-3']) .
                self::metric_grid($metrics, self::PRIMARY_METRICS, $currency, 'crm-commerce-statistics-primary') .
                html_writer::tag('h3', get_string('commerce_statistics_payment_health', 'local_subscriptions'), ['class' => 'h5 mt-4 mb-3']) .
                self::metric_grid($metrics, self::HEALTH_METRICS, $currency, 'crm-commerce-statistics-health'),
                ['class' => 'crm-commerce-statistics-currency mb-5', 'aria-labelledby' => '']
            );
        }
        return implode('', $sections);
    }

    public static function operational_shortcuts(): string {
        $links = [];
        foreach (CommerceStatisticsDrilldown::operational_links() as $link) {
            $links[] = html_writer::link($link['url'], s($link['label']), ['class' => 'btn btn-outline-primary']);
        }
        return CommerceDesignSystemRenderer::panel(
            get_string('commerce_statistics_operational_shortcuts', 'local_subscriptions'),
            html_writer::div(implode('', $links), 'd-flex flex-wrap gap-2')
        );
    }

    /** @return array<string,array<string,CommerceStatisticsMetric>> */
    private static function group_by_currency(CommerceStatisticsSnapshot $snapshot): array {
        $result = [];
        foreach ($snapshot->metrics() as $metric) {
            $currency = $metric->currency();
            if ($currency === null || $currency === '') {
                continue;
            }
            $result[$currency][$metric->key()] = $metric;
        }
        ksort($result);
        return $result;
    }

    /** @param array<string,CommerceStatisticsMetric> $metrics @param string[] $keys */
    private static function metric_grid(array $metrics, array $keys, string $currency, string $class): string {
        $cards = [];
        foreach ($keys as $key) {
            if (!isset($metrics[$key])) {
                continue;
            }
            $cards[] = self::metric_card($metrics[$key], $currency);
        }
        return html_writer::div(implode('', $cards), 'crm-commerce-statistics-grid ' . $class);
    }

    private static function metric_card(CommerceStatisticsMetric $metric, string $currency): string {
        $comparison = $metric->comparison();
        $url = CommerceStatisticsDrilldown::metric_url($metric->key(), $currency);
        $content = html_writer::div(
            get_string('commerce_statistics_metric_' . $metric->key(), 'local_subscriptions'),
            'crm-commerce-statistics-label'
        );
        $content .= html_writer::div(
            self::format_value($metric->key(), $comparison->current(), $currency),
            'crm-commerce-statistics-value'
        );
        $content .= self::trend($comparison);
        $content .= html_writer::div(
            get_string('commerce_statistics_open_details', 'local_subscriptions'),
            'crm-commerce-statistics-link'
        );

        return html_writer::link($url, $content, [
            'class' => 'crm-commerce-statistics-card',
            'aria-label' => get_string('commerce_statistics_metric_link', 'local_subscriptions', (object)[
                'metric' => get_string('commerce_statistics_metric_' . $metric->key(), 'local_subscriptions'),
                'currency' => $currency,
            ]),
        ]);
    }

    private static function trend(CommerceStatisticsComparison $comparison): string {
        $trend = $comparison->trend();
        if ($trend === CommerceStatisticsComparison::TREND_NOT_AVAILABLE) {
            return html_writer::div(get_string('commerce_statistics_no_comparison', 'local_subscriptions'), 'crm-commerce-statistics-trend is-neutral');
        }
        $percent = $comparison->delta_percent();
        if ($percent === null) {
            $text = get_string('commerce_statistics_comparison_unavailable', 'local_subscriptions');
        } else {
            $prefix = $percent > 0 ? '+' : '';
            $text = get_string('commerce_statistics_vs_previous', 'local_subscriptions', $prefix . format_float($percent, 1) . '%');
        }
        $symbol = $trend === CommerceStatisticsComparison::TREND_UP ? '↑' : ($trend === CommerceStatisticsComparison::TREND_DOWN ? '↓' : '→');
        return html_writer::div(
            html_writer::span($symbol, 'me-1', ['aria-hidden' => 'true']) . s($text),
            'crm-commerce-statistics-trend is-' . $trend
        );
    }

    private static function format_value(string $key, int|float $value, string $currency): string {
        if (str_ends_with($key, '_minor')) {
            $major = ((float)$value) / 100;
            if (class_exists('NumberFormatter')) {
                $formatter = new \NumberFormatter(current_language(), \NumberFormatter::CURRENCY);
                $formatted = $formatter->formatCurrency($major, $currency);
                if ($formatted !== false) {
                    return $formatted;
                }
            }
            return format_float($major, 2) . ' ' . $currency;
        }
        if ($key === 'average_order_minor') {
            return format_float((float)$value / 100, 2) . ' ' . $currency;
        }
        return format_float((float)$value, 0);
    }
}
