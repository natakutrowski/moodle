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

$tasks[] = [
    'classname' => '\\local_subscriptions\\task\\reconcile_stripe_payments_task',
    'blocking' => 0,
    'minute' => '*/5',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];


$tasks[] = [
    'classname' => '\local_subscriptions\task\reconcile_paypal_payments_task',
    'blocking' => 0,
    'minute' => '*/5',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];


$tasks[] = [
    'classname' => '\local_subscriptions\task\cleanup_abandoned_guest_checkouts_task',
    'blocking' => 0,
    'minute' => 'R',
    'hour' => '4',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];


$tasks[] = [
    'classname' => '\local_subscriptions\task\process_marketing_mail_campaigns_task',
    'blocking' => 0,
    'minute' => '*',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = [
    'classname' => '\local_subscriptions\task\process_marketing_mail_queue_task',
    'blocking' => 0,
    'minute' => '*/5',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

$tasks[] = ['classname' => '\\local_subscriptions\\task\\process_personal_offer_scheduled_campaigns_task', 'blocking'=>0, 'minute'=>'*', 'hour'=>'*', 'day'=>'*', 'month'=>'*', 'dayofweek'=>'*'];

$tasks[] = [
    'classname' => '\local_subscriptions\task\finalise_progressive_pedagogical_access_task',
    'blocking' => 0,
    'minute' => '*/5',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
];

// A Moodle completion flag changes only once. Re-attempts are reconciled from
// persisted completed H5P attempts and genuine submission events.
$tasks[] = [
    'classname' => '\\local_subscriptions\\task\\reconcile_promotion_xp_replays_task',
    'blocking' => 0,
    'minute' => '*/5',
    'hour' => '*',
    'day' => '*',
    'dayofweek' => '*',
    'month' => '*',
];

// Independent passive observer. Does NOT perform XP repairs.
$tasks[] = [
    'classname' => '\local_subscriptions\task\audit_pedagogical_xp_task',
    'blocking' => 0,
    'minute' => '*/5', 'hour' => '*', 'day' => '*', 'dayofweek' => '*', 'month' => '*',
];

// Operator-controlled worker: off unless explicitly enabled in CRM.
// Its schedule stays registered; execute() becomes a no-op while disabled.
$tasks[] = [
    'classname' => '\local_subscriptions\task\automatic_pedagogical_xp_repair_task',
    'blocking' => 0,
    'minute' => '*/5', 'hour' => '*', 'day' => '*', 'month' => '*', 'dayofweek' => '*',
];


// V4: all-module, evidence-only completion audit. Never grants XP.
$tasks[] = [
    'classname' => '\local_subscriptions\task\audit_all_module_xp_task',
    'blocking' => 0,
    'minute' => '*/5', 'hour' => '*', 'day' => '*', 'dayofweek' => '*', 'month' => '*',
];
