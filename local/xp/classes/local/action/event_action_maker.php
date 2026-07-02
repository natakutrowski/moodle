<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Maker.
 *
 * @package    local_xp
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\action;

use block_xp\local\action\maker_from_event;
use block_xp\local\action\static_action;

/**
 * Maker.
 *
 * @package    local_xp
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_action_maker extends \block_xp\local\action\event_action_maker {

    /**
     * Make actions from event.
     *
     * @param \core\event\base $event The event.
     * @return action[]
     */
    public function make_from_event(\core\event\base $event): iterable {
        $parentactions = parent::make_from_event($event);

        // We cannot trust that the event gives us a context, and we do not want restored ones.
        $context = $event->get_context();
        if (!$context || $event->is_restored()) {
            return $parentactions;
        }

        $actions = [];
        if ($event instanceof \core\event\course_module_completion_updated) {
            $data = $event->get_record_snapshot('course_modules_completion', $event->objectid);
            $state = $data->completionstate;
            if ($state == COMPLETION_COMPLETE || $state == COMPLETION_COMPLETE_PASS) {
                $actions[] = new static_action('cm_completed', $context, $event->relateduserid, $context->instanceid);
            }

        } else if ($event instanceof \core\event\course_completed) {
            $actions[] = new static_action('course_completed', $context, $event->relateduserid, $event->courseid);
        } else if ($event instanceof \mod_customcert\event\issue_created) {
            $actions[] = new certificate_issued($context, (int) $event->relateduserid, (int) $event->objectid);
        }

        return $this->combine_actions($parentactions, $actions);
    }

    /**
     * Combine actions.
     *
     * @param iterable $parentactions The parent actions.
     * @param iterable $actions The local actions.
     */
    protected function combine_actions($parentactions, $actions) {

        // Create a list of local types.
        $localtypes = [];
        foreach ($actions as $action) {
            $localtypes[$action->get_type()] = true;
        }

        // Remove parent actions that share a local type.
        $finalparentactions = [];
        foreach ($parentactions as $parentaction) {
            if (array_key_exists($parentaction->get_type(), $localtypes)) {
                continue;
            }
            $finalparentactions[] = $parentaction;
        }

        return array_merge($finalparentactions, $actions);
    }

}
