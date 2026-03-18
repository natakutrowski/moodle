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
 * Form.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\form\wizard;

use block_gearup\di;
use block_gearup\local\model\mission;
use block_gearup\local\utils\form_utils;
use context;
use core\form\persistent;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class achievement_identity_form extends persistent {

    protected static $persistentclass = mission::class;

    protected function definition() {
        $mform = $this->_form;
        $context = context::instance_by_id($this->get_persistent()->get('contextid'));

        $mform->addElement('text', 'title', get_string('missiontitle', 'block_gearup'), [
            'placeholder' => $this->_customdata['placeholdertitle'] ?? '',
        ]);
        $mform->addRule('title', null, 'required', null, 'client');

        $visualsrepo = di::get('achievement_badges_repository');
        form_utils::add_image_group_from_repository($mform, 'visual', get_string('appearance', 'core'), $visualsrepo, $context);
        $mform->addRule('visual', null, 'required', null, 'client');

        $this->add_action_buttons(false, get_string('continue'));
    }

}
