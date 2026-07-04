<?php

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminNavigation;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::VIEW_DASHBOARD);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::admin_dashboard_page()));
$PAGE->set_title(get_string('admin_dashboard', 'local_subscriptions'));
$PAGE->set_heading(get_string('admin_dashboard', 'local_subscriptions'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles.css'));

echo $OUTPUT->header();

$cards = [
    [
        'title' => get_string('admin_card_crm_users_title', 'local_subscriptions'),
        'description' => get_string('admin_card_crm_users_desc', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::admin_users_page()),
        'icon' => '👤',
        'capability' => Capabilities::VIEW_USERS,
    ],
    [
        'title' => get_string('admin_card_user_subscriptions_title', 'local_subscriptions'),
        'description' => get_string('admin_card_user_subscriptions_desc', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::user_subscriptions_page()),
        'icon' => '📋',
        'capability' => Capabilities::MANAGE_SUBSCRIPTIONS,
    ],
    [
        'title' => get_string('admin_card_add_subscription_title', 'local_subscriptions'),
        'description' => get_string('admin_card_add_subscription_desc', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::add_manual_subscription_page()),
        'icon' => '➕',
        'capability' => Capabilities::MANAGE_SUBSCRIPTIONS,
    ],
    [
        'title' => get_string('admin_card_import_csv_title', 'local_subscriptions'),
        'description' => get_string('admin_card_import_csv_desc', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::import_csv_page()),
        'icon' => '📂',
        'capability' => Capabilities::MANAGE_SUBSCRIPTIONS,
    ],
    [
        'title' => get_string('admin_card_plans_title', 'local_subscriptions'),
        'description' => get_string('admin_card_plans_desc', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::manage_page()),
        'icon' => '🧩',
        'capability' => Capabilities::MANAGE_CONFIGURATION,
    ],
    [
        'title' => get_string('admin_card_digital_products_title', 'local_subscriptions'),
        'description' => get_string('admin_card_digital_products_desc', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::digital_products_admin_page()),
        'icon' => '📦',
        'capability' => Capabilities::MANAGE_DIGITAL,
    ],
    [
        'title' => get_string('admin_card_digital_purchases_title', 'local_subscriptions'),
        'description' => get_string('admin_card_digital_purchases_desc', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::digital_purchases_admin_page()),
        'icon' => '🧾',
        'capability' => Capabilities::VIEW_DIGITAL,
    ],
    [
        'title' => get_string('admin_card_digital_stats_title', 'local_subscriptions'),
        'description' => get_string('admin_card_digital_stats_desc', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::digital_sales_stats_admin_page()),
        'icon' => '📊',
        'capability' => Capabilities::VIEW_STATISTICS,
    ],
];

echo html_writer::start_div('local-subscriptions-admin-dashboard');

echo html_writer::tag(
    'p',
    get_string('admin_dashboard_intro', 'local_subscriptions'),
    ['class' => 'lead text-muted mb-4']
);

echo AdminNavigation::quick_actions();

echo html_writer::start_div('row');

foreach ($cards as $card) {

    if (!AdminSecurity::can($card['capability'])) {
        continue;
    }

    echo html_writer::start_div('col-md-6 col-xl-4 mb-4');

    echo html_writer::link(
        $card['url'],
        html_writer::div(
            html_writer::div($card['icon'], 'local-subscriptions-admin-card-icon') .
            html_writer::tag('h3', $card['title'], ['class' => 'h5 mb-2']) .
            html_writer::tag('p', $card['description'], ['class' => 'text-muted mb-0']),
            'card-body'
        ),
        ['class' => 'card h-100 local-subscriptions-admin-card text-decoration-none']
    );

    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();