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

namespace block_gearup\local\controller\utils;

use block_gearup\di;
use block_gearup\local\mission\persisted_achievement;
use block_gearup\local\mission\persisted_challenge;
use block_gearup\local\mission\persisted_quest;
use block_gearup\local\mission\persisted_streak;
use block_gearup\local\routing\url;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mission_route_base extends route_base {

    /** @var persisted_quest|persisted_achievement|persisted_challenge The mission. */
    protected $mission;
    /** @var string */
    protected $missionnavname;
    /** @var mission_helper The helper. */
    protected $missionhelper;

    /**
     * Is achievement?
     *
     * @return boolean
     */
    protected function is_achievement() {
        return $this->missionhelper->is_an_achievement($this->mission);
    }

    /**
     * Is challenge?
     *
     * @return boolean
     */
    protected function is_challenge() {
        return $this->missionhelper->is_a_challenge($this->mission);
    }

    /**
     * Is quest?
     *
     * @return boolean
     */
    protected function is_quest() {
        return $this->missionhelper->is_a_quest($this->mission);
    }

    /**
     * Is streak?
     *
     * @return boolean
     */
    protected function is_streak() {
        return $this->missionhelper->is_a_streak($this->mission);
    }

    /**
     * Get the mission navigation name.
     *
     * @return string
     */
    protected function get_mission_nav_name() {
        if (!$this->missionnavname) {
            throw new \coding_exception('Invalid mission nav name.');
        }
        return $this->missionnavname;
    }

    /**
     * Get the mission name.
     *
     * @return string
     */
    protected function get_mission_name() {
        return $this->mission->get_title();
    }

    /**
     * Get the mission URL.
     *
     * @param string $routename The route name.
     * @return url
     */
    protected function get_mission_url($routename = '') {
        $route = 'mission' . ($routename ? '_' . $routename : '');
        return $this->urlresolver->reverse($route, ['missionid' => $this->mission->get_id()]);
    }

    protected function get_page_html_head_title() {
        return get_string('missionxitemx', 'block_gearup', [
            'name' => $this->get_mission_name(),
            'item' => $this->get_subpage_html_head_title(),
        ]);
    }

    /**
     * Return the subpage name.
     *
     * @return string
     */
    abstract protected function get_subpage_html_head_title();

    /**
     * Post login.
     *
     * @return void
     */
    protected function post_login() {
        parent::post_login();
        $this->mission = di::get('repository')->get_mission($this->get_param('missionid'));
        $this->missionhelper = di::get('mission_helper');

        // We must confirm that the mission belonds to the context we're in, and that it was found.
        if (!$this->mission || $this->mission->get_context()->id != $this->context->id) {
            throw new \moodle_exception('notfound');

        } else if (!$this->mission instanceof persisted_quest
                && !$this->mission instanceof persisted_achievement
                && !$this->mission instanceof persisted_challenge
                && !$this->mission instanceof persisted_streak
        ) {
            // We are only expecting these type of missions here.
            throw new \moodle_exception('notfound');
        }
    }

    /**
     * Pre-content check.
     */
    protected function pre_content() {
        parent::pre_content();

        // Hacky check to confirm that the mission is not in the wizard mode.
        if (!in_array(mission_wizard_trait::class, class_uses($this, false))) {
            if ($this->missionhelper->is_in_wizard($this->mission)) {
                $this->redirect($this->urlresolver->reverse('mission_wizard_identity', ['missionid' => $this->mission->get_id()]));
            }
        }
    }

    /**
     * Print the mission header.
     *
     * @return void
     */
    protected function page_mission_header() {
        $output = $this->get_renderer();
        echo $output->mission_header($this->urlresolver, $this->mission);
    }

    /**
     * Print the mission navigation.
     *
     * @return void
     */
    protected function page_mission_navigation() {
        $output = $this->get_renderer();
        $mission = $this->mission;
        echo $output->navigation_for_mission_management($this->urlresolver, $mission, $this->get_mission_nav_name());
    }

}
