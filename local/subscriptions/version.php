<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_subscriptions';
$plugin->version   = 2025100101; // YYYYMMDDXX
$plugin->requires  = 2022041900; // Moodle 4.0 minimum
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = '0.9';
$plugin->cron = 3600; // tous les jours (en secondes)

