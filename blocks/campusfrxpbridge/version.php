<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2026091600;
$plugin->requires = 2024100700;
$plugin->component = 'block_campusfrxpbridge';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '7.97 M5.2';
$plugin->dependencies = [
    'block_xp' => 2026042000,
    'local_subscriptions' => 2026091501,
];
