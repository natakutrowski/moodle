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
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller;

use block_gearup\di;
use block_gearup\local\mission\mission_instance as mission_inst;
use block_gearup\local\controller\utils\mission_route_base;
use block_gearup\output\mission_instance_page;
use core_user;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_instance extends mission_route_base {

    protected $missionnavname = 'mission_users';
    /** @var mission_inst The mission instance. */
    protected $missioninst;
    /** @var bool */
    protected $supportsgroups = true;  // To validate access.

    protected function define_optional_params() {
        return [
            ['action', null, PARAM_ALPHANUMEXT, false],
            ['objid', null, PARAM_INT, false],
            ['returnto', null, PARAM_ALPHANUMEXT],
        ];
    }

    protected function post_login() {
        parent::post_login();

        $missioninstid = $this->get_param('missioninstid');
        $repository = di::get('repository');
        $missioninst = $repository->get_instance($missioninstid);
        if ($this->mission->get_id() != $missioninst->get_mission()->get_id()) {
            throw new \coding_exception('Instance not found.');
        }
        $this->missioninst = $missioninst;
    }

    protected function get_subpage_html_head_title() {
        return fullname(core_user::get_user($this->missioninst->get_subject_id()));
    }

    protected function pre_content() {
        parent::pre_content();

        $action = $this->get_param('action');
        $missionoperator = di::get('mission_operator');
        $missionhelper = di::get('mission_helper');

        if ($action == 'startmission' && !$missionhelper->has_started($this->missioninst)) {
            if (confirm_sesskey()) {
                $missionoperator->start_instance($this->missioninst);
            }
            $this->redirect();
        }
    }

    protected function content() {
        $output = $this->get_renderer();

        $this->page_mission_header();
        $this->page_mission_navigation();

        $renderable = (new mission_instance_page($this->missioninst, $this->urlresolver))
            ->set_return_to($this->get_param('returnto'));
        echo $output->render($renderable);
    }

}
