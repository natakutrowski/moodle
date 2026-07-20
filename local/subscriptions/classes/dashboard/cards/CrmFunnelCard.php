<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\ui\DashboardCardUi;
use local_subscriptions\dashboard\funnel\DashboardFunnelComparison;
use local_subscriptions\dashboard\funnel\DashboardFunnelService;
use local_subscriptions\dashboard\funnel\DashboardFunnelSnapshot;
use local_subscriptions\dashboard\services\DashboardPeriod;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Dashboard acquisition and conversion Funnel.
 */
final class CrmFunnelCard implements DashboardCard {

    /**
     * Render the Funnel for the selected Dashboard period.
     *
     * @return string
     */
    public static function render(): string {
        $period = DashboardPeriod::normalize(
            optional_param(
                'period',
                DashboardPeriod::TODAY,
                PARAM_ALPHA
            )
        );

        $comparison = (
            new DashboardFunnelService()
        )->load($period);

        $current = $comparison->current;

        $header = DashboardCardUi::header(
            title: get_string(
                'dashboard_funnel_title',
                'local_subscriptions'
            ),
            icon: '📊',
            subtitle: get_string(
                'dashboard_funnel_subtitle',
                'local_subscriptions',
                $comparison->conversionwindowdays
            ),
            titleid: 'crm-dashboard-funnel-title'
        );

        $rows = [];

        $rows[] = self::volume_row(
            '👤',
            get_string(
                'dashboard_funnel_new_users',
                'local_subscriptions'
            ),
            $current->newusers,
            $comparison->new_users_difference(),
            $comparison->new_users_variation(),
            self::explorer_url(
                'new_users',
                $current,
                $comparison->conversionwindowdays
            )
        );

        $rows[] = self::volume_row(
            '🧪',
            get_string(
                'dashboard_funnel_trial_users',
                'local_subscriptions'
            ),
            $current->trialusers,
            $comparison->trial_users_difference(),
            $comparison->trial_users_variation(),
            self::explorer_url(
                'trial_users',
                $current,
                $comparison->conversionwindowdays
            )
        );

        $rows[] = self::conversion_row(
            $current,
            $comparison
        );

        $rows[] = self::volume_row(
            '💳',
            get_string(
                'dashboard_funnel_new_customers',
                'local_subscriptions'
            ),
            $current->newcustomers,
            $comparison->new_customers_difference(),
            $comparison->new_customers_variation(),
            self::explorer_url(
                'new_customers',
                $current,
                $comparison->conversionwindowdays
            )
        );

        $rows[] = self::volume_row(
            '📦',
            get_string(
                'dashboard_funnel_digital_buyers',
                'local_subscriptions'
            ),
            $current->digitalbuyers,
            $comparison->digital_buyers_difference(),
            $comparison->digital_buyers_variation(),
            self::explorer_url(
                'digital_buyers',
                $current,
                $comparison->conversionwindowdays
            )
        );

        $observation = '';

        if ($current->pendingtrialusers > 0) {
            $observation = html_writer::div(
                get_string(
                    'dashboard_funnel_pending_observation',
                    'local_subscriptions',
                    $current->pendingtrialusers
                ),
                'crm-funnel-observation small text-muted'
            );
        }

        $content =
            $header .
            html_writer::div(
                implode('', $rows),
                'crm-funnel-list'
            ) .
            $observation;

        return DashboardCardUi::shell(
            content: $content,
            extraclasses:
                'crm-dashboard-funnel-card mb-4',
            labelledby:
                'crm-dashboard-funnel-title'
        );
    }

    /**
     * Render one volume metric.
     *
     * @param string $icon
     * @param string $label
     * @param int $value
     * @param int $difference
     * @param float|null $variation
     * @param moodle_url $url
     * @return string
     */
    private static function volume_row(
        string $icon,
        string $label,
        int $value,
        int $difference,
        ?float $variation,
        moodle_url $url
    ): string {
        $trend = self::volume_trend(
            $difference,
            $variation
        );

        $content =
            html_writer::start_div(
                'crm-funnel-row-main'
            )
            . html_writer::span(
                s($icon),
                'crm-funnel-icon',
                [
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::span(
                s($label),
                'crm-funnel-label'
            )
            . html_writer::span(
                format_float($value, 0),
                'crm-funnel-value'
            )
            . html_writer::end_div()
            . $trend;

        return html_writer::link(
            $url,
            $content,
            [
                'class' =>
                    'crm-funnel-row crm-funnel-row-link',
                'aria-label' =>
                    $label . ': ' . $value,
            ]
        );
    }

    /**
     * Render the cohort conversion metric.
     *
     * @param DashboardFunnelSnapshot $current
     * @param DashboardFunnelComparison $comparison
     * @return string
     */
    private static function conversion_row(
        DashboardFunnelSnapshot $current,
        DashboardFunnelComparison $comparison
    ): string {
        $rate =
            $current->mature_trial_conversion();

        $ratedifference =
            $comparison->mature_conversion_difference();

        $value = $rate === null
            ? get_string(
                'dashboard_funnel_rate_unavailable',
                'local_subscriptions'
            )
            : get_string(
                'dashboard_funnel_rate_value',
                'local_subscriptions',
                format_float($rate, 1)
            );

        $details = get_string(
            'dashboard_funnel_conversion_details',
            'local_subscriptions',
            (object)[
                'converted' =>
                    $current->convertedmaturetrialusers,
                'mature' =>
                    $current->maturetrialusers,
                'days' =>
                    $comparison->conversionwindowdays,
            ]
        );

        $trend = self::rate_trend(
            $ratedifference
        );

        $content =
            html_writer::start_div(
                'crm-funnel-row-main'
            )
            . html_writer::span(
                '🎯',
                'crm-funnel-icon',
                [
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::span(
                get_string(
                    'dashboard_funnel_conversion',
                    'local_subscriptions'
                ),
                'crm-funnel-label'
            )
            . html_writer::span(
                s($value),
                'crm-funnel-value'
            )
            . html_writer::end_div()
            . html_writer::div(
                s($details),
                'crm-funnel-row-detail small text-muted'
            )
            . $trend;

        return html_writer::link(
            self::explorer_url(
                'converted_trials',
                $current,
                $comparison->conversionwindowdays
            ),
            $content,
            [
                'class' =>
                    'crm-funnel-row crm-funnel-row-link',
                'aria-label' =>
                    get_string(
                        'dashboard_funnel_conversion',
                        'local_subscriptions'
                    )
                    . ': '
                    . $value,
            ]
        );
    }

    /**
     * Render a volume comparison badge.
     *
     * @param int $difference
     * @param float|null $variation
     * @return string
     */
    private static function volume_trend(
        int $difference,
        ?float $variation
    ): string {
        if ($difference === 0) {
            return html_writer::div(
                get_string(
                    'dashboard_funnel_trend_stable',
                    'local_subscriptions'
                ),
                'crm-funnel-trend '
                    . 'crm-funnel-trend-neutral'
            );
        }

        $positive = $difference > 0;

        if ($variation === null) {
            $label = get_string(
                'dashboard_funnel_trend_absolute',
                'local_subscriptions',
                ($positive ? '+' : '') . $difference
            );
        } else {
            $label = get_string(
                'dashboard_funnel_trend_percent',
                'local_subscriptions',
                ($variation > 0 ? '+' : '')
                    . format_float($variation, 1)
            );
        }

        return html_writer::div(
            s($label),
            'crm-funnel-trend '
                . (
                    $positive
                        ? 'crm-funnel-trend-positive'
                        : 'crm-funnel-trend-negative'
                )
        );
    }

    /**
     * Render a conversion-rate comparison.
     *
     * @param float|null $difference
     * @return string
     */
    private static function rate_trend(
        ?float $difference
    ): string {
        if ($difference === null) {
            return html_writer::div(
                get_string(
                    'dashboard_funnel_trend_not_comparable',
                    'local_subscriptions'
                ),
                'crm-funnel-trend '
                    . 'crm-funnel-trend-neutral'
            );
        }

        if ($difference === 0.0) {
            return html_writer::div(
                get_string(
                    'dashboard_funnel_trend_stable',
                    'local_subscriptions'
                ),
                'crm-funnel-trend '
                    . 'crm-funnel-trend-neutral'
            );
        }

        $label = get_string(
            'dashboard_funnel_trend_points',
            'local_subscriptions',
            ($difference > 0 ? '+' : '')
                . format_float($difference, 1)
        );

        return html_writer::div(
            s($label),
            'crm-funnel-trend '
                . (
                    $difference > 0
                        ? 'crm-funnel-trend-positive'
                        : 'crm-funnel-trend-negative'
                )
        );
    }

    /**
     * Build an Explorer URL for one Funnel stage.
     *
     * @param string $stage
     * @param DashboardFunnelSnapshot $snapshot
     * @param int $conversionwindowdays
     * @return moodle_url
     */
    private static function explorer_url(
        string $stage,
        DashboardFunnelSnapshot $snapshot,
        int $conversionwindowdays
    ): moodle_url {
        return new moodle_url(
            subscription_config::admin_users_page(),
            [
                'funnelstage' => $stage,
                'funnelstart' => $snapshot->start,
                'funnelend' => $snapshot->end,
                'funnelwindow' => $conversionwindowdays,
            ]
        );
    }
}