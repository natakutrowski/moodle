<?php

namespace local_subscriptions\commerce\task\base;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\support\CommerceTaskRunner;

/**
 * Minimal scheduled-task adapter shared by the Commerce domain.
 */
abstract class AbstractCommerceTask extends \core\task\scheduled_task {

    final public function execute(): void {
        (new CommerceTaskRunner())->execute($this->create_job());
    }

    abstract protected function create_job(): CommerceTaskJob;
}
