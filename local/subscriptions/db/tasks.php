<?php

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\local_subscriptions\task\followup_task',
        'blocking' => 0,
        'minute' => 'R', 'hour' => '*/2', 'day' => '*', 'dayofweek' => '*', 'month' => '*'
    ],
];

$tasks[] = [
    'classname' => '\local_subscriptions\task\expire_user_enrolments_task',
    'blocking' => 0,
    'minute' => 'R',
    'hour' => '*/1',
    'day' => '*',
    'dayofweek' => '*',
    'month' => '*',
];

$tasks[] = [
    
    'classname' => 'local_subscriptions\task\cleanup_login_tokens_task',
    'blocking'  => 0,
    'minute'    => 'R',
    'hour'      => '*',
    'day'       => '*',
    'month'     => '*',
    'dayofweek' => '*',
    
];

$tasks[] = [
  'classname' => 'local_subscriptions\task\repair_paid_pr_task',
  'blocking'  => 0,
  'minute'    => '*/10',
  'hour'      => '*',
  'day'       => '*',
  'month'     => '*',
  'dayofweek' => '*',
];

$tasks[] = [
    'classname' => '\local_subscriptions\task\send_expiry_reminders_task',
    'blocking'  => 0,
    'minute'    => 'R',
    'hour'      => '3',
    'day'       => '*',
    'dayofweek' => '*',
    'month'     => '*',
];


$tasks[] = [
    'classname' => '\local_subscriptions\task\enrol_scope_fill_task',
    'blocking'  => 0,
    'minute'    => 'R',
    'hour'      => '3',
    'day'       => '*',
    'dayofweek' => '*',
    'month'     => '*',
];

$tasks[] = [
    'classname' => '\local_subscriptions\task\reconcile_digital_payments_task',
    'blocking' => 0,
    'minute' => '*/15',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = [
        'classname' => 'local_subscriptions\task\run_crm_automations_task',
        'blocking' => 0,
        'minute' => '*/30',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
];

$tasks[] = [
    'classname' => '\local_subscriptions\task\run_crm_intelligence_snapshot_task',
    'blocking' => 0,
    'minute' => 'R',
    'hour' => '2',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = [
    'classname' =>
        '\local_subscriptions\task\sync_crm_inbox_task',
    'blocking' => 0,
    'minute' => '*/15',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = [
    'classname' =>
        '\local_subscriptions\task\reconcile_crm_inbox_contacts_task',
    'blocking' => 0,
    'minute' => 'R',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = [
    'classname' =>
        '\local_subscriptions\task\download_crm_inbox_attachments_task',
    'blocking' => 0,
    'minute' => '5,20,35,50',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = [
    'classname' =>
        '\local_subscriptions\task\cleanup_crm_inbox_task',
    'blocking' => 0,
    'minute' => 'R',
    'hour' => '3',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = [
    'classname' =>
        '\local_subscriptions\task\cleanup_crm_inbox_ai_results_task',
    'blocking' => 0,
    'minute' => 'R',
    'hour' => '3',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = [
    'classname' =>
        '\local_subscriptions\task\analyse_crm_inbox_task',
    'blocking' => 0,
    'minute' => '10,25,40,55',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = [
    'classname' =>
        '\local_subscriptions\task\run_crm_recommendations_task',
    'blocking' => 0,
    'minute' => '5,35',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = [
    'classname' => '\local_subscriptions\task\process_commerce_mail_queue_task',
    'blocking' => 0,
    'minute' => '*/5',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = [
    'classname' => '\local_subscriptions\task\process_personal_offer_mail_queue_task',
    'blocking' => 0,
    'minute' => '*/5',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];


$tasks[] = [
    'classname' => '\\local_subscriptions\\task\\process_commerce_mail_audit_queue_task',
    'blocking' => 0,
    'minute' => '7,22,37,52',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = [
    'classname' => '\local_subscriptions\task\process_commerce_grant_campaigns_task',
    'blocking' => 0,
    'minute' => '*/5',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];


$tasks[] = [
    'classname' => '\local_subscriptions\task\reconcile_alfa_payments_task',
    'blocking' => 0,
    'minute' => '*/5',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];
