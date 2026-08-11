<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/** Renders the filters of the Native Commerce statistics dashboard. */
final class CommerceStatisticsFilterRenderer {
    public static function render(
        moodle_url $action,
        int $days,
        string $currency,
        string $provider,
        string $chartmode = 'instant'
    ): string {
        $periodoptions = [
            1 => get_string('commerce_statistics_period_today', 'local_subscriptions'),
            7 => get_string('commerce_statistics_period_7_days', 'local_subscriptions'),
            30 => get_string('commerce_statistics_period_30_days', 'local_subscriptions'),
            90 => get_string('commerce_statistics_period_90_days', 'local_subscriptions'),
            365 => get_string('commerce_statistics_period_year', 'local_subscriptions'),
        ];
        $currencyoptions = [
            '' => get_string('commerce_statistics_all_currencies', 'local_subscriptions'),
            'EUR' => 'EUR',
            'RUB' => 'RUB',
        ];
        $chartmodeoptions = [
            'instant' => get_string('commerce_statistics_chart_mode_instant', 'local_subscriptions'),
            'cumulative' => get_string('commerce_statistics_chart_mode_cumulative', 'local_subscriptions'),
        ];
        $provideroptions = [
            '' => get_string('commerce_statistics_all_providers', 'local_subscriptions'),
            'stripe' => 'Stripe',
            'alfa' => 'Alfa-Bank',
        ];

        $html = html_writer::start_tag('form', [
            'method' => 'get',
            'action' => $action->out(false),
            'class' => 'row g-3 align-items-end',
        ]);
        $html .= self::select_field('days', get_string('commerce_statistics_period', 'local_subscriptions'), $periodoptions, $days);
        $html .= self::select_field('currency', get_string('commerce_statistics_currency', 'local_subscriptions'), $currencyoptions, $currency);
        $html .= self::select_field('provider', get_string('commerce_statistics_provider', 'local_subscriptions'), $provideroptions, $provider);
        $html .= self::select_field('chartmode', get_string('commerce_statistics_chart_mode', 'local_subscriptions'), $chartmodeoptions, $chartmode);
        $html .= html_writer::div(
            html_writer::empty_tag('input', [
                'type' => 'submit',
                'class' => 'btn btn-primary',
                'value' => get_string('applyfilters'),
            ]) .
            html_writer::link($action, get_string('reset'), ['class' => 'btn btn-outline-secondary']),
            'col-12 col-lg-auto d-flex gap-2'
        );
        $html .= html_writer::end_tag('form');

        return $html;
    }

    /** @param array<int|string,string> $options */
    private static function select_field(string $name, string $label, array $options, int|string $selected): string {
        $id = 'commerce-statistics-' . $name;
        return html_writer::div(
            html_writer::tag('label', s($label), ['for' => $id, 'class' => 'form-label']) .
            html_writer::select($options, $name, $selected, false, ['id' => $id, 'class' => 'form-select']),
            'col-12 col-md-4 col-lg-auto'
        );
    }
}
