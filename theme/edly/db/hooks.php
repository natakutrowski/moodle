<?php
defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_footer_html_generation;

$callbacks = [
    [
        'hook' => before_footer_html_generation::class,
        'callback' => [\theme_edly\local\hook_callbacks::class, 'before_footer'],
        'priority' => 500,
    ],
];
