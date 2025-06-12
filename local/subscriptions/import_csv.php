<?php
require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;
use local_subscriptions\subscription_config;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(subscription_config::import_csv_page()));
$PAGE->set_title(get_string('import_subscriptions', 'local_subscriptions'));
$PAGE->set_heading(get_string('import_subscriptions_csv', 'local_subscriptions'));
$PAGE->requires->css('/local/subscriptions/styles.css');

echo $OUTPUT->header();

// Gestion du formulaire de téléchargement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvfile'])) {
    $tmp = $_FILES['csvfile']['tmp_name'];
    $originalname = $_FILES['csvfile']['name'];
    $content = file_get_contents($tmp);
    $lines = explode(PHP_EOL, $content);

    $rows = [];
    $headers = [];
    $separator = ','; // ou ';' si besoin

    // Récupération des abonnements existants
    $existing_subs = [];
    $allsubs = $DB->get_records('user_subscription');
    foreach ($allsubs as $sub) {
        $user = $DB->get_record('user', ['id' => $sub->userid, 'deleted' => 0], 'email');
        if ($user) {
            $existing_subs[strtolower(trim($user->email))][$sub->access_scope] = true;
        }
    }
    $validrows = [];

    foreach ($lines as $index => $line) {
        if (trim($line) === '') continue;

        $record = str_getcsv($line, $separator);

        if ($index === 0) {
			$headers = array_map('trim', $record);
        } else {
            if (count($record) < 4) continue;
            $row = array_combine($headers, $record);
            $email = strtolower(trim($row['email'] ?? ''));
            $scope = trim($row['access_scope'] ?? '');
            $is_duplicate = isset($existing_subs[$email][$scope]);
            $row['_duplicate'] = $is_duplicate;
            $rows[] = $row;

            if (!$is_duplicate) {
                $validrows[] = $row;
            }
        }
    }

    $importid = time();
    $tempfile = make_request_directory() . "/csv_import_$importid.csv";
    file_put_contents($tempfile, $content);

    // Formulaire de confirmation
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url(subscription_config::process_csv_page())
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'importid',
        'value' => $importid
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sourcefile',
        'value' => base64_encode(serialize($validrows))
    ]);

    // ➕ Message récapitulatif
    $total = count($rows);
    $valid = count($validrows);
    $ignored = $total - $valid;
    
    if (!empty($rows)) {
    
		$plans = subscription_config::get_plans();
		$scopes = subscription_config::get_scopes();
    
        echo html_writer::start_tag('table', ['class' => 'generaltable import-preview']);
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        
        // Master checkbox
		echo html_writer::tag('th',
			html_writer::empty_tag('input', [
				'type' => 'checkbox',
				'id' => 'select-all',
				'class' => 'form-check-input'
			])
		);
		$headers_string = [
			'email' => get_string('email', 'local_subscriptions'),
			'start_date' => get_string('start_date', 'local_subscriptions'),
			'plan' => get_string('plan', 'local_subscriptions'),
			'access_scope' => get_string('access_scope', 'local_subscriptions'),
		];
        
        foreach ($headers_string as $head) {
            echo html_writer::tag('th', s($head));
        }
        echo html_writer::tag('th', ' ');
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');
        
		foreach ($rows as $i => $row) {		
			$duplicate = !empty($row['_duplicate']);
    		$rowclass = $duplicate ? 'text-muted bg-light' : '';
			echo html_writer::start_tag('tr', ['class' => $rowclass]);
			
			// Checkbox (disabled if duplicate)
			$checkboxattrs = [
				'type' => 'checkbox',
				'name' => "selected[$i]",
				'value' => base64_encode(serialize($row)),
				'class' => 'form-check-input subscription-checkbox',
			];
			if ($duplicate) {
				$checkboxattrs['disabled'] = 'disabled';
			}
			echo html_writer::tag('td', html_writer::empty_tag('input', $checkboxattrs));
		
			foreach ($headers as $head) {
				if ($head === 'plan') {
					$planvalue = $row[$head] ?? '';
					$label = s($plans[$planvalue] ?? $planvalue);
				} elseif ($head === 'access_scope') {
					$scopevalue = $row[$head] ?? '';
					$label = s($scopes[$scopevalue] ?? $scopevalue);
				} else {
					$label = s($row[$head] ?? '');
				}
				
				echo html_writer::tag('td', $label);
			}
		
			if (!empty($row['_duplicate'])) {
				$label = (string) get_string('already_exists', 'local_subscriptions');
				$badge = html_writer::tag('span', s($label), ['class' => 'badge bg-secondary']);
				echo html_writer::tag('td', $badge);
			} else {
				echo html_writer::tag('td', '');
			}
		
			echo html_writer::end_tag('tr');
		}

        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
    }

	echo html_writer::tag('p', get_string('import_preview', 'local_subscriptions'));
	
	// Action buttons
	echo html_writer::start_div('form-buttons d-flex flex-wrap align-items-center gap-2 mt-4');

	
	echo html_writer::empty_tag('input', [
		'type' => 'submit',
    	'value' => get_string('confirm_import', 'local_subscriptions'),
		'class' => 'btn btn-primary',
		'id' => 'import-button',
		'disabled' => 'disabled'
	]);

	echo subscription_config::button_add_subscription();
	
	echo subscription_config::button_manage_subscription();
	
	echo html_writer::end_div();
	
	// Summary line (updated via JS)
	echo html_writer::div("
		<p class='mt-3'>
			<strong><span id='import-count'>0</span> " . get_string('import_count_valid', 'local_subscriptions') . "</strong> " . get_string('import_count_ignored', 'local_subscriptions', $ignored) . "
		</p>
	", 'text-muted');
	
    echo html_writer::end_tag('form');

	echo html_writer::script(<<<JS
		document.addEventListener('DOMContentLoaded', function () {
			const masterCheckbox = document.getElementById('select-all');
			const checkboxes = document.querySelectorAll('.subscription-checkbox:not(:disabled)');
			const importButton = document.getElementById('import-button');
			const importCount = document.getElementById('import-count');
		
			function updateUI() {
				const checked = document.querySelectorAll('.subscription-checkbox:checked:not(:disabled)').length;
				const total = checkboxes.length;
		
				importCount.textContent = checked;
				importButton.disabled = checked === 0;
		
				// Mettre à jour l'état de la master checkbox
				if (masterCheckbox) {
					if (checked === total) {
						masterCheckbox.checked = true;
						masterCheckbox.indeterminate = false;
					} else if (checked === 0) {
						masterCheckbox.checked = false;
						masterCheckbox.indeterminate = false;
					} else {
						masterCheckbox.indeterminate = true;
					}
				}
			}
		
			if (masterCheckbox) {
				masterCheckbox.addEventListener('change', function () {
					const isChecked = this.checked;
					checkboxes.forEach(cb => {
						if (!cb.disabled) cb.checked = isChecked;
					});
					updateUI();
				});
			}
		
			checkboxes.forEach(cb => {
				cb.addEventListener('change', updateUI);
			});
		
			updateUI();
		});
	JS);

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

echo html_writer::start_tag('label', [
    'for' => 'csvfile',
    'class' => 'btn btn-outline-primary',
	'style' => 'cursor: pointer; margin-bottom: 10px; margin-right: 10px; display: inline-block;'

]);
echo '📁 ' . get_string('select_csv_file', 'local_subscriptions');
echo html_writer::end_tag('label');

// Le champ input reste masqué
echo html_writer::empty_tag('input', [
    'type' => 'file',
    'name' => 'csvfile',
    'id' => 'csvfile',
    'accept' => '.csv',
    'required' => true,
    'style' => 'display: none;'
]);

// Nom du fichier sélectionné
echo html_writer::tag('div', '', ['id' => 'selected-filename', 'class' => 'text-muted']);

echo html_writer::end_div();

echo html_writer::tag('button', get_string('submit_csv_file', 'local_subscriptions'), [
    'type' => 'submit',
    'class' => 'btn btn-primary me-2',
    'id' => 'preview-button',
    'disabled' => true
]);

echo subscription_config::button_add_subscription();

echo subscription_config::button_manage_subscription();

echo html_writer::end_tag('form');

// JS pour afficher le nom du fichier sélectionné
echo html_writer::script(<<<JS
	document.getElementById('csvfile').addEventListener('change', function(e) {
		const fileName = e.target.files[0]?.name || '';
		if (fileName) {
			const label = document.querySelector("label[for='csvfile']");
			label.innerHTML = label.innerHTML.split(':')[0] + ': ' + fileName;
		}
		const previewButton = document.getElementById('preview-button');
		if (previewButton) {
			previewButton.disabled = !fileName;
		}
	});
JS);


echo $OUTPUT->footer();
