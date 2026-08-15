<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminContextResolver;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;

AdminSecurity::require(Capabilities::VIEW_PAYMENTS);

$q = optional_param('q', '', PARAM_RAW_TRIMMED);
$status = optional_param('status', '', PARAM_ALPHANUMEXT);
$mailtype = optional_param('mailtype', '', PARAM_ALPHANUMEXT);
$language = optional_param('language', '', PARAM_ALPHANUMEXT);
$purchaseid = optional_param('purchaseid', 0, PARAM_INT);
$attempts = max(0, optional_param('attempts', 0, PARAM_INT));
$includeaudit = optional_param('includeaudit', 0, PARAM_BOOL) === 1;
$period = optional_param('period', '30', PARAM_ALPHANUMEXT);
$customfrom = optional_param('from', '', PARAM_RAW_TRIMMED);
$customto = optional_param('to', '', PARAM_RAW_TRIMMED);
$sort = optional_param('sort', 'date', PARAM_ALPHA);
$direction = strtolower(optional_param('dir', 'desc', PARAM_ALPHA)) === 'asc' ? 'asc' : 'desc';

$availablecolumns = ['date', 'recipient', 'type', 'status', 'context', 'language', 'attempts', 'error'];
$requestedcolumns = optional_param_array('columns', [], PARAM_ALPHA);
$visiblecolumns = $requestedcolumns === []
    ? $availablecolumns
    : array_values(array_intersect($availablecolumns, $requestedcolumns));
if ($visiblecolumns === []) {
    $visiblecolumns = $availablecolumns;
}

$parsedate = static function(string $value, bool $endofday = false): ?int {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim($value))) {
        return null;
    }
    try {
        $date = new \DateTimeImmutable(trim($value), \core_date::get_user_timezone_object());
        return ($endofday ? $date->setTime(23, 59, 59) : $date->setTime(0, 0, 0))->getTimestamp();
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
} else if ($period === 'today') {
    $datefrom = usergetmidnight($now);
    $dateto = $now;
} else if ($period !== 'all') {
    $days = (int)$period;
    $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
    $datefrom = $now - ($days * DAYSECS);
    $dateto = $now;
}

$filters = compact(
    'q',
    'status',
    'mailtype',
    'language',
    'purchaseid',
    'attempts',
    'includeaudit',
    'datefrom',
    'dateto',
    'sort'
);
$filters['dir'] = $direction;
$records = (new CommerceMailAdminService())->search_all($filters);
$resolver = new CommerceMailAdminContextResolver($DB);

$labels = [
    'date' => get_string('date', 'local_subscriptions'),
    'recipient' => get_string('commerce_mail_recipient_column', 'local_subscriptions'),
    'type' => get_string('type', 'local_subscriptions'),
    'status' => get_string('status', 'local_subscriptions'),
    'context' => get_string('commerce_mail_context_column', 'local_subscriptions'),
    'language' => get_string('language', 'local_subscriptions'),
    'attempts' => get_string('attempts', 'local_subscriptions'),
    'error' => get_string('commerce_mail_last_error', 'local_subscriptions'),
];

$csv = new csv_export_writer();
$csv->set_filename('commerce-mail-journal-' . userdate(time(), '%Y%m%d-%H%M%S'));
$csv->add_data(array_map(static fn(string $key): string => $labels[$key], $visiblecolumns));

foreach ($records as $record) {
    $resolved = $resolver->resolve($record);
    $recipient = trim((string)$record->recipientname);
    if ($recipient !== '') {
        $recipient .= ' <' . (string)$record->recipientemail . '>';
    } else {
        $recipient = (string)$record->recipientemail;
    }
    $contextvalue = trim((string)$resolved['contexttitle']);
    if (trim((string)$resolved['contextsubtitle']) !== '') {
        $contextvalue .= ($contextvalue !== '' ? ' · ' : '') . trim((string)$resolved['contextsubtitle']);
    }
    $values = [
        'date' => userdate((int)$record->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        'recipient' => $recipient,
        'type' => CommerceMailAdminPresentation::type_label((string)$record->mailtype),
        'status' => CommerceMailAdminPresentation::status_label((string)$record->status),
        'context' => $contextvalue,
        'language' => CommerceMailAdminPresentation::language_label((string)$record->language),
        'attempts' => (int)$record->attemptcount . '/' . (int)$record->maxattempts,
        'error' => trim((string)($record->lasterror ?? '')),
    ];
    $csv->add_data(array_map(static fn(string $key): string => (string)$values[$key], $visiblecolumns));
}

$csv->download_file();
exit;
