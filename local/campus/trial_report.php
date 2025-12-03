<?php
// local/campus/trial_report.php

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('moodle/site:config', \context_system::instance());

$trialplanid = 27;

// Paramètres de tri.
$sort = optional_param('sort', 'start_date', PARAM_ALPHAEXT);
$dir  = optional_param('dir', 'desc', PARAM_ALPHA);

// Colonnes autorisées pour le tri → expression SQL.
$sortfields = [
    'firstname'  => 'u.firstname',
    'lastname'   => 'u.lastname',
    'email'      => 'u.email',
    'phone'      => 'u.phone2',
    'country'    => 'u.country',
    'date48'     => '(us.start_date + 48 * 3600)',
    'date72'     => '(us.start_date + 72 * 3600)',
    'date7d'     => '(us.start_date + 7 * 24 * 3600)',
    'status'     => 'us.status',
    'start_date' => 'us.start_date',
];

// Validation du tri.
if (!array_key_exists($sort, $sortfields)) {
    $sort = 'start_date';
}
$dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';
$orderclause = $sortfields[$sort] . ' ' . $dir;

// Gestion export.
$download = optional_param('download', '', PARAM_ALPHA); // '', 'xls', 'csv'

$PAGE->set_url(new moodle_url('/local/campus/trial_report.php', [
    'sort' => $sort,
    'dir'  => strtolower($dir),
]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('trialreport_title', 'local_campus'));
$PAGE->set_heading(get_string('trialreport_title', 'local_campus'));

global $DB, $CFG;

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
        ORDER BY $orderclause";

$params = [
    'planid'   => $trialplanid,
    'replaced' => 'replaced',
];

$records = $DB->get_records_sql($sql, $params);

// Préparation des données.
$data = [];
$dateformat = '%a %d %b %H:%M'; // ex : lun 02 déc 14:30 (selon la locale Moodle)

foreach ($records as $r) {
    $start = (int)$r->start_date;

    $date48 = $start ? $start + 48 * 3600 : 0;
    $date72 = $start ? $start + 72 * 3600 : 0;
    $date7d = $start ? $start + 7 * 24 * 3600 : 0;

    // Pays lisible (si code ISO connu).
    $countryname = '';
    if (!empty($r->country)) {
        if (get_string_manager()->string_exists($r->country, 'countries')) {
            $countryname = get_string($r->country, 'countries');
        } else {
            $countryname = $r->country;
        }
    }

    // Téléphone : ajouter + si manquant.
    $phone = '';
    if (!empty($r->phone)) {
        $p = trim($r->phone);
        if ($p !== '' && $p[0] !== '+') {
            $p = '+' . $p;
        }
        $phone = $p;
    }

    $data[] = (object)[
        'firstname' => $r->firstname,
        'lastname'  => $r->lastname,
        'email'     => $r->email,
        'phone'     => $phone,
        'country'   => $countryname,
        'date48'    => $date48 ? userdate($date48, $dateformat) : '',
        'date72'    => $date72 ? userdate($date72, $dateformat) : '',
        'date7d'    => $date7d ? userdate($date7d, $dateformat) : '',
        'status'    => $r->status,
    ];
}

// En-têtes des colonnes (labels).
$headers = [
    'firstname' => get_string('trialreport_col_firstname', 'local_campus'),
    'lastname'  => get_string('trialreport_col_lastname',  'local_campus'),
    'email'     => get_string('trialreport_col_email',     'local_campus'),
    'phone'     => get_string('trialreport_col_phone',     'local_campus'),
    'country'   => get_string('trialreport_col_country',   'local_campus'),
    'date48'    => get_string('trialreport_col_date_48h',  'local_campus'),
    'date72'    => get_string('trialreport_col_date_72h',  'local_campus'),
    'date7d'    => get_string('trialreport_col_date_7d',   'local_campus'),
    'status'    => get_string('trialreport_col_status',    'local_campus'),
];

// Export CSV / XLS (en gardant sort/dir).
if ($download === 'csv') {
    require_once($CFG->libdir . '/csvlib.class.php');

    $filename = 'campus_trial_report_' . date('Ymd_His');
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data(array_values($headers));

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

    // En-têtes.
    foreach ($headers as $label) {
        $worksheet->write_string($rowno, $colno++, $label);
    }
    $rowno++;

    // Lignes.
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

// Affichage HTML.
echo $OUTPUT->header();

// CSS inline pour rendre le tableau plus agréable.
echo html_writer::tag('style', '
    .campus-trial-report-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .campus-trial-report-actions form {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
    }
    .campus-trial-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
        font-size: 0.8rem;
    }
    .campus-trial-table th,
    .campus-trial-table td {
        padding: 4px 6px;
        border-bottom: 1px solid #e0e0e0;
        border-right: 1px solid #f0f0f0;
        white-space: nowrap;
    }
    .campus-trial-table th:last-child,
    .campus-trial-table td:last-child {
        border-right: none;
    }
    .campus-trial-table thead th {
        background-color: #f5f5f7;
        font-weight: 600;
    }
    .campus-trial-table tbody tr:nth-child(even) {
        background-color: #fafafa;
    }
    .campus-trial-table tbody tr:hover {
        background-color: #f0f7ff;
    }
', ['type' => 'text/css']);

$exportxlsurl = new moodle_url($PAGE->url, ['download' => 'xls']);
$exportcsvurl = new moodle_url($PAGE->url, ['download' => 'csv']);

// Barre d’actions (tri + export).
echo html_writer::start_div('campus-trial-report-actions');

// Formulaire pour choisir ASC / DESC.
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url]);
echo html_writer::label('Tri :', 'id_sortdir', ['class' => 'me-1']);
echo html_writer::empty_tag('input', [
    'type'  => 'hidden',
    'name'  => 'sort',
    'value' => $sort,
]);
$options = [
    'asc'  => 'ASC',
    'desc' => 'DESC',
];
echo html_writer::select($options, 'dir', strtolower($dir), null, [
    'id'    => 'id_sortdir',
    'class' => 'custom-select',
]);
echo html_writer::empty_tag('input', [
    'type'  => 'submit',
    'value' => 'OK',
    'class' => 'btn btn-secondary btn-sm ms-1',
]);
echo html_writer::end_tag('form');

// Boutons export.
echo html_writer::link(
    $exportxlsurl,
    get_string('trialreport_export_xls', 'local_campus'),
    ['class' => 'btn btn-secondary btn-sm']
);
echo html_writer::link(
    $exportcsvurl,
    get_string('trialreport_export_csv', 'local_campus'),
    ['class' => 'btn btn-secondary btn-sm']
);

echo html_writer::end_div();

// Table triable.
$table = new html_table();
$table->attributes['class'] = 'campus-trial-table';

// Construction des en-têtes avec liens de tri.
$table->head = [];
foreach ($headers as $key => $label) {
    $current = ($key === $sort);
    $icon = '';

    if ($current) {
        $icon = ($dir === 'ASC') ? ' ▲' : ' ▼';
    }

    // Le clic change seulement la colonne de tri, la direction vient du select.
    $url = new moodle_url($PAGE->url, [
        'sort' => $key,
        'dir'  => strtolower($dir),
    ]);
    $link = html_writer::link($url, $label . $icon);
    $table->head[] = $link;
}

// Lignes.
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
