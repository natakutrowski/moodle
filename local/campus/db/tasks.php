<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
  [
    'classname' => '\local_campus\task\trial_maint_task',
    'blocking'  => 0,
    'minute'    => 'R',
    'hour'      => '*/6',
    'day'       => '*',
    'month'     => '*',
    'dayofweek' => '*'
  ],
];


$tasks[] = 
    [
        'classname' => '\local_campus\task\cleanup_notifications_task',
        'blocking'  => 0,
        // Lundi à 3h00 du matin (heure du serveur).
        'minute'    => '0',
        'hour'      => '3',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '1', // 0 = dimanche, 1 = lundi, etc.
    ]
;