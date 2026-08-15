<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\subscription_config;
use moodle_url;

/** Renders the Commerce overview as an operational dashboard. */
final class CommerceDashboardRenderer {
    /** @param array<string,mixed> $data */
    public static function render(array $data): string {
        return html_writer::div(
            self::period_controls($data['period'] ?? [], (string)($data['currency'] ?? 'EUR'))
            . self::metrics($data)
            . html_writer::div(
                self::chart($data['series'] ?? [], (string)($data['currency'] ?? 'EUR'), $data['currencies'] ?? [], $data['period'] ?? [])
                . self::latest_sales($data['latestsales'] ?? [])
                . self::quick_actions(),
                'crm-commerce-dashboard-grid crm-commerce-dashboard-grid-main'
            )
            . html_writer::div(
                self::alerts($data)
                . self::top_products($data['topproducts'] ?? [], (string)($data['currency'] ?? 'EUR'), $data['currencies'] ?? [], $data['period'] ?? [])
                . self::product_showroom_summary($data['products'] ?? [], $data['showrooms'] ?? []),
                'crm-commerce-dashboard-grid crm-commerce-dashboard-grid-bottom'
            ),
            'crm-commerce-dashboard'
        );
    }

    /** @param array<string,mixed> $period */
    private static function period_controls(array $period, string $currency): string {
        $mode = (string)($period['mode'] ?? '30');
        $start = (int)($period['start'] ?? time());
        $end = (int)($period['end'] ?? time());
        $dateformat = get_string('strftimedate', 'langconfig');
        $range = get_string(
            'commerce_dashboard_period_summary',
            'local_subscriptions',
            (object)[
                'start' => userdate($start, $dateformat),
                'end' => userdate($end, $dateformat),
            ]
        );
        $options = [
            'today' => 'commerce_dashboard_period_today',
            '7' => 'commerce_dashboard_period_7',
            '30' => 'commerce_dashboard_period_30',
            '90' => 'commerce_dashboard_period_90',
            '365' => 'commerce_dashboard_period_365',
        ];
        $links = [];
        foreach ($options as $value => $stringkey) {
            $links[] = html_writer::link(
                new moodle_url(subscription_config::admin_commerce_page(), ['period' => $value, 'currency' => $currency]),
                get_string($stringkey, 'local_subscriptions'),
                ['class' => 'crm-commerce-dashboard-period-option' . ($value === $mode ? ' is-active' : '')]
            );
        }
        $lang = current_language();
        $custom = html_writer::start_tag('form', ['method' => 'get', 'class' => 'crm-commerce-dashboard-custom-period'])
            . html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'period', 'value' => 'custom'])
            . html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'currency', 'value' => $currency])
            . html_writer::empty_tag('input', [
                'type' => 'date', 'name' => 'from', 'required' => 'required',
                'class' => 'form-control form-control-sm', 'lang' => $lang,
            ])
            . html_writer::empty_tag('input', [
                'type' => 'date', 'name' => 'to', 'required' => 'required',
                'class' => 'form-control form-control-sm', 'lang' => $lang,
            ])
            . html_writer::tag('button', get_string('commerce_dashboard_period_apply', 'local_subscriptions'), [
                'type' => 'submit', 'class' => 'btn btn-sm btn-outline-primary',
            ])
            . html_writer::end_tag('form');
        $details = html_writer::tag('details',
            html_writer::tag('summary',
                html_writer::tag('i', '', ['class' => 'fa fa-calendar', 'aria-hidden' => 'true'])
                . html_writer::span(get_string('commerce_dashboard_period_label', 'local_subscriptions'), 'crm-commerce-dashboard-period-caption'),
                ['class' => 'crm-commerce-dashboard-period-summary'])
            . html_writer::div(implode('', $links) . $custom, 'crm-commerce-dashboard-period-menu'),
            ['class' => 'crm-commerce-dashboard-period']);
        return html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', ['class' => 'fa fa-calendar-check-o', 'aria-hidden' => 'true'])
                . html_writer::span(s($range)),
                'crm-commerce-dashboard-period-range'
            )
            . $details,
            'crm-commerce-dashboard-period-bar'
        );
    }

    /** @param array<string,mixed> $data */
    private static function metrics(array $data): string {
        $currency = (string)($data['currency'] ?? 'EUR');
        $cards = [];
        $cards[] = self::revenue_card($data['revenue'] ?? [], $data['revenueprevious'] ?? [], $currency, $data['currencies'] ?? [], $data['period'] ?? []);
        $status = $data['orderstatus'] ?? ['total' => 0, 'pending' => 0, 'failed' => 0];
        $cards[] = self::metric_card('fa-shopping-cart', get_string('commerce_dashboard_orders_paid', 'local_subscriptions'),
            format_float((int)($data['orders'] ?? 0), 0), get_string('commerce_dashboard_orders_status_foot', 'local_subscriptions', (object)[
                'total' => (int)($status['total'] ?? 0), 'pending' => (int)($status['pending'] ?? 0), 'failed' => (int)($status['failed'] ?? 0)]));
        $cards[] = self::metric_card('fa-envelope', get_string('commerce_dashboard_mails_sent', 'local_subscriptions'),
            format_float((int)($data['mailssent'] ?? 0), 0), self::number_trend((int)($data['mailssent'] ?? 0), (int)($data['mailssentprevious'] ?? 0)));
        $cards[] = self::metric_card('fa-tag', get_string('commerce_dashboard_active_offers', 'local_subscriptions'),
            format_float((int)($data['activeoffers'] ?? 0), 0), get_string('commerce_dashboard_expiring_7d', 'local_subscriptions', (int)($data['expiringoffers'] ?? 0)), 'attention');
        $cards[] = self::metric_card('fa-users', get_string('commerce_dashboard_new_customers', 'local_subscriptions'),
            format_float((int)($data['customers'] ?? 0), 0), self::number_trend((int)($data['customers'] ?? 0), (int)($data['customersprevious'] ?? 0)));
        $cards[] = self::metric_card('fa-line-chart', get_string('commerce_dashboard_conversion_rate', 'local_subscriptions'),
            format_float((float)($data['conversion'] ?? 0.0), 1) . '%', self::float_trend((float)($data['conversion'] ?? 0.0), (float)($data['conversionprevious'] ?? 0.0)), 'success');
        return html_writer::div(implode('', $cards), 'crm-commerce-dashboard-metrics');
    }

    /** @param array<string,int> $amounts @param array<string,int> $previous @param string[] $currencies @param array<string,mixed> $period */
    private static function revenue_card(array $amounts, array $previous, string $currency, array $currencies, array $period): string {
        $value = (int)($amounts[$currency] ?? 0);
        $previousvalue = (int)($previous[$currency] ?? 0);
        return html_writer::div(
            self::currency_selector($currency, $currencies, $period, 'crm-commerce-dashboard-metric-currency')
            . html_writer::div(html_writer::tag('i', '', ['class' => 'fa fa-shopping-bag', 'aria-hidden' => 'true']), 'crm-commerce-dashboard-metric-icon')
            . html_writer::div(
                html_writer::div(get_string('commerce_dashboard_sales_short', 'local_subscriptions'), 'crm-commerce-dashboard-metric-label')
                . html_writer::div(s(self::money_amount_only($value)), 'crm-commerce-dashboard-metric-value')
                . html_writer::div(self::number_trend($value, $previousvalue), 'crm-commerce-dashboard-metric-foot'),
                'crm-commerce-dashboard-metric-copy'),
            'crm-commerce-dashboard-card crm-commerce-dashboard-metric crm-commerce-dashboard-revenue-card'
        );
    }

    private static function metric_card(string $icon, string $label, string $value, string $foot, string $intent = ''): string {
        return html_writer::div(
            html_writer::div(html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']), 'crm-commerce-dashboard-metric-icon' . ($intent !== '' ? ' is-' . $intent : ''))
            . html_writer::div(html_writer::div(s($label), 'crm-commerce-dashboard-metric-label')
                . html_writer::div(s($value), 'crm-commerce-dashboard-metric-value')
                . html_writer::div($foot, 'crm-commerce-dashboard-metric-foot'), 'crm-commerce-dashboard-metric-copy'),
            'crm-commerce-dashboard-card crm-commerce-dashboard-metric');
    }

    /** @param array<string,array<int,array{timestamp:int,value:int}>> $series @param string[] $currencies @param array<string,mixed> $period */
    private static function chart(array $series, string $currency, array $currencies, array $period): string {
        $points = $series[$currency] ?? [];
        $toolbar = self::currency_selector($currency, $currencies, $period, 'crm-commerce-dashboard-panel-currency');
        if ($points === []) {
            return self::panel(
                get_string('commerce_dashboard_sales_period', 'local_subscriptions'),
                $toolbar . html_writer::div(
                    get_string('commerce_dashboard_no_sales_data', 'local_subscriptions'),
                    'crm-commerce-dashboard-empty'
                ),
                '',
                'fa-line-chart'
            );
        }

        $width = 680;
        $height = 270;
        $left = 72;
        $right = 18;
        $top = 18;
        $bottom = 46;
        $plotw = $width - $left - $right;
        $ploth = $height - $top - $bottom;
        $rawmax = max(1, max(array_map(static fn(array $p): int => (int)$p['value'], $points)));
        [$axismax, $majorstep] = self::nice_axis($rawmax, 4);
        $minorstep = max(1, (int)round($majorstep / 2));

        $grid = [];
        $ylabels = [];
        for ($value = 0; $value <= $axismax; $value += $minorstep) {
            $y = $top + $ploth - ($ploth * ($value / $axismax));
            $major = $value % $majorstep === 0;
            $grid[] = html_writer::empty_tag('line', [
                'x1' => $left,
                'x2' => $width - $right,
                'y1' => round($y, 1),
                'y2' => round($y, 1),
                'class' => 'crm-commerce-dashboard-chart-grid' . ($major ? ' is-major' : ' is-minor'),
            ]);
            if ($major) {
                $ylabels[] = html_writer::tag('text', s(self::axis_money($value, $currency)), [
                    'x' => $left - 9,
                    'y' => round($y + 4, 1),
                    'class' => 'crm-commerce-dashboard-chart-axis-label',
                    'text-anchor' => 'end',
                ]);
            }
        }

        $coords = [];
        $circles = [];
        $count = max(1, count($points) - 1);
        foreach ($points as $i => $point) {
            $x = $left + ($plotw * ($i / $count));
            $y = $top + $ploth - ($ploth * ((int)$point['value'] / $axismax));
            $coords[] = [round($x, 1), round($y, 1)];
            $tooltip = userdate((int)$point['timestamp'], get_string('strftimedate', 'langconfig'))
                . ' · ' . CommercePurchasePresentation::money((int)$point['value'], $currency);
            $circles[] = html_writer::tag(
                'g',
                html_writer::tag('circle', '', [
                    'cx' => round($x, 1),
                    'cy' => round($y, 1),
                    'r' => 4,
                    'class' => 'crm-commerce-dashboard-chart-point',
                    'tabindex' => '0',
                    'aria-label' => $tooltip,
                ])
                . html_writer::tag('title', s($tooltip)),
                ['class' => 'crm-commerce-dashboard-chart-point-wrap']
            );
        }

        $linepoints = implode(' ', array_map(static fn(array $c): string => $c[0] . ',' . $c[1], $coords));
        $basey = $top + $ploth;
        $area = $left . ',' . $basey . ' ' . $linepoints . ' ' . ($width - $right) . ',' . $basey;
        $defs = '<defs><linearGradient id="commerceDashGradient" x1="0" y1="0" x2="0" y2="1">'
            . '<stop offset="0%" stop-color="currentColor" stop-opacity="0.28"/>'
            . '<stop offset="100%" stop-color="currentColor" stop-opacity="0.02"/>'
            . '</linearGradient></defs>';

        $xlabels = [];
        $indices = array_values(array_unique([
            0,
            (int)round($count / 4),
            (int)round($count / 2),
            (int)round(3 * $count / 4),
            $count,
        ]));
        foreach ($indices as $idx) {
            $point = $points[$idx] ?? end($points);
            $x = $left + ($plotw * ($idx / $count));
            $xlabels[] = html_writer::tag('text', s(userdate(
                (int)$point['timestamp'],
                get_string('strftimedateshort', 'langconfig')
            )), [
                'x' => round($x, 1),
                'y' => $height - 12,
                'class' => 'crm-commerce-dashboard-chart-axis-label',
                'text-anchor' => 'middle',
            ]);
        }

        $axes = html_writer::empty_tag('line', [
            'x1' => $left, 'x2' => $left, 'y1' => $top, 'y2' => $basey,
            'class' => 'crm-commerce-dashboard-chart-axis',
        ])
        . html_writer::empty_tag('line', [
            'x1' => $left, 'x2' => $width - $right, 'y1' => $basey, 'y2' => $basey,
            'class' => 'crm-commerce-dashboard-chart-axis',
        ]);

        $svgcontent = $defs
            . implode('', $grid)
            . $axes
            . implode('', $ylabels)
            . html_writer::empty_tag('polygon', ['points' => $area, 'class' => 'crm-commerce-dashboard-chart-area'])
            . html_writer::empty_tag('polyline', ['points' => $linepoints, 'class' => 'crm-commerce-dashboard-chart-line', 'fill' => 'none'])
            . implode('', $circles)
            . implode('', $xlabels);

        $svg = html_writer::tag('svg', $svgcontent, [
            'viewBox' => "0 0 {$width} {$height}",
            'class' => 'crm-commerce-dashboard-chart-svg',
            'role' => 'img',
            'aria-label' => get_string('commerce_dashboard_sales_chart', 'local_subscriptions'),
        ]);
        return self::panel(
            get_string('commerce_dashboard_sales_period', 'local_subscriptions'),
            $toolbar . $svg,
            '',
            'fa-line-chart'
        );
    }

    /** @param array<int,array<string,mixed>> $sales */
    private static function latest_sales(array $sales): string {
        global $OUTPUT;
        $rows = [];
        foreach ($sales as $sale) {
            $purchaseurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['id' => (int)$sale['id']]);
            $profileurl = !empty($sale['userid']) ? new moodle_url('/local/subscriptions/admin/users/view.php', ['id' => (int)$sale['userid']]) : null;
            $producturl = !empty($sale['productsku']) ? new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', ['sku' => (string)$sale['productsku']]) : null;
            if (!empty($sale['user']) && is_object($sale['user'])) {
                $avatar = html_writer::div($OUTPUT->user_picture($sale['user'], ['size' => 42, 'link' => false]), 'crm-commerce-dashboard-sale-avatar');
            } else {
                $avatar = html_writer::div(s(self::initials((string)$sale['customer'])), 'crm-commerce-dashboard-sale-avatar is-initials');
            }
            $customer = $profileurl ? html_writer::link($profileurl, s((string)$sale['customer']), ['class' => 'crm-commerce-dashboard-sale-customer'])
                : html_writer::span(s((string)$sale['customer']), 'crm-commerce-dashboard-sale-customer');
            $product = $producturl ? html_writer::link($producturl, s((string)$sale['product']), ['class' => 'crm-commerce-dashboard-sale-product'])
                : html_writer::span(s((string)$sale['product']), 'crm-commerce-dashboard-sale-product');
            $rows[] = html_writer::div($avatar
                . html_writer::div(html_writer::div($customer, 'crm-commerce-dashboard-sale-customer-line') . html_writer::div($product, 'crm-commerce-dashboard-sale-product-line'), 'crm-commerce-dashboard-sale-main')
                . html_writer::div(html_writer::link($purchaseurl, s(CommercePurchasePresentation::money((int)$sale['totalminor'], (string)$sale['currency'])), ['class' => 'crm-commerce-dashboard-sale-amount'])
                    . html_writer::span(self::relative_time((int)$sale['timecreated']), 'crm-commerce-dashboard-sale-time'), 'crm-commerce-dashboard-sale-meta'),
                'crm-commerce-dashboard-sale-row');
        }
        $content = $rows === [] ? html_writer::div(get_string('commerce_dashboard_no_sales_data', 'local_subscriptions'), 'crm-commerce-dashboard-empty') : implode('', $rows);
        $content .= html_writer::div(html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php'), get_string('commerce_dashboard_view_all_sales', 'local_subscriptions') . ' →'), 'crm-commerce-dashboard-panel-footer');
        return self::panel(get_string('commerce_dashboard_latest_sales', 'local_subscriptions'), $content, '', 'fa-shopping-bag');
    }

    private static function quick_actions(): string {
        $actions = [
            ['fa-cube', 'commerce_dashboard_action_create_product', '/local/subscriptions/admin/commerce/products/edit.php', Capabilities::MANAGE_CONFIGURATION],
            ['fa-tag', 'commerce_dashboard_action_create_offer', '/local/subscriptions/admin/commerce/personal-offers/create.php', Capabilities::VIEW_PAYMENTS],
            ['fa-envelope', 'commerce_dashboard_action_mail', '/local/subscriptions/admin/commerce/mail/index.php', Capabilities::VIEW_PAYMENTS],
            ['fa-upload', 'commerce_dashboard_action_import', subscription_config::import_csv_page(), Capabilities::MANAGE_SUBSCRIPTIONS],
            ['fa-hourglass-half', 'commerce_dashboard_action_unfinished', '/local/subscriptions/admin/commerce/unfinished-checkouts/index.php', Capabilities::VIEW_PAYMENTS],
            ['fa-compress', 'commerce_dashboard_action_merge_accounts', '/local/subscriptions/admin/commerce/customer-identities/merge.php', Capabilities::MANAGE_USERS],
            ['fa-bar-chart', 'commerce_dashboard_action_statistics', '/local/subscriptions/admin/commerce/statistics/index.php', Capabilities::VIEW_STATISTICS],
        ];
        $links = [];
        foreach ($actions as [$icon, $stringkey, $path, $capability]) {
            if (!AdminSecurity::can($capability)) { continue; }
            $links[] = html_writer::link(new moodle_url($path), html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true'])
                . html_writer::span(get_string($stringkey, 'local_subscriptions'), 'crm-commerce-dashboard-quick-label')
                . html_writer::span('›', 'crm-commerce-dashboard-quick-arrow', ['aria-hidden' => 'true']), ['class' => 'crm-commerce-dashboard-quick-link']);
        }
        return self::panel(get_string('commerce_dashboard_quick_actions', 'local_subscriptions'), implode('', $links), '', 'fa-bolt');
    }

    /** @param array<string,mixed> $data */
    private static function alerts(array $data): string {
        $alerts = [];
        if (($n = (int)($data['unfinishedcheckouts'] ?? 0)) > 0) {
            $alerts[] = self::alert('fa-hourglass-half', get_string('commerce_dashboard_alert_checkouts', 'local_subscriptions', $n), new moodle_url('/local/subscriptions/admin/commerce/unfinished-checkouts/index.php'), 'warning');
        }
        $status = $data['orderstatus'] ?? [];
        if (($n = (int)($status['pending'] ?? 0)) > 0) {
            $alerts[] = self::alert('fa-clock-o', get_string('commerce_dashboard_alert_pending_orders', 'local_subscriptions', $n), new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php'), 'warning');
        }
        if (($n = (int)($status['failed'] ?? 0)) > 0) {
            $alerts[] = self::alert('fa-times-circle', get_string('commerce_dashboard_alert_failed_orders', 'local_subscriptions', $n), new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php'), 'danger');
        }
        if (($n = (int)($data['grantproblems'] ?? 0)) > 0) {
            $alerts[] = self::alert('fa-key', get_string('commerce_dashboard_alert_grants', 'local_subscriptions', $n), new moodle_url('/local/subscriptions/admin/commerce/grants/index.php'), 'danger');
        }
        if (($n = (int)($data['mailpending'] ?? 0)) > 0) {
            $alerts[] = self::alert('fa-envelope-o', get_string('commerce_dashboard_alert_mail_pending', 'local_subscriptions', $n), new moodle_url('/local/subscriptions/admin/commerce/mail/index.php'), 'warning');
        }
        if (($n = (int)($data['mailfailed'] ?? 0)) > 0) {
            $alerts[] = self::alert('fa-envelope', get_string('commerce_dashboard_alert_mail', 'local_subscriptions', $n), new moodle_url('/local/subscriptions/admin/commerce/mail/index.php'), 'danger');
        }
        if (($n = (int)($data['expiringoffers'] ?? 0)) > 0) {
            $alerts[] = self::alert('fa-tag', get_string('commerce_dashboard_alert_offers', 'local_subscriptions', $n), new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php'), 'attention');
        }
        if ($alerts === []) {
            $alerts[] = html_writer::div(html_writer::tag('i', '', ['class' => 'fa fa-check-circle', 'aria-hidden' => 'true']) . ' ' . get_string('commerce_dashboard_no_alerts', 'local_subscriptions'), 'crm-commerce-dashboard-all-clear');
        }
        return self::panel(get_string('commerce_dashboard_alerts', 'local_subscriptions'), implode('', $alerts), '', 'fa-exclamation-triangle');
    }

    private static function alert(string $icon, string $label, moodle_url $url, string $intent): string {
        return html_writer::link($url, html_writer::span(html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']), 'crm-commerce-dashboard-alert-icon is-' . $intent)
            . html_writer::span(s($label), 'crm-commerce-dashboard-alert-label') . html_writer::span('›', 'crm-commerce-dashboard-alert-arrow', ['aria-hidden' => 'true']), ['class' => 'crm-commerce-dashboard-alert-row']);
    }

    /** @param array<string,array<int,array<string,mixed>>> $groups @param string[] $currencies @param array<string,mixed> $period */
    private static function top_products(array $groups, string $currency, array $currencies, array $period): string {
        $products = $groups[$currency] ?? [];
        $medals = ['🥇', '🥈', '🥉', '4', '5'];
        $rows = [];
        foreach ($products as $index => $product) {
            $label = s((string)$product['label']);
            if (!empty($product['reference'])) {
                $label = html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', ['sku' => (string)$product['reference']]), $label);
            }
            $rankclass = $index < 5 ? ' is-rank-' . ($index + 1) : '';
            $rows[] = html_writer::div(html_writer::span($medals[$index] ?? '•', 'crm-commerce-dashboard-product-medal')
                . html_writer::span($label, 'crm-commerce-dashboard-product-label')
                . html_writer::span(s(CommercePurchasePresentation::money((int)$product['revenueminor'], $currency)), 'crm-commerce-dashboard-product-revenue'),
                'crm-commerce-dashboard-product-row' . $rankclass);
        }
        $html = self::currency_selector($currency, $currencies, $period, 'crm-commerce-dashboard-panel-currency')
            . ($rows === [] ? html_writer::div(get_string('commerce_dashboard_no_sales_data', 'local_subscriptions'), 'crm-commerce-dashboard-empty') : implode('', $rows));
        $html .= html_writer::div(html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/statistics/index.php'), get_string('commerce_dashboard_view_statistics', 'local_subscriptions') . ' →'), 'crm-commerce-dashboard-panel-footer');
        return self::panel(get_string('commerce_dashboard_top_products_period', 'local_subscriptions'), $html, '', 'fa-trophy');
    }

    /** @param array<string,int> $products @param array<string,int> $showrooms */
    private static function product_showroom_summary(array $products, array $showrooms): string {
        $productitems = [
            ['total', 'commerce_dashboard_products_total'],
            ['bundle', 'commerce_dashboard_products_bundles'],
            ['course', 'commerce_dashboard_products_courses'],
            ['digital', 'commerce_dashboard_products_digital'],
        ];
        $productcards = [];
        foreach ($productitems as [$key, $labelkey]) {
            $productcards[] = html_writer::div(
                html_writer::div(get_string($labelkey, 'local_subscriptions'), 'crm-commerce-dashboard-product-summary-label')
                . html_writer::div(format_float((int)($products[$key] ?? 0), 0), 'crm-commerce-dashboard-product-summary-value')
                . html_writer::div(get_string('commerce_dashboard_active', 'local_subscriptions'), 'crm-commerce-dashboard-product-summary-foot'),
                'crm-commerce-dashboard-product-summary-item' . ($key === 'total' ? ' is-total' : '')
            );
        }

        $showroomcards = [
            html_writer::div(
                html_writer::div(get_string('commerce_dashboard_showrooms_total', 'local_subscriptions'), 'crm-commerce-dashboard-product-summary-label')
                . html_writer::div(format_float((int)($showrooms['total'] ?? 0), 0), 'crm-commerce-dashboard-product-summary-value'),
                'crm-commerce-dashboard-showroom-summary-item is-total'
            ),
            html_writer::div(
                html_writer::div(get_string('commerce_dashboard_showrooms_published', 'local_subscriptions'), 'crm-commerce-dashboard-product-summary-label')
                . html_writer::div(format_float((int)($showrooms['published'] ?? 0), 0), 'crm-commerce-dashboard-product-summary-value'),
                'crm-commerce-dashboard-showroom-summary-item'
            ),
            html_writer::div(
                html_writer::div(get_string('commerce_dashboard_showrooms_workinprogress', 'local_subscriptions'), 'crm-commerce-dashboard-product-summary-label')
                . html_writer::div(format_float((int)($showrooms['workinprogress'] ?? 0), 0), 'crm-commerce-dashboard-product-summary-value'),
                'crm-commerce-dashboard-showroom-summary-item'
            ),
        ];

        $html = html_writer::div(
                html_writer::div(get_string('commerce_dashboard_products_section', 'local_subscriptions'), 'crm-commerce-dashboard-summary-section-title')
                . html_writer::div(implode('', $productcards), 'crm-commerce-dashboard-product-summary-grid'),
                'crm-commerce-dashboard-summary-section'
            )
            . html_writer::div(
                html_writer::div(get_string('commerce_dashboard_showrooms_section', 'local_subscriptions'), 'crm-commerce-dashboard-summary-section-title')
                . html_writer::div(implode('', $showroomcards), 'crm-commerce-dashboard-showroom-summary-grid'),
                'crm-commerce-dashboard-summary-section'
            )
            . html_writer::div(
                html_writer::link(
                    new moodle_url('/local/subscriptions/admin/commerce/products/index.php'),
                    get_string('commerce_dashboard_manage_catalog', 'local_subscriptions') . ' →'
                )
                . html_writer::span(' · ', 'text-muted mx-1')
                . html_writer::link(
                    new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php'),
                    get_string('commerce_dashboard_manage_showrooms', 'local_subscriptions') . ' →'
                ),
                'crm-commerce-dashboard-panel-footer'
            );

        return self::panel(
            get_string('commerce_dashboard_product_showroom_summary', 'local_subscriptions'),
            $html,
            'crm-commerce-dashboard-product-summary-panel',
            'fa-th-large'
        );
    }

    /** @param string[] $currencies @param array<string,mixed> $period */
    private static function currency_selector(string $currency, array $currencies, array $period, string $extraclass = ''): string {
        if ($currencies === []) { $currencies = [$currency]; }
        $links = [];
        foreach ($currencies as $code) {
            $params = self::period_params($period) + ['currency' => $code];
            $links[] = html_writer::link(new moodle_url(subscription_config::admin_commerce_page(), $params), self::currency_flag($code) . ' ' . s($code),
                ['class' => 'crm-commerce-dashboard-currency-option' . ($code === $currency ? ' is-active' : '')]);
        }
        return html_writer::tag('details', html_writer::tag('summary', self::currency_flag($currency) . ' ' . s($currency), ['class' => 'crm-commerce-dashboard-currency-summary'])
            . html_writer::div(implode('', $links), 'crm-commerce-dashboard-currency-menu'), ['class' => 'crm-commerce-dashboard-currency ' . $extraclass]);
    }

    /** @param array<string,mixed> $period @return array<string,string> */
    private static function period_params(array $period): array {
        $mode = (string)($period['mode'] ?? '30');
        $params = ['period' => $mode];
        if ($mode === 'custom') {
            $params['from'] = userdate((int)$period['start'], '%Y-%m-%d');
            $params['to'] = userdate((int)$period['end'], '%Y-%m-%d');
        }
        return $params;
    }

    private static function currency_flag(string $currency): string {
        return match (strtoupper($currency)) {
            'EUR' => '🇪🇺', 'RUB' => '🇷🇺', 'USD' => '🇺🇸', 'GBP' => '🇬🇧', 'CHF' => '🇨🇭',
            'CAD' => '🇨🇦', 'AUD' => '🇦🇺', 'CNY' => '🇨🇳', 'JPY' => '🇯🇵', default => '💱',
        };
    }

    private static function panel(string $title, string $content, string $extraclass = '', string $icon = ''): string {
        $heading = ($icon !== '' ? html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']) : '') . html_writer::span(s($title));
        return html_writer::tag('section', html_writer::tag('h2', $heading, ['class' => 'crm-commerce-dashboard-panel-title']) . $content,
            ['class' => 'crm-commerce-dashboard-card crm-commerce-dashboard-panel ' . $extraclass]);
    }

    private static function number_trend(int $current, int $previous): string {
        if ($previous <= 0) { return ''; }
        $percent = (($current - $previous) / abs($previous)) * 100;
        $class = $percent > 0 ? 'is-up' : ($percent < 0 ? 'is-down' : 'is-flat');
        $arrow = $percent > 0 ? '↑' : ($percent < 0 ? '↓' : '→');
        return html_writer::span(s($arrow . ' ' . format_float(abs($percent), 1) . '% ' . get_string('commerce_dashboard_vs_previous', 'local_subscriptions')), 'crm-commerce-dashboard-trend ' . $class);
    }

    private static function float_trend(float $current, float $previous): string {
        if ($previous <= 0.0) { return ''; }
        $delta = $current - $previous; $class = $delta > 0 ? 'is-up' : ($delta < 0 ? 'is-down' : 'is-flat'); $arrow = $delta > 0 ? '↑' : ($delta < 0 ? '↓' : '→');
        return html_writer::span(s($arrow . ' ' . format_float(abs($delta), 1) . ' pts ' . get_string('commerce_dashboard_vs_previous', 'local_subscriptions')), 'crm-commerce-dashboard-trend ' . $class);
    }

    private static function money_amount_only(int $minor): string {
        return format_float($minor / 100, 2);
    }

    /** @return array{0:int,1:int} */
    private static function nice_axis(int $rawmax, int $targetticks = 4): array {
        $rawmax = max(1, $rawmax);
        $roughstep = $rawmax / max(1, $targetticks);
        $power = 10 ** floor(log10($roughstep));
        $fraction = $roughstep / $power;
        if ($fraction <= 1) {
            $nicefraction = 1;
        } else if ($fraction <= 2) {
            $nicefraction = 2;
        } else if ($fraction <= 5) {
            $nicefraction = 5;
        } else {
            $nicefraction = 10;
        }
        $step = max(1, (int)round($nicefraction * $power));
        $axismax = max($step, (int)(ceil($rawmax / $step) * $step));
        return [$axismax, $step];
    }

    private static function axis_money(int $minor, string $currency): string {
        $amount = $minor / 100;
        if (abs($amount) >= 1000000) { return format_float($amount / 1000000, 1) . 'M ' . $currency; }
        if (abs($amount) >= 1000) { return format_float($amount / 1000, 1) . 'k ' . $currency; }
        return format_float($amount, 0) . ' ' . $currency;
    }

    private static function initials(string $name): string {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) { if ($part !== '') { $letters .= \core_text::strtoupper(\core_text::substr($part, 0, 1)); } }
        return $letters !== '' ? $letters : '?';
    }

    private static function relative_time(int $timestamp): string {
        return userdate($timestamp, get_string('strftimedateshort', 'langconfig'))
            . ' · '
            . userdate($timestamp, get_string('strftimetime', 'langconfig'));
    }
}
