<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$task = new \local_subscriptions\task\download_crm_inbox_attachments_task();

$task->execute();
