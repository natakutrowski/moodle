<?php
require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;
use local_subscriptions\subscription_config;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::manage_subscription_page()));
$PAGE->set_title(get_string('manage_subscriptions', 'local_subscriptions'));
$PAGE->set_heading(get_string('manage_subscriptions', 'local_subscriptions'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected'])) {

    $selected = $_POST['selected'];
    $updated = 0;
    $deleted = 0;

    if (isset($_POST['save'])) {
        foreach ($selected as $subid) {
            $plan = $_POST['plan'][$subid] ?? null;
            $start = $_POST['start'][$subid] ?? null;
            $end = $_POST['end'][$subid] ?? null;

            if ($plan && $start && $end) {
                $starttimestamp = strtotime($start);
                $endtimestamp = strtotime($end);

                $subscription = $DB->get_record('user_subscription', ['id' => $subid], '*', IGNORE_MISSING);
                                
				if ($subscription) {
					$subscription->plan = $plan;
					$subscription->start_date = $starttimestamp;
					$subscription->end_date = $endtimestamp;
					$subscription->last_update = time();
					$DB->update_record('user_subscription', $subscription);
					$updated++;
				}
            }
        }

        \core\notification::success(get_string('updated_subscriptions', 'local_subscriptions', $updated));
    }

    if (isset($_POST['delete'])) {

		foreach ($selected as $subid) {
			$subscription = $DB->get_record('user_subscription', ['id' => $subid], '*', IGNORE_MISSING);
			if (!$subscription) {
				continue;
			}
		
			// ✂️ Désinscription des cours liés à access_scope
			\local_subscriptions\subscription_manager::unenrol_user_from_scope($subscription->userid, $subscription->access_scope);
		
			$DB->delete_records('user_subscription', ['id' => $subid]);
			$deleted++;
		}
        \core\notification::success(get_string('delete_subscriptions', 'local_subscriptions', $deleted));
    }
}

echo $OUTPUT->header();

// Récupérer les abonnements actifs
global $DB;
$subscriptions = $DB->get_records('user_subscription', ['status' => 'active'], 'start_date DESC');

if (empty($subscriptions)) {
    echo $OUTPUT->notification(get_string('no_active_subscriptions', 'local_subscriptions'), 'info');
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_tag('form', ['method' => 'post', 'action' => new moodle_url(subscription_config::manage_subscription_page()), 'id' => 'editform']);

echo html_writer::start_div('subscription-controls');

echo html_writer::tag('button', '✏️ ' . get_string('edit_subscriptions', 'local_subscriptions'), [
    'type' => 'button',
    'id' => 'edit-button',
    'class' => 'btn btn-primary me-2'
]);

echo subscription_config::button_add_subscription();
echo subscription_config::button_import_csv();
	
echo html_writer::end_div();

$table = new html_table();
$table->head = [
    ' ', // Checkbox column
    get_string('user', 'local_subscriptions'),
    get_string('plan', 'local_subscriptions'),
    get_string('access_scope', 'local_subscriptions'),
    get_string('start_date', 'local_subscriptions'),
    get_string('end_date', 'local_subscriptions'),
    get_string('status', 'local_subscriptions'),
    get_string('creation_date', 'local_subscriptions'),
];

$table->attributes['class'] = 'subscription-table';
$table->id = 'subscriptions-table';
$table->data = [];

$plans = subscription_config::get_plans();
$scopes = subscription_config::get_scopes();

foreach ($subscriptions as $sub) {

    $row = [];

    // Checkbox (hidden by default)
    $row[] = html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'selected[]',
        'value' => $sub->id,
        'class' => 'subscription-checkbox edit-checkbox form-check-input d-none'
    ]);

    // Static user name
    $user = $DB->get_record('user', ['id' => $sub->userid], 'id, firstname, lastname, email');
	$username = fullname($user) . " ({$user->email})";
	$row[] = $username;

    // Plan selector (hidden by default, shown in edit mode)
    $row[] = html_writer::select($plans, "plan[{$sub->id}]", $sub->plan, false, ['class' => 'form-control edit-input d-none']) .
             html_writer::tag('span', $plans[$sub->plan], ['class' => 'edit-display']);

    // Access scope
    $row[] = html_writer::tag('span', $scopes[$sub->access_scope]);

    // Start date
    $row[] = html_writer::empty_tag('input', [
                    'type' => 'date',
                    'name' => "start[{$sub->id}]",
                    'value' => date('Y-m-d', $sub->start_date),
                    'class' => 'form-control edit-input d-none'
               ]) .
               html_writer::tag('span', date('Y-m-d', $sub->start_date), ['class' => 'edit-display']);

    // End date
    $row[] = html_writer::empty_tag('input', [
                    'type' => 'date',
                    'name' => "end[{$sub->id}]",
                    'value' => date('Y-m-d', $sub->end_date),
                    'class' => 'form-control edit-input d-none'
               ]) .
               html_writer::tag('span', date('Y-m-d', $sub->end_date), ['class' => 'edit-display']);

    // Status (not editable)
    $row[] = html_writer::tag('span', $sub->status);
    
    // Creation date
    $row[] = html_writer::tag('span', date('Y-m-d H:i:s', $sub->creation_date));

    $table->data[] = $row;
}

echo html_writer::table($table);

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'name' => 'save',
    'value' => '💾 ' . get_string('save_modifications', 'local_subscriptions'),
    'class' => 'btn btn-primary mt-3 me-2 d-none disabled-btn',
    'id' => 'save-button',
    'disabled' => true
]);

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'name' => 'delete',
    'value' => '🗑️ ' . get_string('delete_selected', 'local_subscriptions'),
    'class' => 'btn btn-danger mt-3 me-2 d-none disabled-btn',
    'id' => 'delete-button',
    'disabled' => true
]);

echo html_writer::empty_tag('input', [
    'type' => 'button',
    'value' => get_string('cancel'),
    'class' => 'btn btn-secondary mt-3 me-2 d-none',
    'id' => 'cancel-button',
    'onclick' => 'location.reload()'
]);

echo html_writer::span('', 'badge badge-info ml-3 d-none', ['id' => 'checked-count']);

echo html_writer::end_tag('form');

// Script JS pour gérer l’édition
echo html_writer::script(<<<JS
document.addEventListener('DOMContentLoaded', function () {
    const updateState = () => {
        const checkboxes = document.querySelectorAll('.subscription-checkbox');
        const saveBtn = document.getElementById('save-button');
        const deleteBtn = document.getElementById('delete-button');
        const badge = document.getElementById('checked-count');

        let count = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) count++;
        });

        const active = count > 0;

        if (saveBtn) {
            saveBtn.disabled = !active;
            saveBtn.classList.toggle('disabled-btn', !active);
        }

        if (deleteBtn) {
            deleteBtn.disabled = !active;
            deleteBtn.classList.toggle('disabled-btn', !active);
        }

        if (badge) {
            badge.textContent = count + ' sélectionné' + (count > 1 ? 's' : '');
            badge.classList.toggle('d-none', count === 0);
        }
    };

    document.getElementById('edit-button')?.addEventListener('click', () => {
        document.querySelectorAll('.edit-checkbox').forEach(cb => cb.classList.remove('d-none'));
        document.querySelectorAll('.edit-input').forEach(el => el.classList.remove('d-none'));
        document.querySelectorAll('.edit-display').forEach(el => el.style.display = 'none');
        document.getElementById('edit-button').disabled = true;
        document.getElementById('edit-button').classList.toggle('disabled-btn', true);
        document.getElementById('save-button').classList.remove('d-none');
        document.getElementById('delete-button').classList.remove('d-none');
        document.getElementById('cancel-button').classList.remove('d-none');

        updateState();
    });

    document.querySelectorAll('.subscription-checkbox').forEach(cb => {
        cb.addEventListener('change', updateState);
    });

    updateState();
});
JS);

echo $OUTPUT->footer();
