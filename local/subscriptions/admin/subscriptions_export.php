<?php
// This file is part of Moodle - https://moodle.org/

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/excellib.class.php');

require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

define('LOCAL_SUBSCRIPTIONS_SCOPE_ALL_LEVELS', 13);
define('LOCAL_SUBSCRIPTIONS_SCOPE_TRIAL', 14);
define('LOCAL_SUBSCRIPTIONS_SCOPE_A1', 15);

$download = optional_param('download', 0, PARAM_BOOL);

$PAGE->set_url(new moodle_url('/local/subscriptions/admin/subscriptions_export.php'));
$PAGE->set_context($context);
$PAGE->set_title('Export des souscriptions');
$PAGE->set_heading('Export des souscriptions');

if (!$download) {
    echo $OUTPUT->header();

    echo $OUTPUT->heading('Export des souscriptions utilisateurs');

    $url = new moodle_url('/local/subscriptions/admin/subscriptions_export.php', [
        'download' => 1,
    ]);

    echo html_writer::div(
        html_writer::link($url, 'Télécharger le fichier Excel', [
            'class' => 'btn btn-primary',
        ]),
        'mt-4'
    );

    echo $OUTPUT->footer();
    exit;
}

$lang = current_language();

$sql = "
    SELECT
        us.id,
        u.firstname,
        u.lastname,
        u.email,
        u.phone1,
        u.phone2,
        COALESCE(NULLIF(pt.name, ''), p.name) AS planname,
        p.accessscopeid,
        COALESCE(NULLIF(ast.name, ''), s.name) AS scopename,
        p.duration_key,
        p.is_trial,
        us.pricepaid,
        us.currency,
        us.creation_date,
        us.start_date,
        us.status
    FROM {user_subscription} us
    JOIN {user} u ON u.id = us.userid
    LEFT JOIN {subscription_plan} p ON p.id = us.planid
    LEFT JOIN {subscription_plan_translation} pt
        ON pt.planid = p.id
        AND pt.lang = :planlang
    LEFT JOIN {subscription_access_scope} s ON s.id = p.accessscopeid
    LEFT JOIN {subscription_access_scope_translation} ast
        ON ast.accessscopeid = s.id
        AND ast.lang = :scopelang
    WHERE u.deleted = 0
    ORDER BY us.creation_date DESC, us.id DESC
";

$records = $DB->get_records_sql($sql, [
    'planlang' => $lang,
    'scopelang' => $lang,
]);

$sheets = [
    'long' => [
        'title' => '1 an - 3 ans - à vie',
        'records' => [],
    ],
    'a1' => [
        'title' => 'Cours A1',
        'records' => [],
    ],
    'trial' => [
        'title' => 'Essai',
        'records' => [],
    ],
];

foreach ($records as $record) {
    $accessscopeid = (int)($record->accessscopeid ?? 0);

    if ($accessscopeid === LOCAL_SUBSCRIPTIONS_SCOPE_TRIAL || (int)$record->is_trial === 1) {
        $sheets['trial']['records'][] = $record;
        continue;
    }

    if ($accessscopeid === LOCAL_SUBSCRIPTIONS_SCOPE_A1) {
        $sheets['a1']['records'][] = $record;
        continue;
    }

    if ($accessscopeid === LOCAL_SUBSCRIPTIONS_SCOPE_ALL_LEVELS) {
        $sheets['long']['records'][] = $record;
        continue;
    }

    if (in_array($record->duration_key, ['1year', '3years', 'lifetime'], true)) {
        $sheets['long']['records'][] = $record;
        continue;
    }
}

$filename = 'souscriptions_utilisateurs_' . date('Y-m-d_H-i') . '.xlsx';

$workbook = new MoodleExcelWorkbook('-');
$workbook->send($filename);

$headerformat = $workbook->add_format([
    'bold' => 1,
    'bg_color' => '#D9EAF7',
]);

$moneyformat = $workbook->add_format([
    'num_format' => '#,##0.00',
]);

$headers = [
    'Nom',
    'Prénom',
    'Email',
    'Téléphone',
    'Plan choisi',
    'Scope',
    'Prix payé',
    'Devise',
    'Date d’inscription',
    'Statut',
];

foreach ($sheets as $sheetdata) {
    $worksheet = $workbook->add_worksheet($sheetdata['title']);

    foreach ($headers as $col => $header) {
        $worksheet->write_string(0, $col, $header, $headerformat);
    }

    $row = 1;

    foreach ($sheetdata['records'] as $record) {
        $phone = $record->phone1 ?: $record->phone2;
        $date = $record->creation_date ?: $record->start_date;

        $worksheet->write_string($row, 0, $record->lastname ?? '');
        $worksheet->write_string($row, 1, $record->firstname ?? '');
        $worksheet->write_string($row, 2, $record->email ?? '');
        $worksheet->write_string($row, 3, $phone ?? '');
        $worksheet->write_string($row, 4, $record->planname ?? '');
        $worksheet->write_string($row, 5, $record->scopename ?? '');

        if ($record->pricepaid !== null) {
            $worksheet->write_number($row, 6, (float)$record->pricepaid, $moneyformat);
        } else {
            $worksheet->write_string($row, 6, '');
        }

        $worksheet->write_string($row, 7, $record->currency ?? '');

        if (!empty($date)) {
            $worksheet->write_string($row, 8, userdate($date, '%d/%m/%Y %H:%M'));
        } else {
            $worksheet->write_string($row, 8, '');
        }

        $worksheet->write_string($row, 9, $record->status ?? '');

        $row++;
    }

    $worksheet->set_column(0, 0, 22);
    $worksheet->set_column(1, 1, 22);
    $worksheet->set_column(2, 2, 35);
    $worksheet->set_column(3, 3, 20);
    $worksheet->set_column(4, 4, 35);
    $worksheet->set_column(5, 5, 22);
    $worksheet->set_column(6, 6, 14);
    $worksheet->set_column(7, 7, 10);
    $worksheet->set_column(8, 8, 22);
    $worksheet->set_column(9, 9, 14);
}

$workbook->close();
exit;