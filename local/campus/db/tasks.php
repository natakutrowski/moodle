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
