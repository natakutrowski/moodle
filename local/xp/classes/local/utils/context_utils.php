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
// https://levelup.plus

namespace local_xp\local\utils;

use context;
use cm_info;
use moodle_exception;
use moodle_url;

/**
 * Utils.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_utils {

    /**
     * Get the activity name.
     *
     * @param context|int|null|false $contextorid
     * @return string|null
     */
    public static function get_activity_name($contextorid): ?string {
        $cm = self::get_module_cm($contextorid);
        if (!$cm) {
            return null;
        }
        return format_string($cm->name, true, ['context' => $cm->context ?: null]);
    }

    /**
     * Get the activity name prefixed with the module type name.
     *
     * @param context|int|null|false $contextorid
     * @return string|null
     */
    public static function get_activity_name_prefixed($contextorid): ?string {
        $cm = self::get_module_cm($contextorid);
        if (!$cm) {
            return null;
        }
        $formatted = format_string($cm->name, true, ['context' => $cm->context ?: null]);
        return get_string('colon', 'block_xp', [
            'a' => get_string('modulename', $cm->modname),
            'b' => $formatted,
        ]);
    }

    /**
     * Get the course name short.
     *
     * @param context|int|null|false $contextorid
     * @return string|null
     */
    public static function get_course_name_short($contextorid): ?string {
        $context = self::resolve_context($contextorid);
        if (!$context || $context->contextlevel != CONTEXT_COURSE) {
            return null;
        }
        try {
            $modinfo = get_fast_modinfo($context->instanceid);
            return format_string($modinfo->get_course()->shortname, true, ['context' => $context ?: null]);
        } catch (moodle_exception $e) {
            return null;
        }
    }

    /**
     * Get context URL.
     *
     * @param context|int|null|false $contextorid
     * @return moodle_url|null
     */
    public static function get_url($contextorid) {
        $context = self::resolve_context($contextorid);
        if (!$context) {
            return null;
        }
        if ($context->contextlevel == CONTEXT_MODULE) {
            $cm = self::get_module_cm($context);
            return $cm ? $cm->get_url() : null;
        }
        return $context->get_url();
    }

    /**
     * Return the course-module info for a module context (or null).
     *
     * @param context|int|null|false $contextorid
     * @return cm_info|null
     */
    protected static function get_module_cm($contextorid) {
        $context = self::resolve_context($contextorid);
        if (!$context || $context->contextlevel != CONTEXT_MODULE) {
            return null;
        }
        $cmid = (int) $context->instanceid;
        $coursecontext = $context->get_course_context(false);
        if (!$coursecontext) {
            return null;
        }
        try {
            $modinfo = get_fast_modinfo($coursecontext->instanceid);
            return $modinfo->get_cm($cmid);
        } catch (moodle_exception $e) {
            return null;
        }
    }

    /**
     * Resolve the context.
     *
     * @param context|int|null|false $contextorid
     * @return context|null
     */
    protected static function resolve_context($contextorid) {
        if ($contextorid instanceof context) {
            return $contextorid;
        } else if (!$contextorid) {
            return null;
        }
        return context::instance_by_id($contextorid, IGNORE_MISSING) ?: null;
    }
}
