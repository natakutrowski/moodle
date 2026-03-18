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

namespace block_gearup\form;

use block_gearup\di;
use block_gearup\local\mission\mission_instance;
use html_writer;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mission_instance_dynamic_form_base extends mission_dynamic_form_base {

    protected $paramname = 'missionid';
    protected $supportsgroups = true;

    /** @var mission_instance */
    private $missioninst;

    protected function definition() {
        parent::definition();

        $mform = $this->_form;
        $mform->addElement('hidden', 'missioninstid');
        $mform->setType('missioninstid', PARAM_INT);
    }

    protected function get_default_data() {
        return [
            'missioninstid' => $this->get_mission_instance()->get_id(),
        ];
    }

    /**
     * Get the mission instance.
     *
     * @return mission_instance
     */
    final protected function get_mission_instance(): mission_instance {
        if (!$this->missioninst) {
            $missioninst = di::get('repository')->get_instance($this->optional_param('missioninstid', 0, PARAM_INT));
            if ($missioninst->get_mission()->get_id() !== $this->get_mission()->get_id()) {
                throw new \moodle_exception('notfound', 'block_gearup');
            }
            $this->missioninst = $missioninst;
        }
        return $this->missioninst;
    }

}
