<?php

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'availability_campusfr';
$plugin->version = 2026091000;
$plugin->requires = 2025041400;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';
$plugin->dependencies = [
    'local_subscriptions' => 2026091007,
];
