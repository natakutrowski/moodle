<?php
// local/campus/trial_report.php

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('moodle/site:config', \context_system::instance());

$trialplanid = 27;

$download = optional_param('download', '', PARAM_ALPHA); // '', 'xls', 'csv'

$PAGE->set_url(new moodle_url('/local/campus/trial_report.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('trialreport_title', 'local_campus'));
$PAGE->set_heading(get_string('trialreport_title', 'local_campus'));

// Récupération des données.
global $DB;

$sql = "SELECT
            us.id AS subid,
            us.userid,
            us.start_date,
            us.status,
            u.firstname,
            u.lastname,
            u.email,
            u.phone2 AS phone,
            u.country
        FROM {user_subscription} us
        JOIN {user} u ON u.id = us.userid
        WHERE us.planid = :planid
          AND us.status <> :replaced
        ORDER BY us.start_date DESC";

$params = [
    'planid'   => $trialplanid,
    'replaced' => 'replaced',
];

$records = $DB->get_records_sql($sql, $params);

// Prépare les données formatées.
$data = [];
foreach ($records as $r) {
    $start = (int)$r->start_date;

    $date48  = $start ? $start + 48 * 3600 : 0;
    $date72  = $start ? $start + 72 * 3600 : 0;
    $date7d  = $start ? $start + 7  * 24 * 3600 : 0;

    // Pays lisible (si code ISO)
    $countryname = '';
    if (!empty($r->country)) {
        // Si c'est un code ISO compatible avec pack 'countries'
        $countryname = get_string($r->country, 'countries');
    }

    // Téléphone avec indicatif
    $phone = '';
    if (!empty($r->phone)) {
        $cc = '';
        if (!empty($r->phone_country)) {
            $cc = trim($r->phone_country);
            if ($cc !== '' && $cc[0] !== '+') {
                $cc = '+' . $cc;
            }
        }
        if ($cc !== '') {
            $phone = $cc . ' ' . $r->phone;
        } else {
            $phone = $r->phone;
        }
    }

    $data[] = (object) [
        'firstname' => $r->firstname,
        'lastname'  => $r->lastname,
        'email'     => $r->email,
        'phone'     => $phone,
        'country'   => $countryname,
        'date48'    => $date48 ? userdate($date48) : '',
        'date72'    => $date72 ? userdate($date72) : '',
        'date7d'    => $date7d ? userdate($date7d) : '',
        'status'    => $r->status,
    ];
}

// En-têtes de colonnes (avec strings)
$headers = [
    get_string('trialreport_col_firstname', 'local_campus'),
    get_string('trialreport_col_lastname',  'local_campus'),
    get_string('trialreport_col_email',     'local_campus'),
    get_string('trialreport_col_phone',     'local_campus'),
    get_string('trialreport_col_country',   'local_campus'),
    get_string('trialreport_col_date_48h',  'local_campus'),
    get_string('trialreport_col_date_72h',  'local_campus'),
    get_string('trialreport_col_date_7d',   'local_campus'),
    get_string('trialreport_col_status',    'local_campus'),
];

// Gestion export CSV / XLS
if ($download === 'csv') {
    require_once($CFG->libdir . '/csvlib.class.php');

    $filename = 'campus_trial_report_' . date('Ymd_His');
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($headers);

    foreach ($data as $row) {
        $csvexport->add_data([
            $row->firstname,
            $row->lastname,
            $row->email,
            $row->phone,
            $row->country,
            $row->date48,
            $row->date72,
            $row->date7d,
            $row->status,
        ]);
    }

    $csvexport->download_file();
    exit;
}

if ($download === 'xls') {
    require_once($CFG->libdir . '/excellib.class.php');

    $filename = 'campus_trial_report_' . date('Ymd_His') . '.xls';
    $workbook = new MoodleExcelWorkbook($filename);
    $worksheet = $workbook->add_worksheet('Trials');

    $rowno = 0;
    $colno = 0;

    // En-têtes
    foreach ($headers as $h) {
        $worksheet->write_string($rowno, $colno++, $h);
    }
    $rowno++;

    // Lignes
    foreach ($data as $row) {
        $colno = 0;
        $worksheet->write_string($rowno, $colno++, $row->firstname);
        $worksheet->write_string($rowno, $colno++, $row->lastname);
        $worksheet->write_string($rowno, $colno++, $row->email);
        $worksheet->write_string($rowno, $colno++, $row->phone);
        $worksheet->write_string($rowno, $colno++, $row->country);
        $worksheet->write_string($rowno, $colno++, $row->date48);
        $worksheet->write_string($rowno, $colno++, $row->date72);
        $worksheet->write_string($rowno, $colno++, $row->date7d);
        $worksheet->write_string($rowno, $colno++, $row->status);
        $rowno++;
    }

    $workbook->close();
    exit;
}

// Affichage HTML normal.
echo $OUTPUT->header();

$exportxlsurl = new moodle_url($PAGE->url, ['download' => 'xls']);
$exportcsvurl = new moodle_url($PAGE->url, ['download' => 'csv']);

echo html_writer::start_div('campus-trial-report-actions mb-3');
echo html_writer::link(
    $exportxlsurl,
    get_string('trialreport_export_xls', 'local_campus'),
    ['class' => 'btn btn-secondary me-2']
);
echo html_writer::link(
    $exportcsvurl,
    get_string('trialreport_export_csv', 'local_campus'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

$table = new html_table();
$table->head = $headers;
$table->data = [];

foreach ($data as $row) {
    $table->data[] = [
        s($row->firstname),
        s($row->lastname),
        s($row->email),
        s($row->phone),
        s($row->country),
        s($row->date48),
        s($row->date72),
        s($row->date7d),
        s($row->status),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
