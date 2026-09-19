<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\access\CommerceProgressiveAccessFinalizer;

/** Persist lifetime access after pedagogical calendars become complete. */
final class finalise_progressive_pedagogical_access_task extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_finalise_progressive_pedagogical_access', 'local_subscriptions');
    }

    public function execute(): void {
        $count = CommerceProgressiveAccessFinalizer::create()->finalise(time());
        mtrace('CampusFR pedagogical access finalised: ' . $count);
    }
}
