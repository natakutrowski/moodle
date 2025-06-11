<?php
require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/import_csv.php'));
$PAGE->set_title(get_string('import_csv_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('import_csv_heading', 'local_subscriptions'));
$PAGE->requires->css('/local/subscriptions/styles.css');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('import_csv_heading', 'local_subscriptions'));

// Gestion du formulaire de téléchargement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvfile'])) {
    $tmp = $_FILES['csvfile']['tmp_name'];
    $originalname = $_FILES['csvfile']['name'];
    $content = file_get_contents($tmp);
    $lines = explode(PHP_EOL, $content);

    $rows = [];
    $headers = [];
    $separator = ','; // ou ',' si besoin

    foreach ($lines as $index => $line) {
        if (trim($line) === '') continue;

        $record = str_getcsv($line, $separator);

        if ($index === 0) {
            $headers = array_map('trim', $record);
        } else {
            if (count($record) < 4) continue;
            $row = array_combine($headers, $record);
            $rows[] = $row;
        }
    }

    $importid = time();
    $tempfile = make_request_directory() . "/csv_import_$importid.csv";
    file_put_contents($tempfile, $content);

    // Formulaire de confirmation
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/subscriptions/process_csv.php')
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'importid',
        'value' => $importid
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sourcefile',
        'value' => base64_encode($content)
    ]);

    echo html_writer::tag('p', get_string('import_preview', 'local_subscriptions'));

    if (!empty($rows)) {
        echo html_writer::start_tag('table', ['class' => 'generaltable import-preview']);
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        foreach ($headers as $head) {
            echo html_writer::tag('th', s($head));
        }
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');
        foreach ($rows as $row) {
            echo html_writer::start_tag('tr');
            foreach ($row as $cell) {
                echo html_writer::tag('td', s($cell));
            }
            echo html_writer::end_tag('tr');
        }
        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
    }

    echo html_writer::tag('button', get_string('confirm_import', 'local_subscriptions'), [
        'type' => 'submit',
        'class' => 'btn btn-primary',
        'style' => 'margin-top: 20px;'
    ]);

    echo html_writer::end_tag('form');

    echo $OUTPUT->footer();
    exit;
}

// Formulaire de sélection de fichier
echo html_writer::start_tag('form', [
    'method' => 'post',
    'enctype' => 'multipart/form-data',
    'class' => 'csv-upload-form'
]);

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('select_csv_file', 'local_subscriptions'), [
    'for' => 'csvfile',
    'class' => 'form-label'
]);
echo html_writer::empty_tag('input', [
    'type' => 'file',
    'name' => 'csvfile',
    'id' => 'csvfile',
    'accept' => '.csv',
    'required' => true
]);
echo html_writer::end_div();

echo html_writer::tag('button', get_string('submit_csv_file', 'local_subscriptions'), [
    'type' => 'submit',
    'class' => 'btn btn-success',
    'style' => 'margin-top: 15px;'
]);

echo html_writer::end_tag('form');

// JS pour afficher le nom du fichier sélectionné
echo <<<HTML
<script>
document.getElementById('csvfile').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || '';
    if (fileName) {
        const label = document.querySelector("label[for='csvfile']");
        label.innerHTML = label.innerHTML.split(':')[0] + ': ' + fileName;
    }
});
</script>
HTML;

echo $OUTPUT->footer();
