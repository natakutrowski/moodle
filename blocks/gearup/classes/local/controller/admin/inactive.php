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
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller\admin;

use block_gearup\di;
use block_gearup\local\controller\utils\route_base;
use cache_helper;
use context_system;
use moodle_url;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inactive extends route_base {

    protected $requiremanage = false;

    protected function define_optional_params() {
        return [
            ['action', null, PARAM_ALPHANUMEXT, false],
        ];
    }

    protected function get_page_html_head_title() {
        return get_string('licenceexpired', 'block_gearup');
    }

    protected function pre_content() {
        parent::pre_content();
        $lm = di::get('lm');

        if ($lm->is_active()) {
            return $this->redirect($this->urlresolver->reverse('missions'));
        }

        if (!$this->accessperms->can_manage()) {
            return $this->redirect(new \moodle_url('/'), get_string('thispageiscurrentlyunavailable', 'block_gearup'));
        }

        if ($this->get_param('action') === 'purge' && confirm_sesskey()) {
            cache_helper::purge_by_definition('block_gearup', 'metadata');
            $fs = get_file_storage();
            $fs->delete_area_files(context_system::instance()->id, 'block_gearup', 'metadata');

            return $this->redirect(null, get_string('cachepurged', 'block_gearup'));

        } else if ($this->get_param('action') === 'deactivate' && confirm_sesskey()) {
            unset_config('activationid', 'block_gearup');
            unset_config('activationtoken', 'block_gearup');
            unset_config('webhooksecret', 'block_gearup');

            cache_helper::purge_by_definition('block_gearup', 'metadata');
            $fs = get_file_storage();
            $fs->delete_area_files(context_system::instance()->id, 'block_gearup', 'metadata');

            return $this->redirect(null, get_string('licencedeactivated', 'block_gearup'));
        }
    }

    protected function content() {
        $output = $this->get_renderer();
        $purgeurl = new moodle_url($this->pageurl, ['action' => 'purge', 'sesskey' => sesskey()]);
        $deactivateurl = new moodle_url($this->pageurl, ['action' => 'deactivate', 'sesskey' => sesskey()]);
        echo $output->render_from_template('block_gearup/inactive', [
            'levelupurl' => 'https://www.levelup.plus',
            'canmanagelicence' => $this->accessperms->can_manage_licence(),
            'activationid' => di::get('lm')->get_activation_id(),
            'purgeurl' => $purgeurl->out(false),
            'deactivateurl' => $deactivateurl->out(false),
        ]);
    }

}
