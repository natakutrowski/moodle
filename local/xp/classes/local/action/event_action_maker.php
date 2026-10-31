<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\local\action;

use block_xp\di;
use block_xp\local\action\action;
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
        } else if (
            $event instanceof \mod_customcert\event\issue_created
                || $event instanceof \tool_certificate\event\certificate_issued
        ) {
            $actions = array_merge($actions, $this->make_from_certificate_issued($event));
        }

        return $this->combine_actions($parentactions, $actions);
    }

    /**
     * Make actions from a certificate issuance event.
     *
     * @param \core\event\base $event The event.
     * @return certificate_issued[]
     */
    protected function make_from_certificate_issued(\core\event\base $event): array {
        $context = $event->get_context();

        // The Workplace course certificate plugin is issued in the course context by the
        // tool plugin. To recover the module, we use the template ID which is set against
        // the module itself.
        if (
            $context->contextlevel != CONTEXT_MODULE
                && $event instanceof \tool_certificate\event\certificate_issued
        ) {
            try {
                $issue = $event->get_record_snapshot('tool_certificate_issues', $event->objectid);
                if (!$issue || $issue->component !== 'mod_coursecertificate') {
                    return [];
                }
                // Issuance does not identify the activity. Assume each template is used once per course.
                $sql = "SELECT ctx.id
                          FROM {coursecertificate} cc
                          JOIN {course_modules} cm ON cm.instance = cc.id AND cm.course = cc.course
                          JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                          JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                         WHERE cc.course = :courseid AND cc.template = :templateid
                      ORDER BY cm.id";
                $contextid = di::get('db')->get_field_sql($sql, [
                    'modname' => 'coursecertificate',
                    'contextlevel' => CONTEXT_MODULE,
                    'courseid' => $issue->courseid,
                    'templateid' => $issue->templateid,
                ], IGNORE_MULTIPLE);
                if ($contextid) {
                    $context = \context::instance_by_id($contextid, IGNORE_MISSING) ?: $context;
                }
            } catch (\Throwable $e) {
                // Prevent a potential disaster.
                unset($e);
                return [];
            }
        }

        return [new certificate_issued($context, (int) $event->relateduserid, (int) $event->objectid)];
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
