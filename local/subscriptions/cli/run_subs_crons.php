<?php
// local/subscriptions/cli/run_subs_crons.php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

mtrace("▶︎ Running: expire_user_enrolments_task …");
$task1 = new \local_subscriptions\task\expire_user_enrolments_task();
$task1->execute();

mtrace("▶︎ Running: send_expiry_reminders_task …");
$task2 = new \local_subscriptions\task\send_expiry_reminders_task();
$task2->execute();

mtrace("✔︎ Done.");
