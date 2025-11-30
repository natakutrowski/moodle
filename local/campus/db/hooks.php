<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        // On remplace l’ancien local_campus_before_http_headers() par ce hook.
        'hook'     => \core\hook\output\before_http_headers::class,
        'callback' => [\local_campus\hooks\output\callbacks::class, 'before_http_headers'],
        'priority' => 500, // optionnel
    ],
];
