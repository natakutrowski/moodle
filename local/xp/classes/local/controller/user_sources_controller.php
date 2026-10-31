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

namespace local_xp\local\controller;

use block_xp\di;
use block_xp\local\controller\page_controller;
use block_xp\local\permission\access_report_permissions;
use block_xp\local\utils\user_utils;
use block_xp\local\xp\state_store_with_presence;
use core_reportbuilder\system_report_factory;
use core_user;
use local_xp\local\report\user_source_courses;
use local_xp\local\world\global_world;

/**
 * User sources controller.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_sources_controller extends page_controller {
    /** @var string The route name. */
    protected $routename = 'user_sources';
    /** @var string The navigation name. */
    protected $navname = 'report';
    /** @var bool Whether manage permissions are required. */
    protected $requiremanage = false;
    /** @var \stdClass The target user. */
    protected $user;

    /**
     * Check permissions.
     */
    protected function permissions_checks() {
        if (!$this->world instanceof global_world) {
            throw new \moodle_exception('errorfeaturenotenabled', 'block_xp');
        }
        parent::permissions_checks();

        $accessperms = $this->world->get_access_permissions();
        if (!$accessperms instanceof access_report_permissions) {
            throw new \coding_exception('Access permissions object requires report permissions.');
        }
        $accessperms->require_access_report();

        $userid = (int) $this->get_param('userid');
        $store = $this->world->get_store();
        if (
            !$store instanceof state_store_with_presence
                || !$store->has($userid)
                || !user_utils::is_valid_target($this->world->get_context(), $userid)
        ) {
            throw new \moodle_exception('invaliduser', 'core_error');
        }

        $this->user = core_user::get_user($userid, '*', MUST_EXIST);
        if ($this->user->deleted) {
            throw new \moodle_exception('invaliduser', 'core_error');
        }
    }

    /**
     * Get the browser page title.
     *
     * @return string
     */
    protected function get_page_html_head_title() {
        return $this->get_page_heading();
    }

    /**
     * Get the page heading.
     *
     * @return string
     */
    protected function get_page_heading() {
        return fullname($this->user);
    }

    /**
     * Output the sub-navigation.
     */
    protected function get_sub_navigation_route_name() {
        return 'report';
    }

    /**
     * Output page content.
     */
    protected function page_content() {
        $output = $this->get_renderer();
        echo $output->render_from_template('local_xp/user-sources-heading', [
            'name' => $this->get_page_heading(),
            'backurl' => $this->navigator->get_url('report')->out(false),
        ]);

        $report = system_report_factory::create(
            user_source_courses::class,
            $this->world->get_context(),
            '',
            '',
            $this->user->id
        );
        echo $report->output();
    }
}
