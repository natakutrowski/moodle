<?php

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_subscriptions';
$plugin->version = 2026072605; // YYYYMMDDXX.
$plugin->requires = 2022041900; // Moodle 4.0 minimum.
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.1.0-7.94g-shadow-runtime';
$plugin->cron = 3600; // Every hour, in seconds.
