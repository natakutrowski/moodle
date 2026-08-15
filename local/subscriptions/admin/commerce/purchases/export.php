<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseListFilter;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\payment\Provider;

AdminSecurity::require(Capabilities::VIEW_PAYMENTS);

$query = optional_param('q', '', PARAM_RAW_TRIMMED);
$type = optional_param('type', '', PARAM_ALPHANUMEXT);
$commercialstatus = optional_param('commercialstatus', '', PARAM_ALPHANUMEXT);
$paymentstatus = optional_param('paymentstatus', '', PARAM_ALPHANUMEXT);
$fulfillmentstatus = optional_param('fulfillmentstatus', '', PARAM_ALPHANUMEXT);
$provider = optional_param('provider', '', PARAM_ALPHANUMEXT);
$currency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
$offerorigin = optional_param('offerorigin', '', PARAM_ALPHA);
$adminstate = optional_param('adminstate', 'open', PARAM_ALPHA);
$adminstate = in_array($adminstate, ['open', 'closed', 'all'], true) ? $adminstate : 'open';
$period = optional_param('period', '30', PARAM_ALPHANUMEXT);
$customfrom = optional_param('from', '', PARAM_RAW_TRIMMED);
$customto = optional_param('to', '', PARAM_RAW_TRIMMED);
$sort = optional_param('sort', 'date', PARAM_ALPHA);
$direction = strtolower(optional_param('dir', 'desc', PARAM_ALPHA)) === 'asc' ? 'asc' : 'desc';

$availablecolumns = [
    'date', 'reference', 'customer', 'type', 'products', 'amount',
    'payment', 'fulfillment', 'commercial',
];
$defaultcolumns = [
    'date', 'reference', 'customer', 'type', 'products', 'amount', 'commercial',
];
$requestedcolumns = optional_param_array('columns', [], PARAM_ALPHA);
$visiblecolumns = $requestedcolumns === []
    ? $defaultcolumns
    : array_values(array_intersect($availablecolumns, $requestedcolumns));
if ($visiblecolumns === []) {
    $visiblecolumns = $defaultcolumns;
}

/** @return int|null */
$parsedate = static function(string $value, bool $endofday = false): ?int {
    $value = trim($value);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
        return null;
    }
    try {
        $timezone = \core_date::get_user_timezone_object();
        $date = new \DateTimeImmutable($value, $timezone);
        $date = $endofday ? $date->setTime(23, 59, 59) : $date->setTime(0, 0, 0);
        return $date->getTimestamp();
    } catch (\Throwable) {
        return null;
    }
};

$now = time();
$datefrom = 0;
$dateto = 0;
if ($period === 'custom') {
    $datefrom = $parsedate($customfrom) ?? 0;
    $dateto = $parsedate($customto, true) ?? 0;
} elseif ($period === 'today') {
    $datefrom = usergetmidnight($now);
    $dateto = $now;
} elseif ($period !== 'all') {
    $days = (int)$period;
    $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
    $datefrom = $now - ($days * DAYSECS);
    $dateto = $now;
}

$filter = new CommercePurchaseListFilter(
    $query,
    $type,
    $commercialstatus,
    $paymentstatus,
    $fulfillmentstatus,
    $provider,
    $currency,
    $datefrom,
    $dateto,
    $sort,
    $direction,
    $offerorigin,
    $adminstate
);

$rows = (new CommercePurchaseReadRepository($DB))->summaries_for_export($filter);

$columnlabels = [
    'date' => get_string('date'),
    'reference' => get_string('commerce_purchase_reference', 'local_subscriptions'),
    'customer' => get_string('commerce_purchase_customer', 'local_subscriptions'),
    'type' => get_string('commerce_sales_product_type', 'local_subscriptions'),
    'products' => get_string('commerce_purchase_products', 'local_subscriptions'),
    'amount' => get_string('commerce_purchase_amount', 'local_subscriptions'),
    'payment' => get_string('commerce_purchase_payment_status', 'local_subscriptions'),
    'fulfillment' => get_string('commerce_purchase_fulfillment_status', 'local_subscriptions'),
    'commercial' => get_string('commerce_purchase_commercial_status', 'local_subscriptions'),
];

$csv = new csv_export_writer();
$csv->set_filename('commerce-sales-' . userdate(time(), '%Y%m%d-%H%M%S'));
$csv->add_data(array_map(
    static fn(string $key): string => $columnlabels[$key],
    $visiblecolumns
));

foreach ($rows as $purchase) {
    $customer = $purchase->customer->display_name();
    if ($customer === '') {
        $customer = $purchase->customer->email;
    }

    $values = [
        'date' => userdate(
            $purchase->timecreated,
            get_string('strftimedatetimeshort', 'langconfig')
        ),
        'reference' => $purchase->publicreference !== ''
            ? $purchase->publicreference
            : $purchase->reference,
        'customer' => trim($customer . (
            $purchase->customer->email !== '' ? ' <' . $purchase->customer->email . '>' : ''
        )),
        'type' => CommercePurchasePresentation::type_label($purchase->type),
        'products' => implode(' | ', $purchase->productlabels),
        'amount' => CommercePurchasePresentation::money(
            $purchase->totalminor,
            $purchase->currency
        ),
        'payment' => CommercePurchasePresentation::technical_status_label(
            'payment',
            $purchase->paymentstatus
        ) . (
            $purchase->provider !== null && $purchase->provider !== ''
                ? ' · ' . Provider::get($purchase->provider)
                : ''
        ),
        'fulfillment' => CommercePurchasePresentation::technical_status_label(
            'fulfillment',
            $purchase->fulfillmentstatus
        ),
        'commercial' => CommercePurchasePresentation::commercial_status_label(
            $purchase->commercialstatus
        ),
    ];

    $csv->add_data(array_map(
        static fn(string $key): string => (string)$values[$key],
        $visiblecolumns
    ));
}

$csv->download_file();
exit;
