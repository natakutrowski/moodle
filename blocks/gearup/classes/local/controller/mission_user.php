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
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller;

use block_gearup\di;
use block_gearup\local\controller\utils\mission_route_base;
use block_gearup\local\exporter\user_exporter;
use block_gearup\local\repository\mission_instance_query;
use block_gearup\table\mission_instances;
use core_user;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_user extends mission_route_base {

    protected $missionnavname = 'mission_users';
    /** @var \stdClass The user. */
    protected $user;
    /** @var bool */
    protected $supportsgroups = true;  // To validate access.

    protected function define_optional_params() {
        return [
            ['action', false, PARAM_ALPHANUMEXT, false],
            ['missioninstid', 0, PARAM_INT, false],
        ];
    }

    protected function post_login() {
        parent::post_login();

        $userid = $this->get_param('userid');
        $user = core_user::get_user($userid, '*', MUST_EXIST);
        $this->user = $user;
    }

    protected function get_subpage_html_head_title() {
        return fullname($this->user);
    }

    protected function pre_content() {
        parent::pre_content();

        if ($this->get_param('action') === 'delete') {

            if (!$this->missionhelper->is_active($this->mission)) {
                throw new \coding_exception('The mission is not active.');
            }

            $id = $this->get_param('missioninstid');
            if ($id) {
                require_sesskey();
                $missioninst = di::get('repository')->get_instance($id);
                di::get('mission_operator')->delete_instance($missioninst);
            };

            $this->redirect();
        }
    }

    protected function content() {
        global $PAGE;
        $output = $this->get_renderer();

        $this->page_mission_header();
        $this->page_mission_navigation();

        $repository = di::get('repository');

        $query = (new mission_instance_query($this->mission->get_context()));
        $table = (new mission_instances())
            ->define_mission($this->mission)
            ->define_repository($repository)
            ->define_url_resolver($this->urlresolver)
            ->define_mission_helper($this->missionhelper)
            ->define_has_dropdown(true)
            ->define_subject_id($this->user->id)
            ->define_query($query)
            ->init();
        $table->define_baseurl($this->pageurl);

        ob_start();
        $table->out(20);
        $content = ob_get_contents();
        ob_end_clean();

        $data = [
            'table' => $content,
            'subject' => (new user_exporter($this->user, ['context' => $this->context]))->export($output),
            'listurl' => $this->urlresolver->reverse('mission_users', ['missionid' => $this->mission->get_id()]),
        ];
        echo $output->render_from_template('block_gearup/mission_user', $data);
        $PAGE->requires->js_call_amd('block_gearup/modal_form', 'registerOpen', ['[data-action="open-form"]']);
    }
}
