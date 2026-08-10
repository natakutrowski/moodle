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

namespace block_gearup\local\utils;

use context;
use context_course;
use core_text;
use core_user\fields;

/**
 * User utils.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_utils {

    /**
     * Can select group in a group selector.
     *
     * @param int|object|context $courseish A context, or the course, or its ID.
     * @param int $userid The user ID.
     * @param int $groupid The group ID.
     * @return bool
     */
    public static function can_select_group($courseish, $userid, $groupid): bool {
        if (!$courseish) {
            return false;
        } else if (!course_utils::uses_group_mode($courseish)) {
            return false;
        }

        $course = course_utils::get_course($courseish);
        if (!$course) {
            return false;
        }

        $context = context_course::instance($course->id);
        $groupmode = $course->groupmode;
        $aag = has_capability('moodle/site:accessallgroups', $context, $userid);

        if ($groupmode == VISIBLEGROUPS || $aag) {
            $allowedgroups = groups_get_all_groups($course->id, 0, $course->defaultgroupingid);
        } else {
            $allowedgroups = groups_get_all_groups($course->id, $userid, $course->defaultgroupingid);
        }

        return array_key_exists($groupid, $allowedgroups);
    }

    /**
     * Whether the user can view all participants.
     *
     * This is more or less the same as the permission to access all groups, except
     * that it handles the case where the user is allowed to select "all participants"
     * in a group selector because they do not belong to any group.
     *
     * It's not meant as a blanket statement to say that the user can access all groups,
     * but when this returns true we can assume that there are no restrictions to which
     * users can be seen in the course. As such, we know that we can trust to disclose
     * all users without needing to implement additional checks.
     *
     * This does not check whether the user has visibility over any particular group,
     * or wether they are enrolled, etc. It only broadly checks that there are no group
     * separation in place that we need to consider.
     *
     * Note that the participants page itself seems to be blocked when using separate
     * groups where the user is not a member of any group. However, the user profiles
     * would still be accessible, and users could still be visible in other modules.
     *
     * Returns true when checks cannot be made (not in course, no groups, etc.).
     *
     * @param int|object|context $courseish A context, or the course, or its ID.
     * @param int $userid The user ID.
     * @return bool
     */
    public static function can_view_all_participants($courseish, $userid): bool {
        if (!$courseish) {
            return true;
        } else if (!course_utils::uses_group_mode($courseish)) {
            return true;
        }

        $mincourse = course_utils::get_min_course($courseish);
        if (!$mincourse) {
            // We're probably not in a course.
            return true;
        }

        // All groups are always visible in these modes.
        if ($mincourse->groupmode == NOGROUPS || $mincourse->groupmode == VISIBLEGROUPS) {
            return true;
        }

        // Can explicitly view all participants.
        $context = context_course::instance($mincourse->id);
        $aag = has_capability('moodle/site:accessallgroups', $context, $userid);
        if ($aag) {
            return true;
        }

        // We're in separate groups mode, where we can view all participants if we're not a member of any group.
        $course = course_utils::get_course($courseish);
        $allowedgroups = groups_get_all_groups($mincourse->id, $userid, $course->defaultgroupingid);
        return empty($allowedgroups);
    }

    /**
     * Get SQL filter user by term.
     *
     * @param string $term The term.
     * @param array $allowedidentityfields The identity fields that are allowed to be used.
     * @param string $tablealias The user table alias.
     * @return array SQL where fragment and parameters.
     */
    public static function get_filter_user_by_term_sql(string $term, array $allowedidentityfields = [], string $tablealias = 'u') {
        global $DB;

        if (empty($term)) {
            return ['1=1', []];
        }

        static $paramn = 0;
        $makeparam = static function () use (&$paramn) {
            return 'guuserterm' . $paramn++;
        };

        $parts = ['1=0'];
        $params = [];

        $tableprefix = $tablealias ? $tablealias . '.' : '';
        $termlength = core_text::strlen($term);
        $isemail = (bool) preg_match('#^[^\s@]+@[^\s@]+$#', $term);
        $hasspace = (bool) preg_match('/\s/', $term);
        $isdomain = strpos($term, '@') === 0 && !$hasspace && $termlength > 1;
        $ispotentialid = is_number($term);

        // Filter by straight user ID.
        if ($ispotentialid && $term > 1) {
            $paramname = $makeparam();
            $parts[] = "{$tableprefix}id = :$paramname";
            $params[$paramname] = $term;
        }

        // Filter by name.
        if (!$isemail) {
            $nameoptions = [
                ['firstname' => $term],
                ['lastname' => $term],
            ];
            $namecombos = explode(' ', $term);
            if (count($namecombos) > 1) {
                for ($i = 0; $i < count($namecombos) - 1; $i++) {
                    $nameoptions[] = [
                        'firstname' => implode(' ', array_slice($namecombos, 0, $i + 1)),
                        'lastname' => implode(' ', array_slice($namecombos, $i + 1)),
                    ];
                }
            }
            foreach ($nameoptions as $option) {
                $subparams = [];
                $subsql = [];
                if (!empty($option['firstname'])) {
                    $paramname = $makeparam();
                    $subsql[] = $DB->sql_like("{$tableprefix}firstname", ':' . $paramname, false, false);
                    $subparams[$paramname] = $DB->sql_like_escape($option['firstname']) . '%';
                }
                if (!empty($option['lastname'])) {
                    $paramname = $makeparam();
                    $subsql[] = $DB->sql_like("{$tableprefix}lastname", ':' . $paramname, false, false);
                    $subparams[$paramname] = $DB->sql_like_escape($option['lastname']) . '%';
                }
                if (!empty($subsql)) {
                    $parts[] = '(' . implode(' AND ', $subsql) . ')';
                    $params = array_merge($params, $subparams);
                }
            }
        }

        // Filter email.
        if (in_array('email', $allowedidentityfields)) {
            if ($isemail) {
                $paramname = $makeparam();
                $parts[] = "{$tableprefix}email = :$paramname";
                $params[$paramname] = $term;
            }
            if ($isdomain) {
                $paramname = $makeparam();
                $parts[] = $DB->sql_like("{$tableprefix}email", ':' . $paramname, false, false);
                $params[$paramname] = '%' . $DB->sql_like_escape($term) . '%';
            }
            if (!$hasspace && !$isemail && !$isdomain && $termlength > 2) {
                $paramname = $makeparam();
                $parts[] = $DB->sql_like("{$tableprefix}email", ':' . $paramname, false, false);
                $params[$paramname] = $DB->sql_like_escape($term) . '%';
            }
        }

        // Filter ID number.
        if (in_array('idnumber', $allowedidentityfields)) {
            if ($termlength > 2) {
                $paramname = $makeparam();
                $parts[] = $DB->sql_like("{$tableprefix}idnumber", ':' . $paramname, false, false);
                $params[$paramname] = $DB->sql_like_escape($term) . '%';
            } else {
                $paramname = $makeparam();
                $parts[] = "{$tableprefix}idnumber = :" . $paramname;
                $params[$paramname] = $term;
            }
        }

        // Filter username.
        if (in_array('username', $allowedidentityfields) && !$hasspace) {
            if ($termlength > 2) {
                $paramname = $makeparam();
                $parts[] = $DB->sql_like("{$tableprefix}username", ':' . $paramname, false, false);
                $params[$paramname] = $DB->sql_like_escape($term) . '%';
            } else {
                $paramname = $makeparam();
                $parts[] = "{$tableprefix}username = :" . $paramname;
                $params[$paramname] = $term;
            }
        }

        $sql = '(' . implode(') OR (', $parts) . ')';
        return [$sql, $params];
    }

    /**
     * Get group options.
     *
     * @param int|object|context $courseish A context, or the course, or its ID.
     * @param int $userid The user ID.
     * @return array Where keys are group IDs, and values are group names.
     */
    public static function get_group_select_options($courseish, $userid) {
        if (!$courseish) {
            return [];
        } else if (!course_utils::uses_group_mode($courseish)) {
            return [];
        }

        $course = course_utils::get_course($courseish);
        if (!$course) {
            return [];
        }

        $context = context_course::instance($course->id);
        $groupmode = $course->groupmode;
        $aag = has_capability('moodle/site:accessallgroups', $context, $userid);

        $usergroups = [];
        if ($groupmode == VISIBLEGROUPS || $aag) {
            $allowedgroups = groups_get_all_groups($course->id, 0, $course->defaultgroupingid);
            $usergroups = groups_get_all_groups($course->id, $userid, $course->defaultgroupingid);
        } else {
            $allowedgroups = groups_get_all_groups($course->id, $userid, $course->defaultgroupingid);
        }

        $options = [];
        if (!$allowedgroups || $groupmode == VISIBLEGROUPS || $aag) {
            $options[0] = get_string('allparticipants', 'core');
        }
        $options += groups_sort_menu_options($allowedgroups, $usergroups);

        return $options;
    }


    /**
     * Get the user fields.
     *
     * @param int $userid The user ID.
     * @param array $fields The list of fields.
     * @param bool $loaddefaults Whether to load the default values for profile fields.
     * @param bool $isreattempt Whether this is a re-attempt to load data, do not set.
     * @return \stdClass The user, or an empty object.
     */
    public static function get_user_with_fields(
        $userid,
        array $fields,
        bool $loaddefaults = false,
        bool $isreattempt = false
    ): \stdClass {
        global $DB;

        $fallbackvalue = (object) [];
        if (empty($fields)) {
            return $fallbackvalue;
        }

        // Extract the profile fields from the list of fields.
        $profilefields = array_filter($fields, function ($field) {
            return strpos($field, fields::PROFILE_FIELD_PREFIX) === 0;
        });
        $profilefieldshortnames = array_map(function ($field) {
            return substr($field, strlen(fields::PROFILE_FIELD_PREFIX));
        }, $profilefields);

        // Load the data from the database, we cannot use the $USER object as it's not up to date.
        $userfields = fields::empty();
        $userfields->including(...$fields);
        $sqlparts = $userfields->get_sql('u', true, '', '', false);

        $user = $fallbackvalue;
        try {
            $sql = "SELECT u.id, $sqlparts->selects FROM {user} u $sqlparts->joins WHERE u.id = :userid";
            $user = $DB->get_record_sql($sql, array_merge(['userid' => $userid], $sqlparts->params), MUST_EXIST);
        } catch (\moodle_exception $e) {

            // The exception is likely to happen when a database field no longer exists, as the core code does
            // a JOIN on the field table, leading to returning nothing for an invalid custom field.

            if (!$isreattempt) {
                // We only re-attempt once.
                return $fallbackvalue;
            } else if (empty($profilefields)) {
                // If we are not looking for profile fields, we can just return the fallback value.
                return $fallbackvalue;
            }

            // Load the existing profile fields.
            [$insql, $inparams] = $DB->get_in_or_equal($profilefieldshortnames);
            $realprofilefields = $DB->get_records_select('user_info_field', "shortname $insql", $inparams, '', 'id, shortname');
            $realprofilefields = array_map(function ($field) {
                return fields::PROFILE_FIELD_PREFIX . $field->shortname;
            }, $realprofilefields);

            // Remove the missing profile fields.
            $missingfields = array_diff($profilefields, $realprofilefields);
            $fields = array_diff($fields, $missingfields);

            // Defer to the original process.
            return static::get_user_with_fields($userid, $fields, $loaddefaults, true);
        }

        if (empty($user)) {
            return $user;
        }

        // When we have null profile fields, we try to fetch their default values.
        $nullcustomfields = [];
        foreach ($profilefields as $field) {
            if ($user->{$field} === null) {
                $nullcustomfields[] = substr($field, strlen(fields::PROFILE_FIELD_PREFIX));
            }
        }
        if ($loaddefaults && !empty($nullcustomfields)) {
            [$insql, $inparams] = $DB->get_in_or_equal($nullcustomfields, SQL_PARAMS_NAMED);
            $defaultvalues = $DB->get_records_select('user_info_field',
                "shortname $insql AND defaultdata IS NOT NULL",
                $inparams,
                '',
                'shortname, defaultdata'
            );
            foreach ($defaultvalues as $shortname => $defaultvalue) {
                $user->{fields::PROFILE_FIELD_PREFIX . $shortname} = $defaultvalue->defaultdata;
            }
        }

        return $user;
    }


    /**
     * Get the visible identity fields.
     *
     * @param \context $context The context.
     * @return array Where keys are user properties, and values are labels.
     */
    public static function get_visible_identity_fields(context $context) {
        $identityfields = fields::get_identity_fields($context, false);
        return array_intersect_key([
            'username' => get_string('username', 'core'),
            'idnumber' => get_string('idnumber', 'core'),
            'email' => get_string('email', 'core'),
        ], array_flip($identityfields));
    }

}
