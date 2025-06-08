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
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\utils;

use cm_info;
use completion_info;
use course_modinfo;

/**
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_utils {

    /**
     * Get a cm_info.
     *
     * We do not strict type the method as Moodle is renaming their classes quite often now...
     *
     * @param int|object|course_modinfo|context $courseish The course-ish.
     * @param int $userid The user ID.
     * @param int $cmid The CM ID.
     * @return cm_info|null
     */
    public static function get_cm_info($courseish, $userid, $cmid) {
        $modinfo = static::get_modinfo($courseish, $userid);
        if (!$modinfo) {
            return null;
        }
        try {
            return $modinfo->get_cm($cmid);
        } catch (\moodle_exception $e) {
            return null;
        }
    }

    /**
     * Get course completion info.
     *
     * We do not strict type the method as Moodle is renaming their classes quite often now...
     *
     * @param int|object|course_modinfo|context $courseish The course-ish.
     * @return completion_info|null
     */
    public static function get_completion_info($courseish) {
        static::require_completion_libs();
        try {
            $course = static::get_min_course($courseish);
            if (!$course) {
                return null;
            }
            return new completion_info($course);
        } catch (\moodle_exception $e) {
            return null;
        }
    }

    /**
     * Get the min course object from course-ish.
     *
     * This object is cached in modinfo and is only guaranteed to contain:
     * id, shortname, fullname, format, enablecompletion, groupmode, groupmodeforce, cacherev
     *
     * @param int|object|course_modinfo|context $courseish The course-ish.
     * @return \stdClass|null
     */
    public static function get_min_course($courseish) {
        if ($courseish instanceof \context) {
            $coursecontext = $courseish->get_course_context(false);
            if (!$coursecontext) {
                return null;
            }
            $courseish = (int) $coursecontext->instanceid;
        }
        if ($courseish instanceof course_modinfo) {
            return $courseish->get_course();
        } else if (is_object($courseish)) {
            return $courseish;
        }
        $modinfo = static::get_modinfo($courseish);
        return $modinfo ? $modinfo->get_course() : null;
    }

    /**
     * Get the course object from course-ish.
     *
     * If the current $COURSE object matches, it returns it instead.
     *
     * @param int|object|course_modinfo|context $courseish The course-ish.
     * @return \stdClass|null
     */
    public static function get_course($courseish) {
        if ($courseish instanceof \context) {
            $coursecontext = $courseish->get_course_context(false);
            if (!$coursecontext) {
                return null;
            }
            $courseish = (int) $coursecontext->instanceid;
        } else if ($courseish instanceof course_modinfo) {
            $courseish = (int) $courseish->get_course_id();
        } else if (is_object($courseish)) {
            $courseish = (int) $courseish->id;
        }

        try {
            return get_course($courseish);
        } catch (\dml_exception $e) {
            return null;
        }
    }

    /**
     * Get course format.
     *
     * @param int|object|course_modinfo $courseish The course-ish.
     * @return \core_courseformat\base
     */
    public static function get_format($courseish) {
        static::require_course_libs();
        try {
            $course = static::get_min_course($courseish);
            return $course ? course_get_format($course) : null;
        } catch (\moodle_exception $e) {
            return null;
        }
    }

    /**
     * Get course_modinfo.
     *
     * @param int|object|context|course_modinfo $courseish The course-ish.
     * @param int|null $userid The user ID.
     * @return course_modinfo|null
     */
    public static function get_modinfo($courseish, $userid = 0) {
        global $USER;
        static::require_course_libs();

        $courseorid = 0;
        if ($courseish instanceof \context) {
            $coursecontext = $courseish->get_course_context(false);
            if (!$coursecontext) {
                return null;
            }
            $courseorid = (int) $coursecontext->instanceid;
        } else if ($courseish instanceof course_modinfo) {
            $useridmismatch = ($userid && $courseish->userid != $userid) || (!$userid && $courseish->userid != $USER->id);
            if (!$useridmismatch) {
                return $courseish;
            }
            $courseorid = (int) $courseish->id;
        } else {
            $courseorid = $courseish;
        }

        try {
            return get_fast_modinfo($courseorid, $userid);
        } catch (\moodle_exception $e) {
            return null;
        }
    }

    /**
     * Get course_modinfo.
     *
     * @param int|object|course_modinfo|context $courseish The course-ish.
     * @param int $userid The user ID.
     * @return course_modinfo|null
     */
    protected static function get_modinfo_from_courseish($courseish, $userid = 0) {
        global $USER;

        return static::get_modinfo($courseish, $userid);
    }

    /**
     * Get the course module.
     *
     * @param int|object|course_modinfo|context $courseish The course-ish.
     * @return bool
     */
    public static function uses_group_mode($courseish): bool {
        if ($courseish instanceof \context) {
            $coursecontext = $courseish->get_course_context(false);
            if (!$coursecontext) {
                return false;
            }
            $courseish = (int) $coursecontext->instanceid;
        }
        $course = static::get_min_course($courseish);
        return $course ? $course->groupmode != NOGROUPS : false;
    }

    /**
     * Require completion libs.
     *
     * @return void
     */
    protected static function require_completion_libs() {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
    }

    /**
     * Require course libs.
     *
     * @return void
     */
    protected static function require_course_libs() {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
    }

}
