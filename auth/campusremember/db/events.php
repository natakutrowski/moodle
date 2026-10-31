<?php
// GPL v3 or later.
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\\core\\event\\user_loggedin',
        'callback' => '\\auth_campusremember\\manager::on_login',
    ],
    [
        'eventname' => '\\core\\event\\user_password_updated',
        'callback' => '\\auth_campusremember\\manager::on_password_change',
    ],
    [
        'eventname' => '\\core\\event\\user_deleted',
        'callback' => '\\auth_campusremember\\manager::on_user_deleted',
    ],
];
