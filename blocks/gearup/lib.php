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

use block_gearup\di;
use block_gearup\local\action\motrain_level_attained;
use block_gearup\local\action\xp_gained;
use block_gearup\local\file\speech_file_server;
use block_gearup\local\mission\mission_instance;
use core_user\output\myprofile\category;
use core_user\output\myprofile\node;

/**
 * Lib.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Before footer hook.
 *
 * Converted to \block_gearup\local\hooks\before_footer_html_generation::callback.
 *
 * To be removed once the minimum required version is Moodle 4.4, in the meantime
 * both the hook and this callback must be maintained.
 */
function block_gearup_before_footer() {
    global $PAGE;

    if (during_initial_install() || !isloggedin() || isguestuser()) {
        return;
    }

    if (!di::get('lm')->is_active()) {
        return;
    }

    echo (di::get('html_injector')->inject($PAGE) ?? '');
}

/**
 * Before HTTP headers hook.
 *
 * To be removed once the minimum required version is Moodle 4.4, in the meantime
 * both the hook and this callback must be maintained.
 */
function block_gearup_before_http_headers() {
    \block_gearup\local\hooks\before_http_headers::keep_drawer_open();
}

/**
 * Extend profile.
 *
 * @param core_user\output\myprofile\tree $tree The tree.
 * @param object $user The user.
 * @param bool $iscurrentuser Whether is the current user.
 * @param object $course The course.
 */
function block_gearup_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $USER;

    if (!di::get('lm')->is_active()) {
        return;
    }

    $mr = di::get('repository');
    $output = di::get('renderer');
    $context = $course ? context_course::instance($course->id) : context_system::instance();
    $context = di::get('context_manager')->normalise_context($context);
    $accessperms = di::get('access_permissions_factory')->get_permissions_for_context($context);

    // Both users must be able to have access.
    if (!$accessperms->can_access($user->id) || !$accessperms->can_access($USER->id)) {
        return;
    }

    // See if there are achievements for this person first.
    $hasany = $mr->has_achievement_instances_in($user->id, $context);
    if (!$hasany) {
        return;
    }

    $tree->add_category(new category('block_gearup_achievements', get_string('achievements', 'block_gearup'), 'contact'));
    $achievements = $mr->get_achievement_instances_by_subject_id(
        $user->id,
        mission_instance::STATE_ENDED,
        $context,
        ['m.title ASC']
    );

    if (empty($achievements)) {
        $node = new node('block_gearup_achievements',
            'list',
            '',
            null,
            null,
            html_writer::tag('em', get_string('noneyetexcl', 'block_gearup'))
        );
    } else {
        $node = new node('block_gearup_achievements',
            'list',
            '',
            null,
            null,
            $output->profile_achievement_list($achievements)
        );
    }

    $tree->add_node($node);
}

/**
 * Motrain webhook hook.
 *
 * @param string $type The type.
 * @param object $payload The payload.
 */
function block_gearup_handle_block_motrain_webhook($type, $payload) {
    if (!di::get('lm')->is_active()) {
        return;
    } else if ($type !== 'user.leveledUp') {
        return;
    }

    $manager = \block_motrain\manager::instance();
    $playermapper = $manager->get_player_mapper();
    $userid = $playermapper->get_local_user_id($payload->user_id);
    if (!$userid) {
        return;
    }

    $action = new motrain_level_attained($userid, $payload->level);
    di::get('action_processor')->process_action($action);
}

/**
 * Serve file.
 *
 * @param stdClass $course The course object.
 * @param stdClass $bi Block instance record.
 * @param context $context The context object.
 * @param string $filearea The file area.
 * @param array $args List of arguments.
 * @param bool $forcedownload Whether or not to force the download of the file.
 * @param array $options Array of options.
 * @return void
 */
function block_gearup_pluginfile($course, $bi, context $context, $filearea, $args, $forcedownload, array $options = []) {
    global $USER;

    $lifetime = null;
    $immutable = false;

    $fs = get_file_storage();
    $file = null;

    if ($filearea === 'questnarrators' || $filearea === 'achievementbadges') {
        $itemid = array_shift($args);
        $filename = array_shift($args);
        $filepath = '/';

        $file = $fs->get_file($context->id, 'block_gearup', $filearea, $itemid, $filepath, $filename);
        if (!$file || strpos($file->get_mimetype(), 'image/') !== 0) {
            return false;
        }

    } else if ($filearea === 'speech') {
        $fileserver = speech_file_server::from_pluginfile($context, $USER->id, $args);
        if (!$fileserver->can_access()) {
            return false;
        }
        $file = $fileserver->get_file();
    }

    if (!$file) {
        return false;
    }

    $options['immutable'] ??= $immutable;
    send_stored_file($file, $lifetime, 0, $forcedownload, $options);
}

/**
 * Get user preferences.
 *
 * @return array
 */
function block_gearup_user_preferences() {
    return [
        'block_gearup_muted' => [
            'type' => PARAM_BOOL,
            'default' => false,
            'permissioncallback' => function ($user) {
                global $USER;
                return $user->id == $USER->id;
            },
        ],
    ];
}

/**
 * XP Points increased hook.
 *
 * @param \context $context The context.
 * @param int $id The state's object ID.
 * @param int $points The amount of points.
 */
function block_gearup_xp_points_increased(\context $context, $id, $points) {
    if (!di::get('lm')->is_active()) {
        return;
    }

    $action = new xp_gained($id, $points, $context);
    $processor = di::get('action_processor');
    $processor->process_action($action);
}
