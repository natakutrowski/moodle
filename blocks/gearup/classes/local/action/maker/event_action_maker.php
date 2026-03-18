<?php
// This file is part of Level Up Quest.
//
// Level Up Quest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up Quest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up Quest.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

/**
 * Event action maker.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\action\maker;

use block_gearup\local\action\activity_accessed;
use block_gearup\local\action\activity_completed;
use block_gearup\local\action\course_accessed;
use block_gearup\local\action\course_completed;
use block_gearup\local\action\dashboard_viewed;
use block_gearup\local\action\discussion_created;
use block_gearup\local\action\discussion_replied_to;
use block_gearup\local\action\frontpage_viewed;
use block_gearup\local\action\grade_given;
use block_gearup\local\action\grade_received;
use block_gearup\local\action\level_attained;
use block_gearup\local\action\logged_in;
use block_gearup\local\action\message_read;
use block_gearup\local\action\message_received;
use block_gearup\local\action\message_sent;
use block_gearup\local\action\motrain_coins_earned;
use block_gearup\local\action\profile_updated;
use block_gearup\local\action\quiz_attempted;
use block_gearup\local\action\stash_item_acquired;
use block_gearup\local\action\user_modified;
use core\event\base;
use core_user;

/**
 * Event action maker.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_action_maker implements maker_from_event {

    public function make(): array {
        return [];
    }

    public function make_from_event(base $event): array {
        $context = $event->get_context();

        // We cannot trust that get_context will give us a context.
        if (!$context) {
            return [];
        }

        if ($event instanceof \core\event\course_module_completion_updated) {
            $data = $event->get_record_snapshot('course_modules_completion', $event->objectid);
            $state = $data->completionstate;
            if ($state == COMPLETION_COMPLETE || $state == COMPLETION_COMPLETE_PASS) {
                return [new activity_completed($event->relateduserid, $context)];
            }

        } else if ($event instanceof \core\event\course_completed) {
            return [new course_completed($event->relateduserid, $context)];

        } else if ($event instanceof \core\event\course_module_viewed) {
            return [
                new activity_accessed($event->userid, $context),
                new course_accessed($event->userid, $context->get_course_context()),
            ];

        } else if ($event instanceof \core\event\course_viewed) {
            if ($event->courseid == SITEID) {
                // There is almost no point registering the front page as a course viewed, and it creates
                // issues with the context being that of a course, so we use a dedicated action.
                return [new frontpage_viewed($event->userid)];
            }
            return [new course_accessed($event->userid, $context)];

        } else if ($event instanceof \core\event\dashboard_viewed) {
            return [new dashboard_viewed($event->userid)];

        } else if ($event instanceof \core\event\user_loggedin) {
            return [new logged_in($event->userid)];

        } else if ($event instanceof \core\event\message_sent) {
            if (core_user::is_real_user($event->userid) && core_user::is_real_user($event->relateduserid)) {
                return [
                    new message_sent($event->userid, $event->objectid, $event->relateduserid),
                    new message_received($event->relateduserid, $event->objectid, $event->userid),
                ];
            }

        } else if ($event instanceof \core\event\message_viewed) {
            return [new message_read($event->userid, $event->other['messageid'], $event->relateduserid)];

        } else if ($event instanceof \core\event\user_updated) {
            if ($event->userid != $event->relateduserid) {
                return [new user_modified($event->relateduserid)];
            }
            return [new profile_updated($event->relateduserid)];

        } else if ($event instanceof \core\event\user_graded) {
            if (empty($event->other['itemid'])) {
                return [];
            }

            $gradeobject = $event->get_grade(); // Do not trust that this is set!
            $gradereceived = new grade_received($event->relateduserid, $context, $event->objectid);
            $gradereceived->set_grade_object($gradeobject);
            $actions = [$gradereceived];

            if (core_user::is_real_user($event->userid)) {
                $gradegiven = new grade_given($event->userid, $context, $event->objectid, $event->relateduserid);
                $gradegiven->set_grade_object($gradeobject);
                $actions[] = $gradegiven;
            }

            return $actions;

        } else if ($event instanceof \mod_forum\event\discussion_created
                || $event instanceof \mod_forumng\event\discussion_created
                || $event instanceof \mod_hsuforum\event\discussion_created
                || $event instanceof \mod_moodleoverflow\event\discussion_created
        ) {
            return [
                new discussion_created($event->userid, $context, $event->objectid),
            ];

        } else if ($event instanceof \mod_forum\event\post_created
                || $event instanceof \mod_forumng\event\post_created
                || $event instanceof \mod_hsuforum\event\post_created
                || $event instanceof \mod_moodleoverflow\event\post_created
        ) {

            $isng = $event instanceof \mod_forumng\event\post_created;
            $discussionid = $isng ? $event->other['discussid'] : $event->other['discussionid'];

            return [
                new discussion_replied_to($event->userid, $context, $event->objectid, $discussionid),
            ];

        } else if ($event instanceof \mod_quiz\event\attempt_submitted) {
            $action = new quiz_attempted($event->relateduserid, $context, $event->objectid);
            $attempt = $event->get_record_snapshot('quiz_attempts', $event->objectid);
            $action->set_quiz_attempt_record($attempt);
            if ($attempt) {
                $action->set_quiz_record($event->get_record_snapshot('quiz', $attempt->quiz));
            }
            return [$action];

        } else if ($event instanceof \block_xp\event\user_leveledup) {
            return [
                new level_attained($event->relateduserid, $context, $event->other['level']),
            ];

        } else if ($event instanceof \local_mootivated\event\coins_earned
                || $event instanceof \block_motrain\event\coins_earned
        ) {
            return [
                new motrain_coins_earned($event->relateduserid, $event->other['amount']),
            ];

        } else if ($event instanceof \block_stash\event\item_acquired) {
            return [
                new stash_item_acquired($event->relateduserid, $event->objectid, $event->other['quantity'] ?? 1, $context),
            ];
        }

        return [];
    }

}
