<?php

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_subscriptions';
$plugin->version = 2026081601; // YYYYMMDDXX.
$plugin->requires = 2022041900; // Moodle 4.0 minimum.
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.2.0';
$plugin->cron = 3600; // Every hour, in seconds.
