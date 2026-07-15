<?php

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_created',
        'callback' =>
            '\local_subscriptions\crm\inbox\observer\InboxUserObserver::user_created',
    ],
    [
        'eventname' => '\core\event\user_updated',
        'callback' =>
            '\local_subscriptions\crm\inbox\observer\InboxUserObserver::user_updated',
    ],
];