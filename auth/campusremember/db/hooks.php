<?php
// CampusFR Remember: early login recovery on protected GET pages.
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\after_config::class,
        'callback' => [\auth_campusremember\hook_callbacks::class, 'after_config'],
        'priority' => 100,
    ],
];
