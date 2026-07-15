<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

$task = new \local_subscriptions\task\download_crm_inbox_attachments_task();

$task->execute();