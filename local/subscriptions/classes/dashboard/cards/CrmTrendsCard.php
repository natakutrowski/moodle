<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\ui\DashboardCardUi;
use local_subscriptions\dashboard\services\DashboardPeriod;
use local_subscriptions\dashboard\trends\DashboardTrendComparison;
use local_subscriptions\dashboard\trends\DashboardTrendsComparison;
use local_subscriptions\dashboard\trends\DashboardTrendsRepository;
use local_subscriptions\dashboard\trends\DashboardTrendsService;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Aggregated CRM score trends Dashboard card.
 */
final class CrmTrendsCard implements DashboardCard {

    /**
     * Maximum number of trend rows displayed in the Dashboard card.
     */
    private const MAX_DISPLAYED_TRENDS = 6;

    /**
     * Render the CRM trends Dashboard card.
     */
    public static function render(
        string $period = DashboardPeriod::TODAY
    ): string {
        if (!Capabilities::can_view_users()) {
            return '';
        }

        $period = DashboardPeriod::normalize(
            $period
        );

        if (
            !DashboardPeriod::is_comparable(
                $period
            )
        ) {
            return self::render_non_comparable_card();
        }

        try {
            $comparison = (
                new DashboardTrendsService()
            )->load($period);
        } catch (\Throwable $exception) {
            debugging(
                'Unable to load CRM Dashboard trends: '
                    . $exception->getMessage(),
                DEBUG_DEVELOPER
            );

            return self::render_error_card();
        }

        $content = self::render_header(
            $comparison
        );

        if (!$comparison->current->has_current_data()) {
            $content .= self::render_empty_state(
                'crm_trends_no_current_data',
                'crm-trends-empty--unavailable'
            );

            return self::wrap_card($content);
        }

        if (
            !$comparison->current
                ->has_comparable_data()
        ) {
            $content .= self::render_empty_state(
                'crm_trends_insufficient_data',
                'crm-trends-empty--pending'
            );

            $content .= self::render_freshness(
                $comparison
            );

            return self::wrap_card($content);
        }

        $visiblemetrics =
            self::visible_metrics($comparison);

        if (empty($visiblemetrics)) {
            $content .= self::render_empty_state(
                'crm_trends_no_movements',
                'crm-trends-empty--stable'
            );

            $content .= self::render_freshness(
                $comparison
            );

            return self::wrap_card($content);
        }

        $rows = '';

        foreach ($visiblemetrics as $metric) {
            $rows .= self::render_metric(
                $metric,
                $comparison
            );
        }

        $content .= html_writer::div(
            $rows,
            'crm-trends-list'
        );

        $content .= self::render_footer(
            $comparison,
            $period
        );

        return self::wrap_card($content);
    }

    /**
     * Render the Card heading and contextual subtitle.
     */
    private static function render_header(
        DashboardTrendsComparison $comparison
    ): string {
        return DashboardCardUi::header(
            title: get_string(
                'crm_trends_title',
                'local_subscriptions'
            ),
            icon: '📈',
            subtitle: get_string(
                'crm_trends_subtitle',
                'local_subscriptions',
                (object)[
                    'analysed' =>
                        $comparison->current
                            ->analysedusers,
                    'available' =>
                        $comparison->current
                            ->currentusers,
                ]
            ),
            titleid:
                'crm-dashboard-trends-title'
        );
    }

    /**
     * Return only useful metrics for the Card.
     *
     * Metrics without any current or previous occurrence are hidden.
     *
     * @return DashboardTrendComparison[]
     */
    private static function visible_metrics(
        DashboardTrendsComparison $comparison
    ): array {
        $visible = [];

        foreach (
            $comparison->prioritized_metrics()
            as $metric
        ) {
            if (
                $metric->current->value <= 0
                && $metric->previous->value <= 0
            ) {
                continue;
            }

            $visible[] = $metric;

            if (
                count($visible)
                >= self::MAX_DISPLAYED_TRENDS
            ) {
                break;
            }
        }

        return $visible;
    }

    /**
     * Render one aggregated trend.
     */
    private static function render_metric(
        DashboardTrendComparison $comparison,
        DashboardTrendsComparison $alltrends
    ): string {
        $metric = $comparison->current;

        $presentation =
            self::metric_presentation(
                $metric->key
            );

        $state =
            self::comparison_state(
                $comparison
            );

        $icon = html_writer::span(
            $presentation['icon'],
            'crm-trends-metric-icon',
            [
                'aria-hidden' => 'true',
            ]
        );

        $label = html_writer::div(
            get_string(
                $presentation['label'],
                'local_subscriptions'
            ),
            'crm-trends-metric-label'
        );

        $description = html_writer::div(
            get_string(
                $presentation['description'],
                'local_subscriptions'
            ),
            'small text-muted crm-trends-metric-description'
        );

        $heading = html_writer::div(
            $icon
            . html_writer::div(
                $label . $description,
                'crm-trends-metric-heading-text'
            ),
            'crm-trends-metric-heading'
        );

        $currentvalue = html_writer::div(
            html_writer::span(
                (string)$metric->value,
                'crm-trends-metric-value-number'
            )
            . html_writer::span(
                get_string(
                    'crm_trends_users',
                    'local_subscriptions',
                    $metric->value
                ),
                'crm-trends-metric-value-label'
            ),
            'crm-trends-metric-value'
        );

        $comparisonbadge =
            self::render_comparison_badge(
                $comparison,
                $state
            );

        $previous = html_writer::div(
            get_string(
                'crm_trends_previous_value',
                'local_subscriptions',
                $comparison->previous->value
            ),
            'small text-muted crm-trends-previous'
        );

        $values = html_writer::div(
            $currentvalue
            . html_writer::div(
                $comparisonbadge . $previous,
                'crm-trends-metric-comparison'
            ),
            'crm-trends-metric-values'
        );

        $url = new moodle_url(
            subscription_config::admin_users_page(),
            [
                'trend' => $metric->key,
                'trendstart' =>
                    $alltrends->current->start,
                'trendend' =>
                    $alltrends->current->end,
                'trenddelta' =>
                    DashboardTrendsRepository::
                        DEFAULT_SIGNIFICANT_DELTA,
            ]
        );

        $linklabel = get_string(
            'crm_trends_metric_open',
            'local_subscriptions',
            get_string(
                $presentation['label'],
                'local_subscriptions'
            )
        );

        return html_writer::link(
            $url,
            $heading . $values,
            [
                'class' =>
                    'crm-trends-metric '
                    . 'crm-trends-metric--'
                    . $state,
                'aria-label' => $linklabel,
            ]
        );
    }

    /**
     * Render the trend comparison badge.
     */
    private static function render_comparison_badge(
        DashboardTrendComparison $comparison,
        string $state
    ): string {
        $difference =
            $comparison->difference();

        if ($difference > 0) {
            $differencevalue =
                '+' . $difference;
        } else {
            $differencevalue =
                (string)$difference;
        }

        $variation =
            $comparison->variation();

        if ($variation === null) {
            $text = get_string(
                'crm_trends_difference_only',
                'local_subscriptions',
                $differencevalue
            );
        } else {
            $variationvalue =
                $variation > 0
                    ? '+' . format_float($variation, 1)
                    : format_float($variation, 1);

            $text = get_string(
                'crm_trends_difference_with_percent',
                'local_subscriptions',
                (object)[
                    'difference' =>
                        $differencevalue,
                    'variation' =>
                        $variationvalue,
                ]
            );
        }

        if ($difference === 0) {
            $text = get_string(
                'crm_trends_stable',
                'local_subscriptions'
            );
        }

        return html_writer::span(
            self::state_icon($state)
            . html_writer::span(
                $text,
                'crm-trends-comparison-text'
            ),
            'crm-trends-comparison-badge '
                . 'crm-trends-comparison-badge--'
                . $state
        );
    }

    /**
     * Render the Card footer.
     */
    private static function render_footer(
        DashboardTrendsComparison $comparison,
        string $period
    ): string {
        $content =
            self::render_freshness(
                $comparison
            );

        $url = new moodle_url(
            subscription_config::
                admin_users_page(),
            [
                'period' => $period,
            ]
        );

        $content .= html_writer::link(
            $url,
            get_string(
                'crm_trends_open_explorer',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-sm btn-outline-secondary '
                    . 'crm-trends-explorer-link',
            ]
        );

        return DashboardCardUi::footer(
            $content,
            'crm-trends-footer'
        );
    }

    /**
     * Render persisted-data freshness.
     */
    private static function render_freshness(
        DashboardTrendsComparison $comparison
    ): string {
        $freshness =
            $comparison->current->freshness;

        if ($freshness <= 0) {
            return html_writer::span(
                get_string(
                    'crm_trends_freshness_unknown',
                    'local_subscriptions'
                ),
                'small text-muted crm-trends-freshness'
            );
        }

        return html_writer::span(
            get_string(
                'crm_trends_freshness',
                'local_subscriptions',
                userdate(
                    $freshness,
                    get_string(
                        'strftimedatetimeshort',
                        'langconfig'
                    )
                )
            ),
            'small text-muted crm-trends-freshness'
        );
    }

    /**
     * Render an empty state.
     */
    private static function render_empty_state(
        string $stringkey,
        string $class
    ): string {
        $tone = DashboardCardUi::TONE_NEUTRAL;
        $icon = '—';

        if (
            $class ===
            'crm-trends-empty--pending'
        ) {
            $tone =
                DashboardCardUi::TONE_INFO;
            $icon = 'ℹ';
        } else if (
            $class ===
            'crm-trends-empty--stable'
        ) {
            $tone =
                DashboardCardUi::TONE_SUCCESS;
            $icon = '✓';
        }

        return DashboardCardUi::empty_state(
            title: get_string(
                $stringkey,
                'local_subscriptions'
            ),
            icon: $icon,
            tone: $tone
        );
    }

    /**
     * Renders the Trends Card when no previous period exists.
     */
    private static function render_non_comparable_card(): string {
        $content = DashboardCardUi::header(
            title: get_string(
                'crm_trends_title',
                'local_subscriptions'
            ),
            icon: '📉',
            subtitle: get_string(
                'dashboard_trends_all_time_subtitle',
                'local_subscriptions'
            ),
            titleid: 'crm-dashboard-trends-title'
        );

        $content .= DashboardCardUi::info_state(
            title: get_string(
                'dashboard_trends_all_time_title',
                'local_subscriptions'
            ),
            description: get_string(
                'dashboard_trends_all_time_message',
                'local_subscriptions'
            ),
            icon: '∞'
        );

        return self::wrap_card($content);
    }

    /**
     * Render an unexpected-error state.
     */
    private static function render_error_card(): string {
        $content = DashboardCardUi::header(
            title: get_string(
                'crm_trends_title',
                'local_subscriptions'
            ),
            icon: '📈',
            titleid:
                'crm-dashboard-trends-title'
        );

        $content .= DashboardCardUi::error_state(
            title: get_string(
                'dashboard_state_error_title',
                'local_subscriptions'
            ),
            description: get_string(
                'crm_trends_error',
                'local_subscriptions'
            )
        );

        return self::wrap_card($content);
    }

    /**
     * Wrap the content in the standard Dashboard Card.
     */
    private static function wrap_card(
        string $content
    ): string {
        return DashboardCardUi::shell(
            content: $content,
            extraclasses:
                'crm-trends-card mb-4',
            labelledby:
                'crm-dashboard-trends-title'
        );
    }

    /**
     * Resolve business comparison state.
     */
    private static function comparison_state(
        DashboardTrendComparison $comparison
    ): string {
        if (
            $comparison->business_is_degrading()
        ) {
            return 'degrading';
        }

        if (
            $comparison->business_is_improving()
        ) {
            return 'improving';
        }

        return 'stable';
    }

    /**
     * Return the icon used by a comparison badge.
     */
    private static function state_icon(
        string $state
    ): string {
        return match ($state) {
            'improving' =>
                html_writer::span(
                    '↗',
                    'crm-trends-comparison-icon',
                    [
                        'aria-hidden' => 'true',
                    ]
                ),

            'degrading' =>
                html_writer::span(
                    '↘',
                    'crm-trends-comparison-icon',
                    [
                        'aria-hidden' => 'true',
                    ]
                ),

            default =>
                html_writer::span(
                    '→',
                    'crm-trends-comparison-icon',
                    [
                        'aria-hidden' => 'true',
                    ]
                ),
        };
    }

    /**
     * Return labels and icons for one metric.
     *
     * @return array{
     *     icon: string,
     *     label: string,
     *     description: string
     * }
     */
    private static function metric_presentation(
        string $key
    ): array {
        return match ($key) {
            DashboardTrendsRepository::
                METRIC_RISK_UP => [
                    'icon' => '⚠️',
                    'label' =>
                        'crm_trends_metric_risk_up',
                    'description' =>
                        'crm_trends_metric_risk_up_desc',
                ],

            DashboardTrendsRepository::
                METRIC_RISK_DOWN => [
                    'icon' => '🛡️',
                    'label' =>
                        'crm_trends_metric_risk_down',
                    'description' =>
                        'crm_trends_metric_risk_down_desc',
                ],

            DashboardTrendsRepository::
                METRIC_ENGAGEMENT_UP => [
                    'icon' => '🌱',
                    'label' =>
                        'crm_trends_metric_engagement_up',
                    'description' =>
                        'crm_trends_metric_engagement_up_desc',
                ],

            DashboardTrendsRepository::
                METRIC_ENGAGEMENT_DOWN => [
                    'icon' => '📉',
                    'label' =>
                        'crm_trends_metric_engagement_down',
                    'description' =>
                        'crm_trends_metric_engagement_down_desc',
                ],

            DashboardTrendsRepository::
                METRIC_GLOBAL_UP => [
                    'icon' => '✨',
                    'label' =>
                        'crm_trends_metric_global_up',
                    'description' =>
                        'crm_trends_metric_global_up_desc',
                ],

            DashboardTrendsRepository::
                METRIC_GLOBAL_DOWN => [
                    'icon' => '🔻',
                    'label' =>
                        'crm_trends_metric_global_down',
                    'description' =>
                        'crm_trends_metric_global_down_desc',
                ],

            default => [
                'icon' => '•',
                'label' =>
                    'crm_trends_metric_unknown',
                'description' =>
                    'crm_trends_metric_unknown_desc',
            ],
        };
    }
}