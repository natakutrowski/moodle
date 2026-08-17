<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\statistics\CommerceStatisticsSeries;

/**
 * Premium renderer for reusable product analytics datasets.
 *
 * Statistical semantics remain owned by CommerceProductStatisticsDashboardRepository.
 */
final class CommerceProductStatisticsDashboardRenderer {
    public static function comparison_note(?string $description): string {
        if ($description === null || trim($description) === '') {
            return '';
        }

        return html_writer::div(
            html_writer::tag('i', '', [
                'class' => 'fa-solid fa-arrow-trend-up',
                'aria-hidden' => 'true',
            ]) .
            html_writer::tag('span', s($description)),
            'm51-comparison-note'
        );
    }

    public static function kpis(
        array $snapshot,
        callable $money,
        ?array $previous = null
    ): string {
        $global = $snapshot['global'];
        $previousglobal = $previous['global'] ?? [];

        $primarycards = [
            [
                'key' => 'paid',
                'field' => 'paidorders',
                'label' => 'commerce_m51_paid_orders',
                'help' => 'commerce_m51_paid_orders_help',
                'icon' => 'fa-solid fa-receipt',
            ],
            [
                'key' => 'units',
                'field' => 'soldquantity',
                'label' => 'commerce_m51_units_sold',
                'help' => 'commerce_m51_units_sold_help',
                'icon' => 'fa-solid fa-box',
            ],
            [
                'key' => 'grants',
                'field' => 'manualgrants',
                'label' => 'commerce_m51_manual_grants',
                'help' => 'commerce_m51_manual_grants_help',
                'icon' => 'fa-solid fa-gift',
            ],
            [
                'key' => 'delivered',
                'field' => 'delivered',
                'label' => 'commerce_m51_total_delivered',
                'help' => 'commerce_m51_total_delivered_help',
                'icon' => 'fa-solid fa-circle-check',
            ],
        ];

        $html = html_writer::start_div('m51-kpi-layout');

        $html .= html_writer::start_div('m51-primary-kpis');
        foreach ($primarycards as $card) {
            $current = (int)($global[$card['field']] ?? 0);
            $old = $previous !== null ? (int)($previousglobal[$card['field']] ?? 0) : null;

            $html .= html_writer::start_tag('article', [
                'class' => 'm51-stat-card m51-stat-card--' . $card['key'],
            ]);
            $html .= html_writer::div(
                html_writer::tag('i', '', [
                    'class' => $card['icon'],
                    'aria-hidden' => 'true',
                ]),
                'm51-stat-icon'
            );
            $html .= html_writer::start_div('m51-stat-copy');
            $html .= html_writer::start_div('m51-stat-value-row');
            $html .= html_writer::div(s((string)$current), 'm51-stat-value');
            if ($old !== null) {
                $html .= self::trend($current, $old);
            }
            $html .= html_writer::end_div();
            $html .= html_writer::div(
                s(get_string($card['label'], 'local_subscriptions')),
                'm51-stat-label'
            );
            $html .= html_writer::div(
                s(get_string($card['help'], 'local_subscriptions')),
                'm51-stat-help'
            );
            $html .= html_writer::end_div();
            $html .= html_writer::end_tag('article');
        }
        $html .= html_writer::end_div();

        if ($snapshot['currencies']) {
            $html .= html_writer::start_div('m51-finance-kpis');
            foreach ($snapshot['currencies'] as $currency => $row) {
                $current = (int)($row['revenueminor'] ?? 0);
                $old = $previous !== null
                    ? (int)($previous['currencies'][$currency]['revenueminor'] ?? 0)
                    : null;

                $html .= html_writer::start_tag('article', ['class' => 'm51-revenue-card']);
                $html .= html_writer::div(
                    html_writer::tag(
                        'span',
                        self::currency_flag($currency) . ' ' . s($currency),
                        ['class' => 'm51-revenue-currency']
                    ) .
                    html_writer::tag(
                        'span',
                        s(get_string('commerce_m51_revenue_collected', 'local_subscriptions')),
                        ['class' => 'm51-revenue-caption']
                    ),
                    'm51-revenue-heading'
                );
                $html .= html_writer::start_div('m51-revenue-value-row');
                $html .= html_writer::tag(
                    'strong',
                    s($money($current, $currency)),
                    ['class' => 'm51-revenue-value']
                );
                if ($old !== null) {
                    $html .= self::trend($current, $old);
                }
                $html .= html_writer::end_div();
                $html .= html_writer::end_tag('article');
            }
            $html .= html_writer::end_div();
        }

        $statuscards = [
            [
                'label' => 'commerce_m51_payment_pending',
                'value' => $global['pending'],
                'help' => 'commerce_m51_payment_pending_help',
                'class' => 'pending',
                'icon' => 'fa-regular fa-clock',
            ],
            [
                'label' => 'commerce_m51_payment_failed',
                'value' => $global['failed'],
                'help' => 'commerce_m51_payment_failed_help',
                'class' => 'failed',
                'icon' => 'fa-solid fa-triangle-exclamation',
            ],
            [
                'label' => 'commerce_m51_payment_cancelled',
                'value' => $global['cancelled'],
                'help' => 'commerce_m51_payment_cancelled_help',
                'class' => 'cancelled',
                'icon' => 'fa-solid fa-ban',
            ],
        ];

        $html .= html_writer::start_div('m51-payment-health');
        foreach ($statuscards as $card) {
            $html .= html_writer::start_tag('article', [
                'class' => 'm51-health-card m51-health-card--' . $card['class'],
                'title' => get_string($card['help'], 'local_subscriptions'),
            ]);
            $html .= html_writer::tag('i', '', [
                'class' => $card['icon'] . ' m51-health-icon',
                'aria-hidden' => 'true',
            ]);
            $html .= html_writer::div(
                html_writer::tag('strong', s((string)$card['value'])) .
                html_writer::tag(
                    'span',
                    s(get_string($card['label'], 'local_subscriptions'))
                ),
                'm51-health-copy'
            );
            $html .= html_writer::end_tag('article');
        }
        $html .= html_writer::end_div();

        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * @param array<string,CommerceStatisticsSeries> $series
     */
    public static function revenue(\renderer_base $output, array $series): string {
        if ($series === []) {
            return '';
        }

        $cards = '';
        foreach ($series as $currency => $seriesitem) {
            $currency = strtoupper((string)$currency);
            $flag = self::currency_flag($currency);
            $title = get_string(
                'commerce_m51_revenue_evolution',
                'local_subscriptions'
            ) . ' · ' . $flag . ' ' . $currency;

            $labels = $seriesitem->labels();
            $points = $seriesitem->points();
            $periodvalues = array_map(
                static fn($value): float => ((float)$value) / 100,
                $seriesitem->values()
            );

            $timestamps = [];
            foreach ($points as $index => $point) {
                $timestamps[] = (int)($point['timestamp'] ?? 0);
                if (!isset($labels[$index]) && isset($point['label'])) {
                    $labels[$index] = (string)$point['label'];
                }
            }

            $cumulativevalues = [];
            $running = 0.0;
            foreach ($periodvalues as $value) {
                $running += $value;
                $cumulativevalues[] = $running;
            }

            $selector = html_writer::select(
                [
                    'period' => get_string(
                        'commerce_m52_revenue_period',
                        'local_subscriptions'
                    ),
                    'cumulative' => get_string(
                        'commerce_m52_revenue_cumulative',
                        'local_subscriptions'
                    ),
                ],
                '',
                'period',
                false,
                [
                    'class' => 'form-select form-select-sm m52-revenue-mode',
                    'aria-label' => get_string(
                        'commerce_m52_revenue_display',
                        'local_subscriptions'
                    ),
                ]
            );

            $periodsvg = self::revenue_svg(
                $labels,
                $timestamps,
                $periodvalues,
                $currency,
                'period'
            );
            $cumulativesvg = self::revenue_svg(
                $labels,
                $timestamps,
                $cumulativevalues,
                $currency,
                'cumulative'
            );

            $body = html_writer::div(
                $periodsvg,
                'm52-revenue-chart m52-revenue-chart--period'
            )
            . html_writer::div(
                $cumulativesvg,
                'm52-revenue-chart m52-revenue-chart--cumulative d-none'
            );

            $cards .= html_writer::tag(
                'article',
                html_writer::div(
                    html_writer::tag(
                        'h4',
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-line-chart me-2',
                            'aria-hidden' => 'true',
                        ])
                        . s($title),
                        ['class' => 'm51-chart-title mb-0']
                    )
                    . $selector,
                    'm52-chart-heading'
                )
                . $body,
                [
                    'class' =>
                        'm51-chart-card m51-chart-card--revenue '
                        . 'm52-revenue-card m53-commerce-line-card',
                ]
            );
        }

        return html_writer::div(
            $cards,
            'm51-revenue-chart-grid'
        );
    }

    /**
     * Commerce dashboard-style line chart with gradient area and point markers.
     *
     * @param string[] $labels
     * @param int[] $timestamps
     * @param float[] $values Major currency units.
     */
    private static function revenue_svg(
        array $labels,
        array $timestamps,
        array $values,
        string $currency,
        string $mode
    ): string {
        if ($values === []) {
            return '';
        }

        $width = 760;
        $height = 310;
        $left = 72;
        $right = 22;
        $top = 18;
        $bottom = 48;
        $plotw = $width - $left - $right;
        $ploth = $height - $top - $bottom;

        $rawmax = max(1.0, max($values));
        $power = pow(10, floor(log10($rawmax)));
        $normalized = $rawmax / $power;
        $nice = $normalized <= 1
            ? 1
            : ($normalized <= 2 ? 2 : ($normalized <= 5 ? 5 : 10));
        $axismax = max(1.0, $nice * $power);
        $majorstep = $axismax / 4;

        $grid = [];
        $ylabels = [];
        for ($i = 0; $i <= 4; $i++) {
            $value = $majorstep * $i;
            $y = $top + $ploth - ($ploth * ($value / $axismax));
            $grid[] = html_writer::empty_tag('line', [
                'x1' => $left,
                'x2' => $width - $right,
                'y1' => round($y, 1),
                'y2' => round($y, 1),
                'class' => 'm53-commerce-line-grid',
            ]);
            $ylabels[] = html_writer::tag(
                'text',
                self::chart_money_axis($value, $currency),
                [
                    'x' => $left - 10,
                    'y' => round($y + 4, 1),
                    'class' => 'm53-commerce-line-axis-label',
                    'text-anchor' => 'end',
                ]
            );
        }

        $coords = [];
        $circles = [];
        $lastindex = max(1, count($values) - 1);
        foreach ($values as $index => $value) {
            $x = $left + ($plotw * ($index / $lastindex));
            $y = $top + $ploth - ($ploth * ($value / $axismax));
            $coords[] = [round($x, 1), round($y, 1)];

            $date = isset($timestamps[$index]) && $timestamps[$index] > 0
                ? userdate(
                    $timestamps[$index],
                    get_string('strftimedate', 'langconfig')
                )
                : (string)($labels[$index] ?? '');
            $tooltip = $date
                . ' · '
                . format_float((float)$value, 2)
                . ' '
                . $currency;

            $circles[] = html_writer::tag(
                'g',
                html_writer::tag('circle', '', [
                    'cx' => round($x, 1),
                    'cy' => round($y, 1),
                    'r' => 4.2,
                    'class' => 'm53-commerce-line-point',
                    'tabindex' => '0',
                    'aria-label' => $tooltip,
                ])
                . html_writer::tag('title', s($tooltip)),
                ['class' => 'm53-commerce-line-point-wrap']
            );
        }

        $linepoints = implode(
            ' ',
            array_map(
                static fn(array $coord): string =>
                    $coord[0] . ',' . $coord[1],
                $coords
            )
        );
        $basey = $top + $ploth;
        $area = $left
            . ','
            . $basey
            . ' '
            . $linepoints
            . ' '
            . ($width - $right)
            . ','
            . $basey;

        $gradientid = 'productRevenueGradient'
            . preg_replace('/[^A-Za-z0-9]/', '', $currency . $mode);
        $defs = '<defs><linearGradient id="' . $gradientid
            . '" x1="0" y1="0" x2="0" y2="1">'
            . '<stop offset="0%" stop-color="#f51b7b" stop-opacity="0.24"/>'
            . '<stop offset="100%" stop-color="#f51b7b" stop-opacity="0.015"/>'
            . '</linearGradient></defs>';

        $xlabels = [];
        $count = count($values);
        $indices = array_values(array_unique([
            0,
            (int)round(($count - 1) / 4),
            (int)round(($count - 1) / 2),
            (int)round(3 * ($count - 1) / 4),
            max(0, $count - 1),
        ]));
        foreach ($indices as $index) {
            if (!isset($values[$index])) {
                continue;
            }
            $x = $left + ($plotw * ($index / $lastindex));
            $label = isset($timestamps[$index]) && $timestamps[$index] > 0
                ? userdate(
                    $timestamps[$index],
                    get_string('strftimedateshort', 'langconfig')
                )
                : (string)($labels[$index] ?? '');
            $xlabels[] = html_writer::tag(
                'text',
                s($label),
                [
                    'x' => round($x, 1),
                    'y' => $height - 13,
                    'class' => 'm53-commerce-line-axis-label',
                    'text-anchor' => 'middle',
                ]
            );
        }

        $axes = html_writer::empty_tag('line', [
            'x1' => $left,
            'x2' => $left,
            'y1' => $top,
            'y2' => $basey,
            'class' => 'm53-commerce-line-axis',
        ])
        . html_writer::empty_tag('line', [
            'x1' => $left,
            'x2' => $width - $right,
            'y1' => $basey,
            'y2' => $basey,
            'class' => 'm53-commerce-line-axis',
        ]);

        $svgcontent = $defs
            . implode('', $grid)
            . $axes
            . implode('', $ylabels)
            . html_writer::empty_tag('polygon', [
                'points' => $area,
                'class' => 'm53-commerce-line-area',
                'fill' => 'url(#' . $gradientid . ')',
            ])
            . html_writer::empty_tag('polyline', [
                'points' => $linepoints,
                'class' => 'm53-commerce-line-path',
                'fill' => 'none',
            ])
            . implode('', $circles)
            . implode('', $xlabels);

        return html_writer::tag(
            'svg',
            $svgcontent,
            [
                'viewBox' => "0 0 {$width} {$height}",
                'class' => 'm53-commerce-line-svg',
                'role' => 'img',
                'aria-label' => get_string(
                    'commerce_m51_revenue_evolution',
                    'local_subscriptions'
                ) . ' · ' . $currency,
            ]
        );
    }

    private static function chart_money_axis(
        float $value,
        string $currency
    ): string {
        if ($value >= 1000000) {
            return format_float($value / 1000000, 1) . 'M ' . $currency;
        }
        if ($value >= 1000) {
            return format_float($value / 1000, 1) . 'k ' . $currency;
        }
        return format_float($value, 0) . ' ' . $currency;
    }

    /**
     * @param array<string,array<string,CommerceStatisticsSeries>> $dataset
     */
    public static function deliveries(\renderer_base $output, array $dataset): string {
        $all = [];
        foreach ($dataset as $kind => $seriesbycurrency) {
            foreach ($seriesbycurrency as $series) {
                foreach ($series->points() as $point) {
                    $all[$point['timestamp']]['label'] = $point['label'];
                    $all[$point['timestamp']][$kind] =
                        ($all[$point['timestamp']][$kind] ?? 0) + (int)$point['value'];
                }
            }
        }

        if ($all === []) {
            return '';
        }

        ksort($all);

        $labels = [];
        $paid = [];
        $free = [];
        $manual = [];
        foreach ($all as $row) {
            $labels[] = $row['label'];
            $paid[] = $row['paid'] ?? 0;
            $free[] = $row['free'] ?? 0;
            $manual[] = $row['manual'] ?? 0;
        }

        $chart = new \core\chart_bar();
        $title = get_string('commerce_m51_deliveries_evolution', 'local_subscriptions');
        $chart->set_labels($labels);
        if (method_exists($chart, 'set_stacked')) {
            $chart->set_stacked(true);
        }
        if (method_exists($chart, 'set_colors')) {
            $chart->set_colors([
                '#f51b7b',
                '#8b5cf6',
                '#f3a8ca',
            ]);
        }
        $chart->add_series(new \core\chart_series(
            get_string('commerce_m51_delivery_paid', 'local_subscriptions'),
            $paid
        ));
        $chart->add_series(new \core\chart_series(
            get_string('commerce_m51_delivery_free_order', 'local_subscriptions'),
            $free
        ));
        $chart->add_series(new \core\chart_series(
            get_string('commerce_m51_delivery_manual', 'local_subscriptions'),
            $manual
        ));

        return self::wrap(
            $output->render($chart),
            $title,
            'm51-chart-card--deliveries'
        );
    }

    public static function payment_pies(\renderer_base $output, array $payments): string {
        $cards = '';

        $definitions = [
            'paid' => [
                'label' => get_string('commerce_m51_payment_paid', 'local_subscriptions'),
                'class' => 'paid',
                'colour' => '#26a269',
            ],
            'pending' => [
                'label' => get_string('commerce_m51_payment_pending', 'local_subscriptions'),
                'class' => 'pending',
                'colour' => '#e7a725',
            ],
            'failed' => [
                'label' => get_string('commerce_m51_payment_failed', 'local_subscriptions'),
                'class' => 'failed',
                'colour' => '#dc4455',
            ],
            'cancelled' => [
                'label' => get_string('commerce_m51_payment_cancelled', 'local_subscriptions'),
                'class' => 'cancelled',
                'colour' => '#8d8792',
            ],
            'refunded' => [
                'label' => get_string('commerce_m51_payment_refunded', 'local_subscriptions'),
                'class' => 'refunded',
                'colour' => '#7853a7',
            ],
        ];

        foreach ($payments as $currency => $values) {
            $nonzero = [];
            $total = 0;

            foreach ($definitions as $key => $definition) {
                $count = max(0, (int)($values[$key] ?? 0));
                if ($count === 0) {
                    continue;
                }

                $nonzero[$key] = $definition + ['count' => $count];
                $total += $count;
            }

            if ($total === 0) {
                continue;
            }

            $stops = [];
            $legend = '';
            $tablerows = '';
            $cursor = 0.0;

            foreach ($nonzero as $item) {
                $percentage = ($item['count'] / $total) * 100;
                $startpercentage = $cursor;
                $cursor += $percentage;

                $stops[] = sprintf(
                    '%s %.4F%% %.4F%%',
                    $item['colour'],
                    $startpercentage,
                    $cursor
                );

                $percentageformatted = format_float($percentage, 1);

                $legend .= html_writer::start_tag('li', [
                    'class' => 'm51-payment-legend-item',
                ]);
                $legend .= html_writer::tag('span', '', [
                    'class' => 'm51-payment-legend-swatch m51-payment-legend-swatch--' . $item['class'],
                    'style' => 'background:' . $item['colour'],
                    'aria-hidden' => 'true',
                ]);
                $legend .= html_writer::start_div('m51-payment-legend-copy');
                $legend .= html_writer::tag(
                    'span',
                    s($item['label']),
                    ['class' => 'm51-payment-legend-label']
                );
                $legend .= html_writer::tag(
                    'strong',
                    s((string)$item['count']) . ' · ' . s($percentageformatted) . ' %',
                    ['class' => 'm51-payment-legend-value']
                );
                $legend .= html_writer::end_div();
                $legend .= html_writer::end_tag('li');

                $tablerows .= html_writer::tag(
                    'tr',
                    html_writer::tag('td', s($item['label'])) .
                    html_writer::tag('td', s((string)$item['count']), ['class' => 'text-end']) .
                    html_writer::tag('td', s($percentageformatted) . ' %', ['class' => 'text-end'])
                );
            }

            $title = get_string('commerce_m51_payment_distribution', 'local_subscriptions') . ' · ' . $currency;
            $flag = self::currency_flag((string)$currency);

            $pie = html_writer::tag('div', '', [
                'class' => 'm51-payment-pie',
                'style' => 'background:conic-gradient(' . implode(',', $stops) . ')',
                'role' => 'img',
                'aria-label' => $title,
            ]);

            $visual = html_writer::div(
                html_writer::div($pie, 'm51-payment-pie-wrap') .
                html_writer::tag('ul', $legend, ['class' => 'm51-payment-legend']),
                'm51-payment-visual'
            );

            $table = html_writer::tag(
                'table',
                html_writer::tag(
                    'tbody',
                    $tablerows
                ),
                ['class' => 'table table-sm mb-0 m51-payment-data-table']
            );

            $details = html_writer::tag(
                'details',
                html_writer::tag(
                    'summary',
                    s(get_string('commerce_m51_show_chart_data', 'local_subscriptions'))
                ) .
                html_writer::div($table, 'm51-payment-data'),
                ['class' => 'm51-payment-details']
            );

            $cards .= html_writer::tag(
                'article',
                html_writer::tag(
                    'h4',
                    html_writer::tag(
                        'span',
                        $flag . ' ' . s((string)$currency),
                        ['class' => 'm51-payment-title-currency']
                    ) .
                    html_writer::tag(
                        'span',
                        s(get_string('commerce_m51_payment_distribution', 'local_subscriptions')),
                        ['class' => 'm51-payment-title-label']
                    ),
                    ['class' => 'm51-chart-title m51-payment-title']
                ) .
                $visual .
                $details,
                ['class' => 'm51-chart-card m51-chart-card--payment']
            );
        }

        if ($cards === '') {
            return '';
        }

        return html_writer::div($cards, 'm51-payment-chart-grid');
    }

    public static function insights(array $snapshot, ?array $previous, callable $money): string {
        $global = $snapshot['global'];
        $previousglobal = $previous['global'] ?? [];

        $rate = (float)($global['paymentsuccessrate'] ?? 0);
        $oldrate = $previous !== null
            ? (float)($previousglobal['paymentsuccessrate'] ?? 0)
            : null;

        $html = html_writer::start_div('m52-insights');
        $html .= html_writer::start_div('m52-kpi-row');

        foreach ($snapshot['currencies'] as $currency => $row) {
            $average = (int)($row['averageorderminor'] ?? 0);
            $oldaverage = $previous !== null
                ? (int)($previous['currencies'][$currency]['averageorderminor'] ?? 0)
                : null;

            $html .= html_writer::tag(
                'article',
                html_writer::tag(
                    'span',
                    self::currency_flag((string)$currency) . ' ' . s((string)$currency),
                    ['class' => 'm52-mini-eyebrow']
                ) .
                html_writer::div(
                    html_writer::tag('strong', s($money($average, (string)$currency))) .
                    ($oldaverage !== null ? self::trend($average, $oldaverage) : ''),
                    'm52-mini-value'
                ) .
                html_writer::div(
                    s(get_string('commerce_m52_average_order', 'local_subscriptions')),
                    'm52-mini-label'
                ),
                ['class' => 'm52-mini-card']
            );
        }

        $html .= html_writer::tag(
            'article',
            html_writer::tag(
                'span',
                get_string('commerce_m52_payment_quality', 'local_subscriptions'),
                ['class' => 'm52-mini-eyebrow']
            ) .
            html_writer::div(
                html_writer::tag('strong', s(format_float($rate, 1)) . ' %') .
                ($oldrate !== null ? self::trend($rate, $oldrate) : ''),
                'm52-mini-value'
            ) .
            html_writer::div(
                s(get_string('commerce_m52_success_rate', 'local_subscriptions')),
                'm52-mini-label'
            ),
            ['class' => 'm52-mini-card']
        );

        $html .= html_writer::end_div();
        $html .= html_writer::end_div();

        return $html;
    }

    /**
     * Premium product-level payment journey.
     *
     * The branches are based on mutually-exclusive current/latest payment states
     * already calculated in the product snapshot. Deliveries remain outside this
     * tree because units delivered can legitimately exceed the number of orders.
     */
    public static function payment_journey(array $snapshot): string {
        $global = $snapshot['global'] ?? [];

        $attempts = (int)($global['paymentattempts'] ?? 0);
        $paid = (int)($global['successfulpayments'] ?? 0);
        $pending = (int)($global['pending'] ?? 0);
        $failed = (int)($global['failed'] ?? 0);
        $cancelled = (int)($global['cancelled'] ?? 0);
        $refunded = (int)($global['refunded'] ?? 0);
        $notcompleted = $failed + $cancelled;

        if ($attempts === 0 && $paid === 0 && $pending === 0 && $notcompleted === 0 && $refunded === 0) {
            return '';
        }

        $html = html_writer::start_tag('section', [
            'class' => 'm54-panel m54-payment-tree-panel',
        ]);

        $html .= html_writer::tag(
            'h3',
            s(get_string('commerce_m53_payment_journey', 'local_subscriptions')) .
            html_writer::tag('span', 'ⓘ', [
                'class' => 'm54-tree-info',
                'aria-hidden' => 'true',
            ]),
            ['class' => 'm54-panel-title m54-payment-tree-title']
        );

        $html .= html_writer::div(
            s(get_string('commerce_m53_payment_journey_help', 'local_subscriptions')),
            'm54-panel-help'
        );

        $html .= html_writer::start_div('m54-payment-tree m54-payment-tree--premium');

        // Root.
        $html .= self::product_tree_node(
            'root',
            $attempts,
            get_string('commerce_m53_payment_attempts', 'local_subscriptions'),
            100.0,
            'fa-solid fa-cart-shopping'
        );

        // Root -> junction.
        $html .= html_writer::div(
            html_writer::span('', 'm54-tree-trunk-line') .
            html_writer::span('', 'm54-tree-junction'),
            'm54-tree-main-connector'
        );

        // Main mutually-exclusive branches.
        $html .= html_writer::start_div('m54-tree-branches m54-tree-branches--premium');
        $branches = [
            ['paid', $paid, 'commerce_m51_payment_paid', 'fa-solid fa-check'],
            ['pending', $pending, 'commerce_m51_payment_pending', 'fa-regular fa-clock'],
            ['notcompleted', $notcompleted, 'commerce_m53_payment_not_completed', 'fa-solid fa-xmark'],
            ['refunded', $refunded, 'commerce_m51_payment_refunded', 'fa-solid fa-arrow-rotate-left'],
        ];

        foreach ($branches as [$class, $value, $label, $icon]) {
            $percentage = $attempts > 0 ? ($value / $attempts) * 100 : 0.0;
            $html .= html_writer::div(
                html_writer::span('', 'm54-tree-branch-line m54-tree-branch-line--' . $class) .
                self::product_tree_node(
                    $class,
                    $value,
                    get_string($label, 'local_subscriptions'),
                    $percentage,
                    $icon
                ),
                'm54-tree-branch m54-tree-branch--' . $class
            );
        }
        $html .= html_writer::end_div();

        // Non-completed -> failed / cancelled.
        if ($notcompleted > 0) {
            $html .= html_writer::start_div('m54-tree-failure-subtree');
            $html .= html_writer::div(
                html_writer::span('', 'm54-tree-failure-trunk') .
                html_writer::span('', 'm54-tree-failure-junction'),
                'm54-tree-failure-connector'
            );

            $html .= html_writer::start_div('m54-tree-failure-branches');

            $failedpercentage = $attempts > 0 ? ($failed / $attempts) * 100 : 0.0;
            $cancelledpercentage = $attempts > 0 ? ($cancelled / $attempts) * 100 : 0.0;

            $html .= html_writer::div(
                html_writer::span('', 'm54-tree-failure-line') .
                self::product_tree_node(
                    'failed',
                    $failed,
                    get_string('commerce_m51_payment_failed', 'local_subscriptions'),
                    $failedpercentage,
                    'fa-solid fa-circle-xmark'
                ),
                'm54-tree-failure-branch'
            );

            $html .= html_writer::div(
                html_writer::span('', 'm54-tree-failure-line') .
                self::product_tree_node(
                    'cancelled',
                    $cancelled,
                    get_string('commerce_m51_payment_cancelled', 'local_subscriptions'),
                    $cancelledpercentage,
                    'fa-solid fa-ban'
                ),
                'm54-tree-failure-branch'
            );

            $html .= html_writer::end_div();
            $html .= html_writer::end_div();
        }

        // Business summary.
        $conversion = $attempts > 0 ? ($paid / $attempts) * 100 : 0.0;
        $notpaid = max(0, $attempts - $paid);
        $notpaidrate = $attempts > 0 ? ($notpaid / $attempts) * 100 : 0.0;

        $html .= html_writer::start_div('m54-payment-tree-summary');

        $html .= html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => 'fa-solid fa-chart-column',
                    'aria-hidden' => 'true',
                ]),
                'm54-tree-summary-icon'
            ) .
            html_writer::div(
                html_writer::tag(
                    'span',
                    s(get_string('commerce_m53_conversion', 'local_subscriptions'))
                ) .
                html_writer::tag('strong', s(format_float($conversion, 1)) . ' %'),
                'm54-tree-summary-metric'
            ) .
            html_writer::div(
                s(get_string(
                    'commerce_m53_global_conversion',
                    'local_subscriptions',
                    (object)[
                        'paid' => $paid,
                        'attempts' => $attempts,
                        'rate' => format_float($conversion, 1),
                    ]
                )),
                'm54-tree-summary-copy'
            ),
            'm54-tree-summary-block m54-tree-summary-block--conversion'
        );

        $html .= html_writer::div('', 'm54-tree-summary-divider');

        $html .= html_writer::div(
            html_writer::div(
                html_writer::tag(
                    'span',
                    s(get_string('commerce_m53_payment_not_completed', 'local_subscriptions'))
                ) .
                html_writer::tag('strong', s(format_float($notpaidrate, 1)) . ' %'),
                'm54-tree-summary-metric'
            ) .
            html_writer::div(
                s((string)$notpaid) . ' / ' . s((string)$attempts),
                'm54-tree-summary-copy'
            ),
            'm54-tree-summary-block m54-tree-summary-block--abandonment'
        );

        $html .= html_writer::end_div();

        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('section');

        return $html;
    }

    private static function product_tree_node(
        string $class,
        int $value,
        string $label,
        float $percentage,
        string $icon
    ): string {
        return html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => $icon,
                    'aria-hidden' => 'true',
                ]),
                'm54-tree-node-icon'
            ) .
            html_writer::div(
                html_writer::tag('strong', s((string)$value)) .
                html_writer::tag('span', s($label)) .
                html_writer::tag('small', s(format_float($percentage, 1)) . ' %'),
                'm54-tree-node-copy'
            ),
            'm54-tree-node m54-tree-node--premium m54-tree-node--' . $class
        );
    }

    private static function trend(int|float $current, int|float $previous): string {
        // Numeric zero can arrive as either int(0) or float(0.0).
        // A strict comparison against int(0) lets 0.0 through and then divides by zero.
        if ((float)$previous === 0.0) {
            if ($current === 0) {
                return html_writer::tag(
                    'span',
                    '0 %',
                    ['class' => 'm51-trend m51-trend--flat']
                );
            }

            return html_writer::tag(
                'span',
                html_writer::tag('i', '', [
                    'class' => 'fa-solid fa-arrow-up',
                    'aria-hidden' => 'true',
                ]) . ' ' . s(get_string('commerce_m51_trend_new', 'local_subscriptions')),
                ['class' => 'm51-trend m51-trend--up']
            );
        }

        $percentage = (($current - $previous) / $previous) * 100;
        $rounded = (int)round(abs($percentage));

        if (abs($percentage) < 0.5) {
            return html_writer::tag(
                'span',
                '0 %',
                ['class' => 'm51-trend m51-trend--flat']
            );
        }

        $up = $percentage > 0;
        return html_writer::tag(
            'span',
            html_writer::tag('i', '', [
                'class' => $up ? 'fa-solid fa-arrow-up' : 'fa-solid fa-arrow-down',
                'aria-hidden' => 'true',
            ]) . ' ' . ($up ? '+' : '−') . $rounded . ' %',
            ['class' => 'm51-trend ' . ($up ? 'm51-trend--up' : 'm51-trend--down')]
        );
    }

    private static function currency_flag(string $currency): string {
        return match (strtoupper($currency)) {
            'EUR' => '🇪🇺',
            'RUB' => '🇷🇺',
            default => '🌐',
        };
    }

    private static function wrap(string $chart, string $title, string $modifier = ''): string {
        return html_writer::tag(
            'article',
            html_writer::tag('h4', s($title), ['class' => 'm51-chart-title']) .
            html_writer::div($chart, 'm51-chart-body crm-commerce-statistics-chart'),
            ['class' => trim('m51-chart-card ' . $modifier)]
        );
    }
}
