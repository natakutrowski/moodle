<?php
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_manager;
use local_subscriptions\subscription_config;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\support\SubsPresenter;

class local_subscriptions_user_subs_renderer extends plugin_renderer_base {

    /**
     * Affiche le tableau de prévisualisation d’un import CSV.
     *
     * @param array $rows Données valides du CSV
     * @param array $headers Liste des en-têtes (ex: ['email', 'start_date', ...])
     * @return string HTML du tableau complet avec actions
     */
    public function render_import_preview_table(array $rows, array $headers): string {

        $out = '';

        $headers_string = [
            'firstname'  => get_string('firstname'),
            'lastname'   => get_string('lastname'),
            'email' => get_string('email', 'local_subscriptions'),
            'start_date' => get_string('start_date', 'local_subscriptions'),
            'plan' => get_string('plan', 'local_subscriptions'),
            'price' => get_string('price', 'local_subscriptions'),
            'currency' => get_string('currency', 'local_subscriptions'),
        ];

        $out .= html_writer::start_tag('table', ['class' => 'generaltable import-preview']);
        $out .= html_writer::start_tag('thead');
        $out .= html_writer::start_tag('tr');

        $out .= html_writer::tag('th', html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'id' => 'select-all',
            'class' => 'form-check-input'
        ]));

        foreach ($headers_string as $head) {
            $out .= html_writer::tag('th', s($head));
        }

        $out .= html_writer::tag('th', ''); // pour badge ou vide
        $out .= html_writer::end_tag('tr') . html_writer::end_tag('thead');
        $out .= html_writer::start_tag('tbody');

        foreach ($rows as $i => $row) {
            $duplicate = !empty($row['_duplicate']);
            $rowclass = $duplicate ? 'text-muted bg-light' : '';
            $out .= html_writer::start_tag('tr', ['class' => $rowclass]);

            // Checkbox
            $checkboxattrs = [
                'type' => 'checkbox',
                'name' => "selected[$i]",
                'value' => base64_encode(serialize($row)),
                'class' => 'form-check-input subscription-checkbox',
            ];
            if ($duplicate) {
                $checkboxattrs['disabled'] = 'disabled';
            }
            $out .= html_writer::tag('td', html_writer::empty_tag('input', $checkboxattrs));

            foreach ($headers as $head) {
                $value = $row[$head] ?? '';

                if ($head === 'plan') {
                    $planname = trim($value);
                    $planid = subscription_manager::get_plan_id_by_name($planname);
                    $translation = $planid ? subscription_manager::get_translated_plan_name($planid, current_language()) : null;
                    $label = $translation ?: s($planname);
                } elseif ($head === 'price') {
                    $label = s($value);
                } elseif ($head === 'currency') {
                    $label = strtoupper(s($value));
                } elseif (in_array($head, ['firstname', 'lastname'])) {
                    $label = s(ucfirst($value));
                } else {
                    $label = s($value);
                }

                $out .= html_writer::tag('td', $label);
            }

            // Badge si doublon
            if ($duplicate) {
                $label = get_string('already_exists', 'local_subscriptions');
                $badge = html_writer::tag('span', s($label), ['class' => 'badge bg-secondary']);
                $out .= html_writer::tag('td', $badge);
            } else {
                $out .= html_writer::tag('td', '');
            }

            $out .= html_writer::end_tag('tr');
        }

        $out .= html_writer::end_tag('tbody');
        $out .= html_writer::end_tag('table');

        return $out;
    }


    public function render_import_actions_and_summary(int $ignored): string {
        $html = html_writer::start_div('form-buttons d-flex flex-wrap align-items-center gap-2 mt-4');

        // Bouton d'import
        $html .= html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('import_subscriptions', 'local_subscriptions'),
            'class' => 'btn btn-primary',
            'id' => 'import-button',
            'disabled' => 'disabled'
        ]);

        // Autres boutons
        $html .= subscription_config::button_add_subscription();
        $html .= subscription_config::button_manage_subscription();

        $html .= html_writer::end_div();

        // Ligne de résumé
        $html .= html_writer::div("
            <p class='mt-3'>
                <strong><span id='import-count'>0</span> " . get_string('import_count_valid', 'local_subscriptions') . "</strong> " .
                get_string('import_count_ignored', 'local_subscriptions', $ignored) . "
            </p>
        ", 'text-muted');

        return $html;
    }

    public function render_import_checkbox_script(): string {
        return html_writer::script(<<<JS
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
    }

    public function render_csv_upload_form(): string {
        $output = html_writer::start_tag('form', [
            'method' => 'post',
            'enctype' => 'multipart/form-data',
            'class' => 'csv-upload-form'
        ]);
        
        $output .= html_writer::empty_tag('input', ['type'=>'hidden','name'=>'sesskey','value'=>sesskey()]);
        
        $output .= html_writer::start_div('form-group');

        $output .= html_writer::start_tag('label', [
            'for' => 'csvfile',
            'class' => 'btn btn-outline-primary',
            'style' => 'cursor: pointer; margin-bottom: 10px; margin-right: 10px; display: inline-block;'
        ]);
        $output .= '📁 ' . get_string('select_csv_file', 'local_subscriptions');
        $output .= html_writer::end_tag('label');

        $output .= html_writer::empty_tag('input', [
            'type' => 'file',
            'name' => 'csvfile',
            'id' => 'csvfile',
            'accept' => '.csv',
            'required' => true,
            'style' => 'display: none;'
        ]);

        $output .= html_writer::tag('div', '', ['id' => 'selected-filename', 'class' => 'text-muted']);
        $output .= html_writer::end_div();

        $output .= html_writer::tag('button', get_string('submit_csv_file', 'local_subscriptions'), [
            'type' => 'submit',
            'class' => 'btn btn-primary me-2',
            'id' => 'preview-button',
            'disabled' => true
        ]);

        $output .= subscription_config::button_add_subscription();
        $output .= subscription_config::button_manage_subscription();

        $output .= html_writer::end_tag('form');

        // Script pour afficher le nom du fichier sélectionné
        $output .= html_writer::script(<<<JS
            document.getElementById('csvfile').addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name || '';
                if (fileName) {
                    const label = document.querySelector("label[for='csvfile']");
                    label.innerHTML = '📁 ' + fileName;
                }
                const previewButton = document.getElementById('preview-button');
                if (previewButton) {
                    previewButton.disabled = !fileName;
                }
            });
        JS);

        return $output;
    }

    public function render_import_confirmation_form(array $validrows, int $importid): string {
        $source = base64_encode(serialize($validrows));

        $output = html_writer::start_tag('form', [
            'method' => 'post',
            'action' => new moodle_url(subscription_config::process_csv_page())
        ]);

        $output .= html_writer::empty_tag('input', ['type'=>'hidden','name'=>'sesskey','value'=>sesskey()]);

        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'importid',
            'value' => $importid
        ]);

        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sourcefile',
            'value' => $source
        ]);

        return $output;
    }
    public function render_import_summary(int $imported, array $skipped): string {
        $output = '';

        // Message succès
        $output .= html_writer::div('✅ ' . get_string('import_success_count', 'local_subscriptions', $imported), 'subscription-message success');

        // Lignes ignorées
        if (!empty($skipped)) {
            $output .= html_writer::div('⚠️ ' . get_string('import_skipped', 'local_subscriptions'), 'subscription-message warning');
            $output .= html_writer::start_tag('ul');
            foreach ($skipped as $s) {
                $output .= html_writer::tag('li', implode(' | ', $s['data']) . ' (' . $s['reason'] . ')');
            }
            $output .= html_writer::end_tag('ul');
        }

        return $output;
    }

    public function render_manual_subscription_form($users, $plans) {
        $output = '';
        // Form
        $output .= html_writer::start_div('subscription-card');
        $output .= html_writer::start_tag('form', ['method' => 'post', 'class' => 'mform']);

        $output .= html_writer::empty_tag('input', [
            'type'  => 'hidden',
            'name'  => 'sesskey',
            'value' => sesskey(),
        ]);

        // User
        $output .= html_writer::div(
            html_writer::label(get_string('select_user', 'local_subscriptions'), 'userid') .
            html_writer::select($users, 'userid', '', ['' => '—'], ['class' => 'form-control select2']),
            'form-group'
        );

        // Plan
        $output .= html_writer::div(
            html_writer::label(get_string('plan', 'local_subscriptions'), 'plan') .
            html_writer::select($plans, 'plan', '', ['' => '—'], ['class' => 'form-control select2','escape' => false]),
            'form-group'
        );

        $output .= html_writer::start_div('form-group', [
            'id' => 'plan-details-wrapper',
            'style' => 'display: none; background: #fff; border: 1px solid #ccc; padding: 16px; margin-top: 15px; border-radius: 6px;'
        ]);

        // Description
        $output .= html_writer::tag('h5', get_string('description', 'local_subscriptions'));
        $output .= html_writer::div('', 'form-group', ['id' => 'plan-description']);

        // Scope & durée
        $output .= html_writer::tag('h5', get_string('scope_and_duration', 'local_subscriptions'));
        $output .= html_writer::div('', 'form-group', ['id' => 'plan-meta']);

        // Liste des cours
        $output .= html_writer::tag('h5', get_string('courses_included', 'local_subscriptions'));
        $output .= html_writer::start_tag('ul', ['id' => 'plan-courses', 'style' => 'padding-left: 18px;']);
        $output .= html_writer::end_tag('ul');

        // Prix / devise
        $output .= html_writer::tag('h5', get_string('select_price', 'local_subscriptions'));
        $output .= html_writer::div(
            html_writer::select([], 'price_currency', '', ['' => '—'], ['class' => 'form-control', 'id' => 'price-currency']),
            'form-group'
        );

        $output .= html_writer::end_div();

        // Start date
        $output .= html_writer::div(
            html_writer::label(get_string('start_date', 'local_subscriptions'), 'start_date') .
            html_writer::empty_tag('input', [
                'type' => 'date',
                'name' => 'start_date',
                'class' => 'form-control',
                'value' => date('Y-m-d')
            ]),
            'form-group'
        );

        // Buttons
        $output .= html_writer::div(
            html_writer::tag('button', '📘 '.get_string('submit_sub', 'local_subscriptions'), [
                'type' => 'submit',
                'name' => 'action',
                'value' => 'enrol',
                'class' => 'btn btn-primary me-2'
            ]) .
            html_writer::tag('button', '🧪 '.get_string('submit_sub_test', 'local_subscriptions'), [
                'type' => 'submit',
                'name' => 'action',
                'value' => 'enrol_test_only',
                'class' => 'btn btn-outline-primary me-4'
            ]) .    
            subscription_config::button_manage_subscription() .
            subscription_config::button_import_csv(),
            'form-group d-flex flex-wrap align-items-center',
            ['style' => 'margin-top: 30px; gap: 10px;']
        );

        $output .= html_writer::end_tag('form');
        $output .= html_writer::end_div(); // Fin du card

        $output .= html_writer::script(<<<JS
            document.addEventListener('DOMContentLoaded', () => {
                const planSelect = document.querySelector('select[name="plan"]');
                const priceSelect = document.getElementById('price-currency');
                const planDetailsWrapper = document.getElementById('plan-details-wrapper');
                const descDiv = document.getElementById('plan-description');
                const metaDiv = document.getElementById('plan-meta');
                const courseList = document.getElementById('plan-courses');

                planSelect.addEventListener('change', () => {
                    const planid = planSelect.value;
                    if (!planid) return;

                    fetch(M.cfg.wwwroot + '/local/subscriptions/ajax/get_plan_details.php?planid=' + planid)
                        .then(res => res.json())
                        .then(data => {
                            // Description
                            descDiv.innerHTML = data.description || '-';

                            // Scope et durée
                            metaDiv.innerHTML = `<strong>\${data.accessscope}</strong> — \${data.duration}`;

                            // Cours ligne par ligne
                            courseList.innerHTML = '';
                            (data.courses || []).forEach(course => {
                                const li = document.createElement('li');
                                li.textContent = course;
                                courseList.appendChild(li);
                            });

                            // Prix
                            priceSelect.innerHTML = '';
                            (data.prices || []).forEach(priceStr => {
                                const opt = document.createElement('option');
                                const [price, currency] = priceStr.trim().split(' ');
                                opt.value = `\${price}|\${currency}`; // c'est ce qui est attendu côté PHP
                                opt.textContent = priceStr;

                                priceSelect.appendChild(opt);
                            });


                            // Affiche le tableau
                            planDetailsWrapper.style.display = 'block';
                        });
                });
            });
        JS);

        return $output;
    }

    public function render_plan_popover(int $planid, int $subscriptionid): string {
        global $DB;

        $planinfo = subscription_config::get_plan_info($planid);
        $planname = $planinfo['name'] ?? '-';
        $description = $planinfo['description'] ?? '-';
        $durationkey = $planinfo['duration_key'] ?? '-';

        $scoperecord = subscription_manager::get_access_scope_from_planid($planid);
        $scopename = $scoperecord->name ?? '-';
        $coursenames = [];

        if (!empty($scoperecord->course_ids)) {
            $ids = explode(',', $scoperecord->course_ids);
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids);

            if (!empty($ids)) {
                list($in_sql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_QM);
                $courserecords = $DB->get_records_select('course', "id $in_sql", $params, 'fullname ASC', 'id, fullname');

                foreach ($courserecords as $c) {
                    $coursenames[] = format_string($c->fullname);
                }
            }
        }

        // Génère le contenu HTML à afficher dans la popover
        $popovercontent = '<div class="popover-plan-content" style="font-size: 0.95rem;">';
        $popovercontent .= '<table style="width:100%; border-collapse:collapse;">';

        $add_row = function($label, $value) use (&$popovercontent) {
            $popovercontent .= '<tr>';
            $popovercontent .= '<td style="font-weight:bold; padding:6px 8px; vertical-align:top; white-space:nowrap;">' . $label . ' :</td>';
            $popovercontent .= '<td style="padding:6px 8px;">' . $value . '</td>';
            $popovercontent .= '</tr>';
        };

        $add_row(get_string('description', 'local_subscriptions'), s($description));
        $add_row(get_string('popover_duration', 'local_subscriptions'), s(subscription_config::get_plans()[$durationkey]));
        $add_row(get_string('popover_scope', 'local_subscriptions'), s($scopename));

        $courselist = !empty($coursenames)
            ? '<ul style="padding-left:18px; margin: 4px 0;">' . implode('', array_map(fn($c) => '<li>' . s($c) . '</li>', $coursenames)) . '</ul>'
            : '<em>' . get_string('popover_no_courses', 'local_subscriptions') . '</em>';

        $add_row(get_string('popover_courses', 'local_subscriptions'), $courselist);

        $popovercontent .= '</table></div>';

        // Ajoute un div caché dans la page avec ce contenu
        echo html_writer::div($popovercontent, '', [
            'id' => 'popover-content-' . $subscriptionid,
            'class' => 'd-none'
        ]);

        // Affiche le nom + icône popover
        $icon = '<i class="bi bi-info-circle-fill ms-1 text-muted plan-info-icon" 
            data-bs-toggle="popover" 
            data-bs-html="true" 
            data-bs-trigger="click"
            data-bs-placement="right" 
            data-popover-id="popover-content-' . $subscriptionid . '" 
            style="cursor:pointer;"></i>';

        return s($planname) . ' ' . $icon;
    }
 
    public  function render_user_subscriptions_page(array $subscriptions): string {
        global $DB, $OUTPUT;

        if (empty($subscriptions)) {
            return $OUTPUT->notification(get_string('no_active_subscriptions', 'local_subscriptions'), 'info');
        }

        $output = '';

        // Formulaire d'édition
        $output .= html_writer::start_tag('form', ['method' => 'post', 'action' => '', 'id' => 'editform']);
        $output .= html_writer::start_div('subscription-controls');
        $output .= html_writer::tag('button', '✏️ ' . get_string('edit_subscriptions', 'local_subscriptions'), [
            'type' => 'button', 'id' => 'edit-button', 'class' => 'btn btn-primary me-2'
        ]);
        $output .= subscription_config::button_add_subscription();
        $output .= subscription_config::button_import_csv();
        $output .= html_writer::end_div();

        // Tableau HTML
        $table = new \html_table();
        $table->head = [
            ' ',' ', get_string('user', 'local_subscriptions'), get_string('plan', 'local_subscriptions'),
            get_string('price', 'local_subscriptions'), get_string('start_date', 'local_subscriptions'),
            get_string('end_date', 'local_subscriptions'), get_string('status', 'local_subscriptions'),
            get_string('creation_date', 'local_subscriptions'),
        ];
        $table->attributes['class'] = 'subscription-table';
        $table->id = 'subscriptions-table';
        $table->data = [];

        // Cache simple des plans
        $planCache = [];

        foreach ($subscriptions as $sub) {
            $user = $DB->get_record('user', ['id' => $sub->userid], 'id, firstname, lastname, email, firstnamephonetic, lastnamephonetic, middlename, alternatename');
            $username = fullname($user) . " ({$user->email})";
            $modalid = 'subModal'.$sub->id;

            // Assure le plan
            if (!isset($planCache[$sub->planid])) {
                $planCache[$sub->planid] = $DB->get_record(
                    'subscription_plan',
                    ['id' => $sub->planid],
                    'id,name,duration_key,accessscopeid',
                    IGNORE_MISSING
                ) ?: (object)[
                    'id' => $sub->planid,
                    'name' => get_string('unknown_plan', 'local_subscriptions'),
                    'duration_key' => ''
                ];
            }
            $plan = $planCache[$sub->planid];

            // Modal
            $rows = SubsPresenter::rows(
                $sub,
                $plan,
                function (float $amount, string $cur): string {
                    return format_float($amount, 2).' '.strtoupper($cur);
                },
                'admin'
            );

            // Table HTML
            $tableModal = \html_writer::start_tag('table', ['class'=>'table table-sm mb-0']);
            foreach ($rows as [$k,$v]) {
                $tableModal .= '<tr>'
                    .  '<th class="text-muted" style="width:28%;white-space:nowrap;">'.s($k).'</th>'
                    .  '<td class="fw-semibold">'.(is_string($v) ? $v : s($v)).'</td>'
                    .  '</tr>';
            }
            $tableModal .= \html_writer::end_tag('table');

            // Modal markup Bootstrap 5
            echo \html_writer::start_div('modal fade', ['id'=>$modalid, 'tabindex'=>'-1', 'aria-hidden'=>'true']);
            echo \html_writer::start_div('modal-dialog modal-lg modal-dialog-scrollable');
                echo \html_writer::start_div('modal-content');
                echo \html_writer::div(
                        \html_writer::tag('h5', get_string('subscription_details', 'local_subscriptions').' #'.$sub->id, ['class'=>'modal-title'])
                    . \html_writer::tag('button','', ['type'=>'button','class'=>'btn-close','data-bs-dismiss'=>'modal','aria-label'=>'Close']),
                    'modal-header d-flex align-items-center justify-content-between'
                );
                echo \html_writer::div($tableModal, 'modal-body bg-light');
                echo \html_writer::div(
                        \html_writer::tag('button', 'Close', ['class'=>'btn btn-secondary','data-bs-dismiss'=>'modal']),
                    'modal-footer'
                );
                echo \html_writer::end_div(); // content
            echo \html_writer::end_div();   // dialog
            echo \html_writer::end_div();     // modal

            $row = [];

            // Bouton icône qui ouvre la modal
            $row[] = html_writer::link(
                '#'.$modalid,
                $OUTPUT->pix_icon('i/info', get_string('details'), 'moodle'),
                ['data-bs-toggle' => 'modal', 'class' => 'btn btn-link p-0']
            );

            $row[] = html_writer::empty_tag('input', [
                'type' => 'checkbox', 'name' => 'selected[]', 'value' => $sub->id,
                'class' => 'subscription-checkbox edit-checkbox form-check-input d-none'
            ]);
            $row[] = $username;

            if (!empty($sub->planid)) {
                
                // --- Récupère les plans actifs + traduits ---
                $plans = [];
                foreach ($DB->get_records('subscription_plan', ['is_active' => 1], 'name ASC') as $plan) {
                    $translation = subscription_manager::get_translated_plan_name($plan->id, current_language());
                    $label = $translation ?: '<i>' . format_string($plan->name) . '</i>';
                    $plans[$plan->id] = $label;
                    $is_recurring[$plan->id] = $plan->is_recurring;
                }

                // --- Génère le <select> ---
                $selectoptions = [];
                foreach ($plans as $pid => $label) {
                    $selected = ($pid == $sub->planid) ? ' selected' : '';
                    $selectoptions[] = '<option value="' . $pid . '"' . $selected . '>' . $label . '</option>';
                }

                if (!empty($is_recurring[$sub->planid])) {
                    // petite icône + libellé discret
                    $icon_recurring = ' ' . \html_writer::span(
                        $OUTPUT->pix_icon('i/reload', get_string('badge_recurring', 'local_subscriptions'), 'moodle'),
                        'align-middle text-info'
                    );
                } else {
                    $icon_recurring = '';
                }

                $row[] = html_writer::div(
                    html_writer::tag('select', implode("\n", $selectoptions), [
                        'name' => "plan[{$sub->id}]",
                        'class' => 'form-select edit-input d-none'
                    ]) .
                    html_writer::div($this->render_plan_popover($sub->planid, $sub->id) . $icon_recurring, 'edit-display'),
                    'plan-cell-wrapper'
                );
            } else {
                $row[] = '-';
            }

            $price = isset($sub->pricepaid) && $sub->pricepaid > 0
                ? number_format($sub->pricepaid, 2, ',', ' ') . ' ' . strtoupper($sub->currency ?? '')
                : '-';
            $row[] = $price;

            $row[] = html_writer::empty_tag('input', [
                'type' => 'date', 'name' => "start[{$sub->id}]", 'value' => date('Y-m-d', $sub->start_date),
                'class' => 'form-control edit-input d-none'
            ]) . html_writer::tag('span', date('Y-m-d', $sub->start_date), ['class' => 'edit-display']);

            $row[] = html_writer::empty_tag('input', [
                'type' => 'date', 'name' => "end[{$sub->id}]", 'value' => date('Y-m-d', $sub->end_date),
                'class' => 'form-control edit-input d-none'
            ]) . html_writer::tag('span', date('Y-m-d', $sub->end_date), ['class' => 'edit-display']);

            $row[] = html_writer::tag('span', SubsPresenter::render_status_badge($sub->status));
            $row[] = html_writer::tag('span', date('Y-m-d H:i:s', $sub->creation_date));

            $table->data[] = $row;
        }

        $output .= html_writer::table($table);

        $output .= html_writer::empty_tag('input', [
            'type' => 'submit', 'name' => 'save',
            'value' => '💾 ' . get_string('save_modifications', 'local_subscriptions'),
            'class' => 'btn btn-primary mt-3 me-2 d-none disabled-btn',
            'id' => 'save-button', 'disabled' => true
        ]);
        $output .= html_writer::empty_tag('input', [
            'type' => 'submit', 'name' => 'delete',
            'value' => '🗑️ ' . get_string('delete_selected', 'local_subscriptions'),
            'class' => 'btn btn-danger mt-3 me-2 d-none disabled-btn',
            'id' => 'delete-button', 'disabled' => true
        ]);
        $output .= html_writer::empty_tag('input', [
            'type' => 'button', 'value' => get_string('cancel'),
            'class' => 'btn btn-secondary mt-3 me-2 d-none',
            'id' => 'cancel-button', 'onclick' => 'location.reload()'
        ]);
        $output .= html_writer::span('', 'badge badge-info ml-3 d-none', ['id' => 'checked-count']);
        $output .= html_writer::end_tag('form');

        return $output;
    }

}