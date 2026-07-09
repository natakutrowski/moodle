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
    'classname' => '\local_subscriptions\task\subscription_rollover_task',
    'blocking'  => 0,
    'minute'    => '*/5',    // toutes les 5 min
    'hour'      => '*',
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