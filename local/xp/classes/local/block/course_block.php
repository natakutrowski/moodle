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

namespace local_xp\local\block;

use lang_string;
use stdClass;
use block_xp\local\config\course_world_config;
use block_xp\local\world;
use core_user;
use local_xp\local\config\default_course_world_config;
use local_xp\local\xp\level_with_popup_message;

/**
 * Block class.
 *
 * @package    local_xp
 * @copyright  2018 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_block extends \block_xp\local\block\course_block {
    /**
     * Applicable formats.
     *
     * @return array
     */
    public function applicable_formats() {
        $formats = parent::applicable_formats();
        $config = \block_xp\di::get('config');
        if ($config->get('context') == CONTEXT_SYSTEM) {
            $formats += ['user-profile' => true];
        } else if ($config->get('enableglobalworld')) {
            $formats += ['my' => true, 'site' => true, 'user-profile' => true];
        }
        return $formats;
    }

    /**
     * Whether we can show the user states.
     *
     * @param world $world
     * @param null|int $userid
     * @return bool
     */
    protected function can_show_user_state(world $world, $userid): bool {
        if (!$userid || !core_user::is_real_user($userid, false)) {
            return false;
        }

        $config = $world->get_config();
        if (!$config->get('enableladder')) {
            return false;
        } else if ($config->get('identitymode') != course_world_config::IDENTITY_ON) {
            return false;
        }

        $state = \block_xp\di::get(\local_xp\local\leaderboard\participation\service_factory::class)
            ->get_for_context($world->get_context())
            ->get_state($userid);

        return $state->is_participating();
    }

    /**
     * Get the content for the profile.
     *
     * @param int $userid The user whose block we're showing.
     * @return string
     */
    public function get_content_for_profile($userid) {
        global $USER;

        // Neither of the users should be guests or not authenticated.
        if (!$userid || isguestuser($userid) || !$USER->id || isguestuser()) {
            return '';
        }

        // Confirm that we can view the block, and that the target user can view it too.
        $world = $this->get_world_for_page();
        $theycanview = $world->get_access_permissions()->can_access($userid);
        $icanview = $world->get_access_permissions()->can_access($USER->id);
        $icanedit = $world->get_access_permissions()->can_manage($USER->id);

        // The block should not be displayed.
        if (!$theycanview && !($icanview || $icanedit)) {
            return '';
        }

        $renderer = \block_xp\di::get('renderer');
        $state = $world->get_store()->get_state($userid);
        $config = $world->get_config();

        // Navigation.
        $actions = [];
        if ($userid == $USER->id) {
            $actions = $this->get_block_navigation_for_world($world);
        }

        // Widget.
        $widget = new \block_xp\output\xp_widget($state, [], null, $actions);

        // When XP gain is disabled, let the teacher now.
        if (!$config->get('enabled') && $icanedit) {
            $widget->add_manager_notice(new lang_string('xpgaindisabled', 'block_xp'));
        }

        // Render the thing.
        return $renderer->render($widget);
    }

    /**
     * Get content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $USER;

        if (isset($this->content)) {
            return $this->content;
        }

        // Can we show the information of other users? For now, let's assume that if the block
        // is visible on one's profile and that the ladder is enabled, and names not hidden, then
        // we can display other's information.
        $world = $this->get_world_for_page();
        $canshow = $world->get_config()->get('enableladder')
            && $world->get_config()->get('identitymode') == course_world_config::IDENTITY_ON;

        // Do not display the block when it's not set as.
        $iscourseonly = \block_xp\di::get('config')->get('context') == CONTEXT_COURSE
            && !\block_xp\di::get('config')->get('enableglobalworld');

        // Detect whether we are on the system profile page, or the course profile page.
        $onsiteprofile = $this->page->pagetype == 'user-profile';
        $oncourseprofile = strpos($this->page->pagetype, 'course-view-') === 0
            && $this->page->context->contextlevel == CONTEXT_COURSE
            && $this->page->docspath == 'user/profile'
            && preg_match('/(^|\s)path-user(\s|$)/', $this->page->bodyclasses);

        if ($canshow && ((!$iscourseonly && $onsiteprofile) || $oncourseprofile)) {
            $userid = null;

            if ($onsiteprofile) {
                // When we are on the admin's view of the default profile page, mock the user to the current one.
                $userid = $this->page->context->contextlevel == CONTEXT_USER ? $this->page->context->instanceid : $USER->id;
            } else if ($oncourseprofile) {
                // Read the ID from the URL.
                $userid = optional_param('id', null, PARAM_INT);
            }

            // If we're happy with our quest for a user ID, display proceed.
            if ($userid !== null && $USER->id != $userid) {
                $this->content = new stdClass();
                $this->content->text = '';
                $this->content->footer = '';
                $this->content->text = $this->can_show_user_state($world, $userid) ? $this->get_content_for_profile($userid) : '';
                return $this->content;
            }
        }

        // Normal behaviour of showing the current user's block.
        return parent::get_content();
    }

    /**
     * Get popup notification props.
     *
     * @param object $renderer The renderer.
     * @param \block_xp\local\world $world The world.
     * @param \block_xp\local\xp\level $level The level.
     * @param \block_xp\local\xp\level $prevlevel The previous level.
     */
    protected function get_popup_notification_props($renderer, $world, $level, $prevlevel) {
        $props = parent::get_popup_notification_props($renderer, $world, $level, $prevlevel);
        $props += [
            'message' => $level instanceof level_with_popup_message ? $level->get_popup_message() : null,
        ];
        return $props;
    }

    /**
     * Get the widget.
     *
     * @param \block_xp\local\world $world The world.
     * @param \block_xp\local\xp\state $state The user's state.
     * @return \block_xp\output\xp_widget The widget.
     */
    protected function get_widget($world, $state) {
        $widget = parent::get_widget($world, $state);
        $config = $world->get_config();

        // Hide the next level when the progress bar does not refer to the next level.
        if ($config->get('progressbarmode') != default_course_world_config::PROGRESS_BAR_MODE_LEVEL) {
            $widget->set_show_next_level(false);
        }

        return $widget;
    }
}
