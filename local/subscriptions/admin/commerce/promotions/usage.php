<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\promotion\report\CommercePromotionUsageReport;
use local_subscriptions\commerce\promotion\repository\MoodleCommercePromotionRepository;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\currency\Currency;
use local_subscriptions\currency\CurrencyFormatter;

// Personal data + financial transactions: do not expose to normal students/teachers.
$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$id = required_param('id', PARAM_INT);
$promotion = (new MoodleCommercePromotionRepository())->get_by_id($id);
if ($promotion === null) {
    throw new moodle_exception('invalidrecord');
}
$basepath = '/local/subscriptions/admin/commerce/promotions/usage.php';
$pageurl = new moodle_url($basepath, ['id' => $id]);
$viewurl = new moodle_url('/local/subscriptions/admin/commerce/promotions/view.php', ['id' => $id]);
$title = get_string('commerce_promo_usage_title', 'local_subscriptions') . ' — ' .
    ($promotion->get_code() ?: $promotion->get_name());

$rateinput = str_replace(',', '.', trim(optional_param('rate', '0', PARAM_RAW_TRIMMED)));
$rate = is_numeric($rateinput) ? max(0.0, min(100.0, (float)$rateinput)) : 0.0;
$ratebasispoints = (int)round($rate * 100);
$status = optional_param('status', 'all', PARAM_ALPHA);
if (!in_array($status, ['all', 'paid', 'refunded', 'pending'], true)) {
    $status = 'all';
}
$from = trim(optional_param('from', '', PARAM_RAW_TRIMMED));
$to = trim(optional_param('to', '', PARAM_RAW_TRIMMED));
$validdate = static function(string $date): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) {
        return false;
    }
    [$year, $month, $day] = array_map('intval', explode('-', $date));
    return checkdate($month, $day, $year);
};
$from = $validdate($from) ? $from : '';
$to = $validdate($to) ? $to : '';
$timezone = \core_date::get_user_timezone_object();
$fromts = $from === '' ? null : (new DateTimeImmutable($from . ' 00:00:00', $timezone))->getTimestamp();
$tots = $to === '' ? null : (new DateTimeImmutable($to . ' 23:59:59', $timezone))->getTimestamp();
$page = max(0, optional_param('page', 0, PARAM_INT));
$export = optional_param('export', '', PARAM_ALPHA);

$all = (new CommercePromotionUsageReport($DB))->records($promotion);
$rows = array_values(array_filter($all, static function(array $row) use ($status, $fromts, $tots): bool {
    if ($status === 'paid' && !in_array($row['status'], ['paid', 'partial'], true)) {
        return false;
    }
    if ($status === 'refunded' && !in_array($row['status'], ['refunded', 'partial'], true)) {
        return false;
    }
    if ($status === 'pending' && !in_array($row['status'], ['pending', 'failed', 'cancelled', 'unknown', 'free'], true)) {
        return false;
    }
    $date = $row['paidat'] ?? $row['orderat'];
    return ($fromts === null || $date >= $fromts) && ($tots === null || $date <= $tots);
}));

$filters = [
    'id' => $id,
    'rate' => number_format($rate, 2, '.', ''),
    'status' => $status,
    'from' => $from,
    'to' => $to,
];
$money = static fn(int $minor, string $currency): string => CurrencyFormatter::format_minor_code($minor, $currency);
$date = static fn(?int $timestamp): string => $timestamp
    ? userdate($timestamp, get_string('strftimedatetimeshort', 'langconfig'))
    : get_string('commerce_promo_usage_nodate', 'local_subscriptions');
$statustext = [
    'paid' => 'commerce_promo_usage_success',
    'pending' => 'commerce_promo_usage_pending',
    'partial' => 'commerce_promo_usage_partial',
    'refunded' => 'commerce_promo_usage_fullrefund',
    'free' => 'commerce_promo_usage_free',
    'unknown' => 'commerce_promo_usage_unknown',
    'failed' => 'commerce_promo_usage_failed',
    'cancelled' => 'commerce_promo_usage_cancelled',
];

// Export all filtered orders, not merely the current pagination page.
if ($export === 'csv') {
    $name = 'campusfr-promo-' . preg_replace('/[^A-Za-z0-9_-]/', '-',
        (string)($promotion->get_code() ?? $id)) . '-' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Cache-Control: private, no-store, max-age=0');
    $out = fopen('php://output', 'wb');
    fwrite($out, "\xEF\xBB\xBF"); // Excel UTF-8 BOM.
    $safe = static function(string $value): string {
        // Excel formula injection: exported names, email, products and references are untrusted.
        return preg_match('/^[\s]*[=+@\-\t\r]/u', $value) ? "'" . $value : $value;
    };
    $numeric = static function(int $minor, string $currency): string {
        $exp = Currency::minor_unit_exponent($currency);
        return number_format($minor / (10 ** $exp), $exp, '.', '');
    };
    fputcsv($out, ['Order date', 'Paid at', 'Customer', 'Email', 'Moodle user ID',
        'Order reference', 'Products', 'Status', 'Currency', 'Amount paid',
        'Refunded', 'Net collected', 'Coupon discount', 'Discount reliability',
        'Commission rate (%)', 'Estimated commission'], ';');
    foreach ($rows as $row) {
        fputcsv($out, [
            $date($row['orderat']), $date($row['paidat']), $safe($row['name']), $safe($row['email']),
            $row['userid'], $safe($row['reference']), $safe(implode(' | ', $row['products'])),
            $safe(get_string($statustext[$row['status']], 'local_subscriptions')),
            $row['currency'], $numeric($row['grossminor'], $row['currency']),
            $numeric($row['refundedminor'], $row['currency']),
            $numeric($row['netminor'], $row['currency']),
            $row['discountminor'] === null ? '' : $numeric($row['discountminor'], $row['currency']),
            $row['discountprecision'], number_format($rate, 2, '.', ''),
            $numeric(CommercePromotionUsageReport::commission_minor($row['netminor'], $ratebasispoints),
                $row['currency']),
        ], ';');
    }
    fclose($out);
    exit;
}

$totals = [];
foreach ($rows as $row) {
    $currency = $row['currency'];
    $totals[$currency] ??= ['orders' => 0, 'confirmed' => 0, 'gross' => 0, 'refunds' => 0,
        'net' => 0, 'commission' => 0];
    $totals[$currency]['orders']++;
    if (in_array($row['status'], ['paid', 'partial', 'refunded'], true)) {
        $totals[$currency]['confirmed']++;
    }
    $totals[$currency]['gross'] += $row['grossminor'];
    $totals[$currency]['refunds'] += $row['refundedminor'];
    $totals[$currency]['net'] += $row['netminor'];
    $totals[$currency]['commission'] += CommercePromotionUsageReport::commission_minor(
        $row['netminor'], $ratebasispoints
    );
}

CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title,
    'local-subscriptions-commerce-promotion-usage-page');
$actions = html_writer::link($viewurl,
    get_string('commerce_promo_usage_back', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']);
echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_promotions_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/promotions/index.php')],
    ['label' => $promotion->get_code() ?: $promotion->get_name(), 'url' => $viewurl],
    ['label' => get_string('commerce_promo_usage_title', 'local_subscriptions'), 'url' => null],
]);
echo CrmPageHeader::render($title,
    get_string('commerce_promo_usage_desc', 'local_subscriptions'), HelpContext::COMMERCE, $actions);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::OFFERS_ACCESS, $context);
echo CommerceOffersAccessNavigationRenderer::render(CommerceOffersAccessNavigationRenderer::PROMOTIONS);

$form = html_writer::start_tag('form', ['method' => 'get', 'action' => $pageurl->out(false),
    'class' => 'card p-3 mb-3']);
$form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
$form .= html_writer::start_div('row g-3 align-items-end');
$formfields = [
    ['rate', get_string('commerce_promo_usage_rate', 'local_subscriptions'), 'number',
        number_format($rate, 2, '.', ''), ['min' => '0', 'max' => '100', 'step' => '0.01']],
    ['from', get_string('commerce_promo_usage_filter_start', 'local_subscriptions'), 'date', $from, []],
    ['to', get_string('commerce_promo_usage_filter_end', 'local_subscriptions'), 'date', $to, []],
];
foreach ($formfields as [$key, $label, $type, $value, $extra]) {
    $form .= html_writer::start_div('col-sm-6 col-lg-2');
    $form .= html_writer::label($label, 'promo-' . $key, false, ['class' => 'form-label']);
    $form .= html_writer::empty_tag('input', array_merge(['id' => 'promo-' . $key, 'name' => $key,
        'type' => $type, 'value' => $value, 'class' => 'form-control'], $extra));
    $form .= html_writer::end_div();
}
$form .= html_writer::start_div('col-sm-6 col-lg-3');
$form .= html_writer::label(get_string('commerce_promo_usage_status', 'local_subscriptions'),
    'promo-status', false, ['class' => 'form-label']);
$form .= html_writer::select([
    'all' => get_string('commerce_promo_usage_filter_all', 'local_subscriptions'),
    'paid' => get_string('commerce_promo_usage_filter_paid', 'local_subscriptions'),
    'refunded' => get_string('commerce_promo_usage_filter_refunded', 'local_subscriptions'),
    'pending' => get_string('commerce_promo_usage_filter_pending', 'local_subscriptions'),
], 'status', $status, false, ['id' => 'promo-status', 'class' => 'form-select']);
$form .= html_writer::end_div();
$form .= html_writer::div(html_writer::tag('button',
    get_string('commerce_promo_usage_apply', 'local_subscriptions'),
    ['type' => 'submit', 'class' => 'btn btn-primary']), 'col-sm-6 col-lg-2');
$form .= html_writer::end_div() . html_writer::end_tag('form');
echo $form;

echo html_writer::div(get_string('commerce_promo_usage_notes', 'local_subscriptions'),
    'alert alert-info');
if ($ratebasispoints === 0) {
    echo html_writer::div(get_string('commerce_promo_usage_warning', 'local_subscriptions'),
        'text-muted mb-3');
}
echo html_writer::div(get_string('commerce_promo_usage_reconciled', 'local_subscriptions'),
    'small text-muted mb-3');

if ($totals) {
    $summary = new html_table();
    $summary->attributes['class'] = 'table table-striped mb-0';
    $summary->head = [
        get_string('commerce_promo_usage_currency', 'local_subscriptions'),
        get_string('commerce_promo_usage_potential', 'local_subscriptions'),
        get_string('commerce_promo_usage_count', 'local_subscriptions'),
        get_string('commerce_promo_usage_paid', 'local_subscriptions'),
        get_string('commerce_promo_usage_refunded', 'local_subscriptions'),
        get_string('commerce_promo_usage_net', 'local_subscriptions'),
        get_string('commerce_promo_usage_commission', 'local_subscriptions'),
    ];
    foreach ($totals as $currency => $t) {
        $summary->data[] = [s($currency), $t['orders'], $t['confirmed'],
            s($money($t['gross'], $currency)), s($money($t['refunds'], $currency)),
            html_writer::tag('strong', s($money($t['net'], $currency))),
            html_writer::tag('strong', s($money($t['commission'], $currency)))];
    }
    echo html_writer::start_div('card mb-3');
    echo html_writer::div(html_writer::tag('h2',
        get_string('commerce_promo_usage_summary', 'local_subscriptions'), ['class' => 'h5 mb-0']),
        'card-header');
    echo html_writer::div(html_writer::table($summary), 'table-responsive');
    echo html_writer::end_div();
}

$exporturl = new moodle_url($basepath, array_merge($filters, ['export' => 'csv']));
echo html_writer::div(html_writer::link($exporturl,
    html_writer::tag('i', '', ['class' => 'fa fa-download me-2', 'aria-hidden' => 'true']) .
        get_string('commerce_promo_usage_export', 'local_subscriptions'),
    ['class' => 'btn btn-outline-primary']), 'mb-3');

if (!$rows) {
    echo html_writer::div(get_string('commerce_promo_usage_noresults', 'local_subscriptions'),
        'alert alert-secondary');
} else {
    $perpage = 25;
    $page = min($page, max(0, (int)ceil(count($rows) / $perpage) - 1));
    $table = new html_table();
    $table->attributes['class'] = 'table table-striped table-hover align-middle mb-0';
    $table->head = [
        get_string('commerce_promo_usage_date', 'local_subscriptions'),
        get_string('commerce_promo_usage_paidat', 'local_subscriptions'),
        get_string('commerce_promo_usage_customer', 'local_subscriptions'),
        get_string('commerce_promo_usage_order', 'local_subscriptions'),
        get_string('commerce_promo_usage_product', 'local_subscriptions'),
        get_string('commerce_promo_usage_status', 'local_subscriptions'),
        get_string('commerce_promo_usage_gross', 'local_subscriptions'),
        get_string('commerce_promo_usage_refunded', 'local_subscriptions'),
        get_string('commerce_promo_usage_net', 'local_subscriptions'),
        get_string('commerce_promo_usage_discount', 'local_subscriptions'),
        get_string('commerce_promo_usage_commission', 'local_subscriptions'),
    ];
    $badges = ['paid' => 'text-bg-success', 'partial' => 'text-bg-warning',
        'refunded' => 'text-bg-secondary', 'pending' => 'bg-light text-dark border',
        'free' => 'text-bg-info', 'unknown' => 'text-bg-warning',
        'failed' => 'text-bg-danger', 'cancelled' => 'text-bg-secondary'];
    foreach (array_slice($rows, $page * $perpage, $perpage) as $row) {
        $customer = html_writer::tag('strong', s($row['name'] ?: '—')) .
            html_writer::div(s($row['email']), 'text-muted small');
        $reference = html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['id' => $row['id']]),
            s($row['reference'])
        );
        $detail = $row['discountminor'] === null
            ? html_writer::span('—', 'text-muted')
            : s($money($row['discountminor'], $row['currency']));
        if ($row['discountprecision'] === 'estimate') {
            $detail .= html_writer::div(get_string('commerce_promo_usage_approx', 'local_subscriptions'),
                'text-muted small');
        } else if ($row['discountprecision'] === 'unavailable') {
            $detail .= html_writer::div(get_string('commerce_promo_usage_missing', 'local_subscriptions'),
                'text-muted small');
        }
        $table->data[] = [
            s($date($row['orderat'])),
            s($date($row['paidat'])),
            $customer,
            $reference,
            s(implode(', ', $row['products'])),
            html_writer::span(get_string($statustext[$row['status']], 'local_subscriptions'),
                'badge rounded-pill ' . $badges[$row['status']]),
            s($money($row['grossminor'], $row['currency'])),
            s($money($row['refundedminor'], $row['currency'])),
            html_writer::tag('strong', s($money($row['netminor'], $row['currency']))),
            $detail,
            s($money(CommercePromotionUsageReport::commission_minor(
                $row['netminor'], $ratebasispoints), $row['currency'])),
        ];
    }
    echo html_writer::div(html_writer::table($table), 'table-responsive card');
    if (count($rows) > $perpage) {
        echo $OUTPUT->paging_bar(count($rows), $page, $perpage,
            new moodle_url($basepath, $filters), 'page');
    }
    echo html_writer::div(get_string('commerce_promo_usage_legacy', 'local_subscriptions'),
        'small text-muted mt-3');
}
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
