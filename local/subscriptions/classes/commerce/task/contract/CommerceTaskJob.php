<?php
namespace local_subscriptions\commerce\task\contract;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
interface CommerceTaskJob {
    public function run(): TaskExecutionResult;
}
