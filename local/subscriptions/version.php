<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_subscriptions';
$plugin->version   = 2026071707; // YYYYMMDDXX
$plugin->requires  = 2022041900; // Moodle 4.0 minimum
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.1.0';
$plugin->cron = 3600; // tous les jours (en secondes)

