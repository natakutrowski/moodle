<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;

global $DB, $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::VIEW_USERS);

$q = optional_param('q', '', PARAM_RAW_TRIMMED);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 25;

$url = new moodle_url(subscription_config::admin_users_page(), ['q' => $q]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('crm_users', 'local_subscriptions'));
$PAGE->set_heading(get_string('crm_users', 'local_subscriptions'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles.css'));

$params = [];
$where = "u.deleted = 0";

if ($q !== '') {
    $where .= " AND (" .
        $DB->sql_like('u.firstname', ':q1', false, false) . " OR " .
        $DB->sql_like('u.lastname', ':q2', false, false) . " OR " .
        $DB->sql_like('u.email', ':q3', false, false) .
    ")";
    $like = '%' . $DB->sql_like_escape($q) . '%';
    $params = ['q1' => $like, 'q2' => $like, 'q3' => $like];
}

$total = $DB->count_records_sql("
    SELECT COUNT(1)
      FROM {user} u
     WHERE $where
", $params);

$users = $DB->get_records_sql("
    SELECT
    u.id,
    u.firstname,
    u.lastname,
    u.firstnamephonetic,
    u.lastnamephonetic,
    u.middlename,
    u.alternatename,
    u.email,
    u.country,
    u.timecreated,
    u.lastaccess
      FROM {user} u
     WHERE $where
  ORDER BY u.lastname ASC, u.firstname ASC, u.id DESC
", $params, $page * $perpage, $perpage);

echo $OUTPUT->header();

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => new moodle_url(subscription_config::admin_users_page()),
    'class' => 'mb-4 d-flex gap-2',
]);

echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'q',
    'value' => s($q),
    'class' => 'form-control',
    'placeholder' => get_string('crm_search_user_placeholder', 'local_subscriptions'),
]);

echo html_writer::tag('button', get_string('search'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);

echo html_writer::end_tag('form');

if (!$users) {
    echo $OUTPUT->notification(get_string('crm_no_users_found', 'local_subscriptions'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('user'),
    get_string('email'),
    get_string('country'),
    get_string('subscriptions', 'local_subscriptions'),
    get_string('digital_purchases', 'local_subscriptions'),
    get_string('lastaccess'),
];

foreach ($users as $user) {
    $subcount = $DB->count_records('user_subscription', ['userid' => $user->id]);

    $digitalcount = $DB->count_records_select(
        'subscription_digital_payment_request',
        '(userid = :userid OR email = :email)',
        ['userid' => $user->id, 'email' => $user->email]
    );

    $table->data[] = [
        html_writer::link(
            new moodle_url(subscription_config::admin_user_view_page(), ['id' => $user->id]),
            fullname($user)
        ),
        s($user->email),
        $user->country ?: '-',
        $subcount,
        $digitalcount,
        !empty($user->lastaccess) ? userdate($user->lastaccess, '%d/%m/%y') : '-',
    ];
}

echo html_writer::table($table);

echo $OUTPUT->paging_bar($total, $page, $perpage, $url);

echo $OUTPUT->footer();