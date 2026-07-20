<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use local_subscriptions\subscription_config;
use local_subscriptions\currency\Currency;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\services\DashboardPeriod;
use local_subscriptions\dashboard\services\DashboardStatsService;

/**
 * Main Dashboard metrics.
 */
final class StatsCard implements DashboardCard {

    public static function render(
        string $period = DashboardPeriod::TODAY
    ): string {
        $period = DashboardPeriod::normalize($period);

        $stats = (new DashboardStatsService())->load(
            $period
        );

        $newusers = (int)($stats->newusers ?? 0);
        $newtrials = (int)($stats->newtrials ?? 0);
        $newcustomers = (int)($stats->newcustomers ?? 0);

        $trialcustomerratio =
            $stats->trialcustomerratio ?? null;

        $trialcustomerratiolabel =
            $trialcustomerratio === null
                ? get_string(
                    'dashboard_trial_customer_ratio_unavailable',
                    'local_subscriptions'
                )
                : get_string(
                    'dashboard_trial_customer_ratio_value',
                    'local_subscriptions',
                    format_float(
                        (float)$trialcustomerratio,
                        1
                    )
                );

        $cards = [
            [
                'icon' => '👤',
                'label' => get_string(
                    'dashboard_stats_new_users',
                    'local_subscriptions'
                ),
                'value' => (string)$newusers,
                'class' => '',
                'help' => '',
                'title' => '',
            ],
            [
                'icon' => '🧪',
                'label' => get_string(
                    'dashboard_new_trials',
                    'local_subscriptions'
                ),
                'value' => (string)$newtrials,
                'class' => '',
                'help' => '',
                'title' => '',
            ],
            [
                'icon' => '💳',
                'label' => get_string(
                    'dashboard_new_customers',
                    'local_subscriptions'
                ),
                'value' => (string)$newcustomers,
                'class' => '',
                'help' => '',
                'title' => '',
            ],
            [
                'icon' => '📈',
                'label' => get_string(
                    'dashboard_trial_customer_ratio',
                    'local_subscriptions'
                ),
                'value' => $trialcustomerratiolabel,
                'class' => 'crm-dashboard-stat-card-ratio',
                'help' => '',
                'title' => get_string(
                    'dashboard_trial_customer_ratio_help',
                    'local_subscriptions'
                ),
            ],
            [
                'icon' => '📦',
                'label' => get_string(
                    'dashboard_stats_digital_purchases',
                    'local_subscriptions'
                ),
                'value' => (string)($stats->digitalpurchases ?? 0),
                'class' => '',
                'help' => '',
                'title' => '',
            ],
        ];

        $out = html_writer::start_div(
            'crm-dashboard-hero mb-4'
        );

        $out .= html_writer::start_div(
            'crm-dashboard-hero-header'
        );

        $out .= html_writer::tag(
            'h3',
            get_string(
                'dashboard_command_center_title',
                'local_subscriptions'
            ),
            [
                'class' => 'h4 mb-0',
            ]
        );

        $out .= self::period_control($period);
        $out .= html_writer::end_div();

        $out .= html_writer::start_div(
            'row mt-3 crm-dashboard-stats-grid'
        );

        foreach ($cards as $card) {
            $out .= self::stat_card(
                $card['icon'],
                $card['label'],
                $card['value'],
                $card['class'],
                $card['help'],
                $card['title']
            );
        }

        $out .= self::revenue_card($stats);

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        return $out;
    }

    /**
     * Render a standard Dashboard metric.
     *
     * @param string $icon Decorative icon.
     * @param string $label Metric label.
     * @param string $value Formatted metric value.
     * @param string $extraclass Additional Card class.
     * @param string $help Optional visible help.
     * @param string $title Optional accessible tooltip.
     * @return string
     */
    private static function stat_card(
        string $icon,
        string $label,
        string $value,
        string $extraclass = '',
        string $help = '',
        string $title = ''
    ): string {
        $classes = trim(
            'card card-body local-subscriptions-dashboard-card '
            . 'crm-dashboard-stat-card '
            . $extraclass
        );

        $attributes = [];

        if ($title !== '') {
            $attributes['title'] = $title;
            $attributes['aria-label'] =
                $label . '. ' . $value . '. ' . $title;
        }

        $content =
            html_writer::div(
                s($icon),
                'dashboard-stat-icon',
                [
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::div(
                s($value),
                'crm-stat-number'
            )
            . html_writer::div(
                s($label),
                'text-muted'
            );

        if ($help !== '') {
            $content .= html_writer::div(
                s($help),
                'crm-dashboard-stat-help '
                    . 'small text-muted mt-2'
            );
        }

        return html_writer::div(
            html_writer::div(
                $content,
                $classes,
                $attributes
            ),
            'col-md-6 col-xl-4 mb-3'
        );
    }

    /**
     * Render combined revenue with client-side currency switching.
     */
    private static function revenue_card(
        \stdClass $stats
    ): string {
        $revenues = $stats->formattedrevenues;
        $selectedcurrency = $stats->selectedcurrency;

        $attributes = [
            'class' =>
                'card card-body local-subscriptions-dashboard-card '
                . 'crm-dashboard-stat-card '
                . 'crm-dashboard-revenue-card',
            'data-region' => 'dashboard-revenue-card',
        ];

        foreach ($revenues as $currency => $revenue) {
            $key = strtolower($currency);

            $attributes['data-total-' . $key] =
                $revenue['formattedtotal'];

            $attributes['data-subscriptions-' . $key] =
                $revenue['formattedsubscriptions'];

            $attributes['data-digital-' . $key] =
                $revenue['formatteddigital'];
        }

        $header =
            html_writer::div(
                '💰',
                'dashboard-stat-icon',
                [
                    'aria-hidden' => 'true',
                ]
            )
            . self::currency_selector(
                array_keys($revenues),
                $selectedcurrency
            );

        $total = html_writer::div(
            s($stats->formattedrevenue),
            'crm-stat-number',
            [
                'data-region' => 'dashboard-revenue-total',
                'aria-live' => 'polite',
                'aria-atomic' => 'true',
            ]
        );

        $label = html_writer::div(
            get_string(
                'dashboard_stats_revenue',
                'local_subscriptions'
            ),
            'text-muted'
        );

        $breakdown = self::revenue_breakdown(
            $stats
        );

        $content =
            html_writer::div(
                $header,
                'crm-dashboard-revenue-header'
            )
            . $total
            . $label
            . $breakdown;

        return html_writer::div(
            html_writer::div(
                $content,
                '',
                $attributes
            ),
            'col-md-6 col-xl-4 mb-3'
        );
    }

    /**
     * Render the selected-currency breakdown.
     */
    private static function revenue_breakdown(
        \stdClass $stats
    ): string {
        $selected = $stats->formattedrevenues[
            $stats->selectedcurrency
        ] ?? [
            'formattedsubscriptions' =>
                Currency::display_symbol(
                    $stats->selectedcurrency
                ) . ' 0',
            'formatteddigital' =>
                Currency::display_symbol(
                    $stats->selectedcurrency
                ) . ' 0',
        ];

        $subscriptionline =
            html_writer::span(
                get_string(
                    'dashboard_revenue_subscriptions',
                    'local_subscriptions'
                ),
                'crm-dashboard-revenue-breakdown-label'
            )
            . html_writer::span(
                s($selected['formattedsubscriptions']),
                'crm-dashboard-revenue-breakdown-value',
                [
                    'data-region' =>
                        'dashboard-revenue-subscriptions',
                ]
            );

        $digitalline =
            html_writer::span(
                get_string(
                    'dashboard_revenue_digital',
                    'local_subscriptions'
                ),
                'crm-dashboard-revenue-breakdown-label'
            )
            . html_writer::span(
                s($selected['formatteddigital']),
                'crm-dashboard-revenue-breakdown-value',
                [
                    'data-region' =>
                        'dashboard-revenue-digital',
                ]
            );

        return html_writer::div(
            html_writer::div(
                $subscriptionline,
                'crm-dashboard-revenue-breakdown-row'
            )
            . html_writer::div(
                $digitalline,
                'crm-dashboard-revenue-breakdown-row'
            ),
            'crm-dashboard-revenue-breakdown'
        );
    }

    /**
     * Render the available-currency selector.
     *
     * @param string[] $currencies
     */
    private static function currency_selector(
        array $currencies,
        string $selectedcurrency
    ): string {
        if (empty($currencies)) {
            $currencies = [$selectedcurrency];
        }

        $options = [];

        foreach ($currencies as $currency) {
            $currency = Currency::sanitize($currency);

            if ($currency === '') {
                continue;
            }

            $label = Currency::display_symbol($currency);

            if ($label !== $currency) {
                $label .= ' · ' . $currency;
            }

            $options[$currency] = $label;
        }

        return html_writer::select(
            $options,
            'dashboard_revenue_currency',
            $selectedcurrency,
            false,
            [
                'class' =>
                    'custom-select custom-select-sm '
                    . 'crm-dashboard-currency-selector',
                'data-region' =>
                    'dashboard-revenue-currency',
                'aria-label' => get_string(
                    'dashboard_revenue_currency_select',
                    'local_subscriptions'
                ),
            ]
        );
    }

    private static function period_control(
        string $active
    ): string {
        $items = '';

        foreach (DashboardPeriod::allowed() as $period) {
            $classes = 'crm-dashboard-period-pill';

            if ($period === $active) {
                $classes .= ' active';
            }

            $items .= html_writer::link(
                new moodle_url(
                    subscription_config::admin_dashboard_page(),
                    [
                        'period' => $period,
                    ]
                ),
                DashboardPeriod::label($period),
                [
                    'class' => $classes,
                ]
            );
        }

        return html_writer::div(
            $items,
            'crm-dashboard-period-control'
        );
    }
}