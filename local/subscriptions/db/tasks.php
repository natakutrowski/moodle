<?php
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
