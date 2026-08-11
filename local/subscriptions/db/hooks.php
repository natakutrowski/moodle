<?php

defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_footer_html_generation;
use core\hook\output\before_standard_head_html_generation;

$callbacks = [
    [
        'hook' => before_standard_head_html_generation::class,
        'callback' => [\local_subscriptions\hook_listener::class, 'add_storefront_seo_head'],
        'priority' => 200,
    ],
    [
        'hook' => before_footer_html_generation::class,
        'callback' => [\local_subscriptions\output\hook_callbacks::class, 'before_footer'],
        'priority' => 450,
    ],
];
