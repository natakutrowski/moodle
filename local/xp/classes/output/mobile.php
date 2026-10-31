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

namespace local_xp\output;

use DateTime;
use block_xp\di;
use block_xp\local\config\course_world_config;
use block_xp\local\course_world;
use block_xp\local\utils\external_utils;
use block_xp\local\world;
use context_course;
use context_system;
use DateTimeImmutable;
use local_xp\external\external_api;
use local_xp\external\get_course_group_info;
use local_xp\external\get_course_world_group_ladder;
use local_xp\external\get_course_world_ladder;
use local_xp\external\get_levels_info;
use local_xp\external\get_recent_activity;
use local_xp\external\get_setup;
use local_xp\external\get_user_state;
use local_xp\local\config\default_course_world_config;

/**
 * Mobile renderer class.
 *
 * @package    local_xp
 * @copyright  2018 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
    /**
     * Init the course options.
     *
     * @return array
     */
    public static function init_course_options() {
        global $USER;

        di::get('addon')->require_activated();
        static::log_init();

        $config = di::get('config');
        if ($config->get('context') != CONTEXT_COURSE) {
            throw new \moodle_exception('Not accessible per course.');
        } else if (isguestuser() || !$USER->id) {
            throw new \moodle_exception('User not allowed.');
        }

        $worldfactory = di::get('context_world_factory');
        $bifinder = di::get('course_world_block_instance_checker');
        $courseids = di::get('db')->get_fieldset_select(
            'block_xp_config',
            'courseid',
            'courseid > 0 AND courseid != :siteid AND enabled = 1',
            ['siteid' => SITEID]
        );
        return [
            'restrict' => [
                'courses' => array_filter($courseids, function ($courseid) use ($bifinder, $worldfactory) {
                    $context = context_course::instance($courseid, IGNORE_MISSING);
                    if (!$context) {
                        return false;
                    }
                    $world = $worldfactory->get_world_from_context($context);
                    return $world->get_access_permissions()->can_access()
                        && $bifinder->has_instance_in_context('xp', $world->get_context());
                }),
            ],
        ];
    }

    /**
     * Init the main menu.
     *
     * @return void
     */
    public static function init_mainmenu() {
        global $USER;

        di::get('addon')->require_activated();
        static::log_init();

        $config = di::get('config');
        if ($config->get('context') != CONTEXT_SYSTEM && !$config->get('enableglobalworld')) {
            throw new \moodle_exception('Not accessible sitewide.');
        } else if (isguestuser() || !$USER->id) {
            throw new \moodle_exception('User not allowed.');
        }

        $worldfactory = di::get('context_world_factory');
        $world = $worldfactory->get_world_from_context(context_system::instance());
        if (!$world->get_access_permissions()->can_access()) {
            throw new \moodle_exception('User cannot view the block.');
        }
    }

    /**
     * Enhance the data to pass to a page template.
     *
     * @param world $world The world.
     * @param array $data The data.
     * @param int $page The page number.
     * @return array
     */
    protected static function enhance_page_data(world $world, $data, $page) {
        return array_merge($data, [
            'contextid' => $world->get_context()->id,
            'courseid' => $world instanceof course_world ? $world->get_courseid() : SITEID,
            'css' => static::get_css_data(),
            'currency' => external_api::serialize_currency(di::get('currency_factory')->get_currency_for_world($world)),
            'tabs' => static::get_tabs_data($world, $page),
        ]);
    }

    /**
     * Get the CSS data for template.
     *
     * @return array
     */
    protected static function get_css_data() {
        $renderer = di::get('renderer');
        return [
            'badges' => array_reduce(range(1, 10), function ($carry, $i) use ($renderer) {
                $carry["level$i"] = $renderer->pix_url($i, 'block_xp')->out(false);
                return $carry;
            }, []),
        ];
    }

    /**
     * Get the group selector data for template.
     *
     * @param world $world The world.
     * @param int $currentgroupid The current group ID (-1 for undefined).
     * @param int $userid The user ID.
     * @return array
     */
    protected static function get_group_selector_data(world $world, $currentgroupid, $userid) {
        $data = [
            'enabled' => false,
            'currentgroupid' => max(0, $currentgroupid),
        ];

        // No group selection when we're not in default mode.
        if ((int) $world->get_config()->get('ladderiso') !== default_course_world_config::LEADERBOARD_ISO_DEFAULT) {
            return $data;
        }

        $courseid = $world instanceof course_world ? $world->get_courseid() : SITEID;
        $modinfo = get_fast_modinfo($courseid, $userid);
        if (groups_get_course_groupmode($modinfo->get_course()) != NOGROUPS) {
            $groupinfo = get_course_group_info::execute($courseid);
            $data = array_merge(
                $data,
                $groupinfo,
                [
                    'mygroups' => array_values(array_filter($groupinfo['groups'], function ($group) {
                        return $group['ismember'];
                    })),
                    'othergroups' => array_values(array_filter($groupinfo['groups'], function ($group) {
                        return !$group['ismember'];
                    })),
                ]
            );
            $canseeallpart = $data['canseeallparticipants'];
            $data['firstseparator'] = !empty($data['mygroups']);
            $data['secondseparator'] = ($canseeallpart || !empty($data['mygroups'])) && !empty($data['othergroups']);
            $data['canswitch'] = count($data['groups']) > 1 || (count($data['groups']) == 1 && $canseeallpart);
            $data['usesvisiblegroups'] = $data['groupmode'] == VISIBLEGROUPS;
            $data['usesseparategroups'] = $data['groupmode'] == SEPARATEGROUPS;
            $data['enabled'] = true;

            // Select the first group that they can access, or fallback on "All participants". That means that
            // in separare groups, a user without AAG that does not belong to any group will pick "All participants"
            // and should receive an error when we validate the group access.
            if ($currentgroupid < 0 && !$data['canaccessallgroups']) {
                $data['currentgroupid'] = !empty($data['mygroups']) ? $data['mygroups'][0]['id'] : 0;
            }

            // Set the group name.
            $data['groupname'] = $data['currentgroupid'] ? array_reduce($data['groups'], function ($carry, $group) use ($data) {
                if ($data['currentgroupid'] == $group['id']) {
                    $carry = $group['name'];
                }
                return $carry;
            }, '?') : get_string('allparticipants', 'core');
        }

        return $data;
    }

    /**
     * Get the tabs data for template.
     *
     * @param world $world The world.
     * @param string $currenttab An identifier of the current tab.
     * @return array
     */
    protected static function get_tabs_data(world $world, $currenttab) {
        $hasinfo = $world->get_config()->get('enableinfos');
        $hasladder = $world->get_config()->get('enableladder');
        $hasgroupladder = $world->get_config()->get('enablegroupladder') != default_course_world_config::GROUP_LADDER_NONE;
        return [
            'enabled' => $hasinfo || $hasladder,
            'hasinfo' => $hasinfo,
            'hasladder' => $hasladder,
            'hasgroupladder' => $hasgroupladder,
            'infoactive' => $currenttab == 'info',
            'ladderactive' => $currenttab == 'ladder',
            'groupladderactive' => $currenttab == 'groupladder',
            'stateactive' => $currenttab == 'state',
        ];
    }

    /**
     * Get world from args.
     *
     * @param array $args
     * @return world
     */
    protected static function get_world_from_args(array $args) {
        $context = isset($args['courseid']) ? context_course::instance($args['courseid']) : context_system::instance();
        return di::get('context_world_factory')->get_world_from_context($context);
    }

    /**
     * Group ladder page.
     *
     * @param array $args The arguments.
     * @return array
     */
    public static function group_ladder_page(array $args) {
        di::get('addon')->require_activated();
        static::log_page_view();

        $page = isset($args['page']) ? $args['page'] : 0;
        $perpage = 50;

        $world = static::get_world_from_args($args);
        $renderer = di::get('renderer');

        // Ladder.
        $ladder = get_course_world_group_ladder::execute(null, $page, $perpage, $world->get_context()->id);
        $ladder['prevpage'] = $ladder['page'] - 1;
        $ladder['nextpage'] = $ladder['page'] + 1;
        $ladder['hasbefore'] = $ladder['page'] > 1;
        $ladder['hasmore'] = $ladder['page'] * $perpage < $ladder['total'];
        $ladder['ranking'] = array_map(function ($entry) use ($renderer) {
            $state = $entry['state'];
            return array_merge($entry, [
                'rankpositive' => $entry['rank'] > 0,
                'state' => $state,

                // Custom.
                'xptogo' => $state['totalxpinlevel'] - $state['xpinlevel'],
            ]);
        }, $ladder['ranking']);
        $ladder['showpoints'] = in_array('xp', $ladder['columns']);
        $ladder['showprogress'] = in_array('progress', $ladder['columns']);

        // Permissions are handled in the external functions.
        $infodata = static::enhance_page_data($world, [
            'ladder' => $ladder,
        ], 'groupladder');

        return [
            'templates' => [
                [
                    'id' => 'page',
                    'html' => $renderer->render_from_template('local_xp/mobile-group-ladder', $infodata),
                ],
            ],
            'javascript' => null,
            'otherdata' => null,
            'files' => [],
        ];
    }

    /**
     * Render the info page.
     *
     * @param array $args The arguments.
     * @return array
     */
    public static function info_page(array $args) {
        di::get('addon')->require_activated();
        static::log_page_view();

        $renderer = di::get('renderer');
        $world = static::get_world_from_args($args);
        $config = $world->get_config();

        $instructions = $config->get('instructions');
        $instructionsformat = $config->get('instructions_format');
        $cleanedinstructions = trim(strip_tags($instructions));
        $hasinstructions = !empty($cleanedinstructions);
        $instructionsformatted = null;
        if ($hasinstructions) {
            $ctx = $world->get_context();
            [$instructionsformatted, $unused] = external_utils::format_text($instructions, $instructionsformat, $ctx);
        }

        // Levels info.
        $levelsinfo = get_levels_info::execute(null, $world->get_context()->id);
        $alwaysshowname = array_reduce($levelsinfo['levels'], function ($carry, $level) {
            return $carry || !empty($level['name']);
        }, false);
        if ($alwaysshowname) {
            $levelsinfo['levels'] = array_map(function ($level) {
                if (empty($level['name'])) {
                    $level['name'] = get_string('levelx', 'block_xp', $level['level']);
                }
                return $level;
            }, $levelsinfo['levels']);
        }

        // Permissions are handled in the external function.
        $infodata = static::enhance_page_data($world, [
            'levelsinfo' => $levelsinfo,
            'instructions' => $instructionsformatted,
        ], 'info');

        return [
            'templates' => [[
                'id' => 'page',
                'html' => $renderer->render_from_template('local_xp/mobile-info', $infodata),
            ], ],
            'javascript' => null,
            'otherdata' => null,
            'files' => [],
        ];
    }

    /**
     * The ladder page.
     *
     * @param array $args The arguments.
     * @return array
     */
    public static function ladder_page(array $args) {
        global $USER;

        di::get('addon')->require_activated();
        static::log_page_view();

        $groupid = isset($args['groupid']) ? $args['groupid'] : -1;
        $page = isset($args['page']) ? $args['page'] : 0;
        $perpage = 50;
        $userid = $USER->id;

        $renderer = di::get('renderer');
        $world = static::get_world_from_args($args);
        $perms = $world->get_access_permissions();
        $optinorout = ((int) $world->get_config()->get('ladderparticipation'))
            !== default_course_world_config::LEADERBOARD_PARTICIPATION_FORCED;

        // Check participation.
        $state = di::get(\local_xp\local\leaderboard\participation\service_factory::class)
            ->get_for_context($world->get_context())
            ->get_state($USER->id);
        if ($optinorout && !$state->is_participating() && !$perms->can_manage()) {
            $canjoin = !$state->is_state_locked();
            $lockeduntil = $state->get_state_locked_until();
            $showjoin = $canjoin || ($lockeduntil && $lockeduntil < (new DateTimeImmutable("+2 weeks")));
            $dateformat = get_string('strftimedayshort', 'core_langconfig');
            $infodata = static::enhance_page_data($world, [
                'showjoin' => $showjoin,
                'canjoin' => $canjoin,
                'joindateformatted' => $lockeduntil ? userdate($lockeduntil->getTimestamp(), $dateformat) : '',
            ], 'ladder');
            return [
                'templates' => [[
                    'id' => 'page',
                    'html' => $renderer->render_from_template('local_xp/mobile-ladder-not-participating', $infodata),
                ], ],
                'javascript' => null,
                'otherdata' => [],
                'files' => [],
            ];
        }

        // Group stuff.
        $groupselector = static::get_group_selector_data($world, $groupid, $userid);
        $groupid = $groupselector['currentgroupid'];

        // Ladder.
        $ladder = get_course_world_ladder::execute(null, $groupid, $page, $perpage, $world->get_context()->id);
        $ladder['prevpage'] = $ladder['page'] - 1;
        $ladder['nextpage'] = $ladder['page'] + 1;
        $ladder['hasbefore'] = $ladder['page'] > 1;
        $ladder['hasmore'] = !$ladder['neighbours'] && ($ladder['page'] * $perpage < $ladder['total']);
        $ladder['showrank'] = $ladder['rankmode'] == course_world_config::RANK_ON;
        $ladder['showrelrank'] = $ladder['rankmode'] == course_world_config::RANK_REL;
        $ladder['showtotal'] = in_array('xp', $ladder['columns']);
        $ladder['ranking'] = array_map(function ($entry) {
            return array_merge($entry, [
                'rankpositive' => $entry['rank'] > 0,
            ]);
        }, $ladder['ranking']);

        $leavedata = [];
        if ($optinorout) {
            $canleave = !$state->is_state_locked();
            $lockeduntil = $state->get_state_locked_until();
            $showleave = $canleave || !$state->is_state_locked_until(new \DateTimeImmutable('+2 weeks'));
            $dateformat = get_string('strftimedayshort', 'core_langconfig');
            $leavedata = array_merge($leavedata, [
                'showleave' => $showleave,
                'canleave' => $canleave,
                'leavedateformatted' => $lockeduntil ? userdate($lockeduntil->getTimestamp(), $dateformat) : '',
            ]);
        }

        // Permissions are handled in the external functions.
        $infodata = static::enhance_page_data($world, [
            'ladder' => $ladder,
            'groupselector' => $groupselector,
            'leavedata' => $leavedata,
        ], 'ladder');

        return [
            'templates' => [[
                'id' => 'page',
                'html' => $renderer->render_from_template('local_xp/mobile-ladder', $infodata),
            ], ],
            'javascript' => null,
            'otherdata' => [
                'groupid' => $groupid,
            ],
            'files' => [],
        ];
    }

    /**
     * The main entry page.
     *
     * @param array $args The arguments.
     * @return array
     */
    public static function main_page(array $args) {
        return static::state_page($args);
    }

    /**
     * Render the state page.
     *
     * @param array $args The arguments.
     * @return array
     */
    public static function state_page(array $args) {
        global $USER;

        di::get('addon')->require_activated();
        static::log_page_view();

        $userid = $USER->id;
        $world = static::get_world_from_args($args);
        $renderer = di::get('renderer');

        // Permissions are handled in the external function.
        $setup = get_setup::execute(null, $world->get_context()->id);
        $state = get_user_state::execute(null, $userid, $world->get_context()->id);
        $activity = get_recent_activity::execute(null, $world->get_context()->id);
        $infodata = static::enhance_page_data($world, [
            'activity' => array_map(function ($activity) use ($renderer) {
                return array_merge($activity, [
                    'timeago' => $renderer->tiny_time_ago(new DateTime("@{$activity['date']}")),
                ]);
            }, $activity),
            'showactivity' => !empty($setup['block']['recentactivity']),
            'setup' => $setup,
            'state' => $state,

            // Custom.
            'xptogo' => $state['totalxpinlevel'] - $state['xpinlevel'],
        ], 'state');

        return [
            'templates' => [[
                'id' => 'page',
                'html' => $renderer->render_from_template('local_xp/mobile-state', $infodata),
            ], ],
            'javascript' => null,
            'otherdata' => null,
            'files' => [],
        ];
    }

    /**
     * Log an init hit.
     */
    protected static function log_init() {
        $config = di::get('config');
        $config->set('mobilelastinit', time());
    }

    /**
     * Log a page view.
     */
    protected static function log_page_view() {
        $config = di::get('config');
        $config->set('mobilelastview', time());
    }
}
