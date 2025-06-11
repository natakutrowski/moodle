<?php
// Ce script est appelé automatiquement par le cron Moodle.
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\subscription_manager;

subscription_manager::expire_subscription_if_needed();
