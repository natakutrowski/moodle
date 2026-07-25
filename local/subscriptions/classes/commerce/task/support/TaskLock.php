<?php
namespace local_subscriptions\commerce\task\support;
defined('MOODLE_INTERNAL') || die();
final class TaskLock {
    public static function acquire(string $name): ?\core\lock\lock {
        return \core\lock\lock_config::get_lock_factory('local_subscriptions')->get_lock('commerce.' . $name, 0);
    }
}
