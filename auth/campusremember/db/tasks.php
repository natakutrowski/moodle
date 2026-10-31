<?php
// GPL v3 or later.
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\\auth_campusremember\\task\\purge_expired_tokens',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '3',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
