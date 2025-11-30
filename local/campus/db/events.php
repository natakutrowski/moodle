<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\user_loggedin',
        'callback'    => '\local_campus\observers::user_loggedin',
        'includefile' => '/local/campus/classes/observers.php',
        'internal'    => false,
        'priority'    => 999,
    ],
    [
        'eventname'   => '\core\event\user_login_failed',
        'callback'    => '\local_campus\observers::user_login_failed',
        'includefile' => '/local/campus/classes/observers.php',
        'internal'    => false,
        'priority'    => 9999,
    ],
];
