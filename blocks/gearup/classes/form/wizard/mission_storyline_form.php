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

use block_gearup\local\model\mission;
use core\form\persistent;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_storyline_form extends persistent {

    static protected $persistentclass = mission::class;

    protected function definition() {
        $mform = $this->_form;
        $mh = $this->_customdata['missionhelper'];
        $mission = $this->_customdata['mission'];
        $isquest = $mh->is_a_quest($mission);
        $isachievement = $mh->is_an_achievement($mission);
        $isstreak = $mh->is_a_streak($mission);

        if ($isquest && !$mh->is_compulsory($mission)) {
            $mform->addElement('textarea', 'description', get_string('narrativebeforetheyacceptquest', 'block_gearup'),
                ['rows' => 5]);
            $mform->addHelpButton('description', 'storylinedescription', 'block_gearup');
            $mform->addRule('description', null, 'required', null, 'client');
        }

        $instructionslabel = 'narrativeduringobjectivesquest';
        $instructionshelp = 'storylineinstructions';
        $instructionsplaceholder = null;
        if ($isachievement) {
            $instructionslabel = 'achievementinstructions';
            $instructionshelp = 'achievementinstructions';
            $instructionsplaceholder = 'achievementinstructionsplaceholder';
        } else if ($isstreak) {
            $instructionslabel = 'streakinstructions';
            $instructionshelp = 'streakinstructions';
            $instructionsplaceholder = 'streakinstructionsplaceholder';
        }
        $mform->addElement('textarea', 'instructions', get_string($instructionslabel, 'block_gearup'), [
            'rows' => 5,
            'placeholder' => $instructionsplaceholder ? get_string($instructionsplaceholder, 'block_gearup') : '',
        ]);
        $mform->addHelpButton('instructions', $instructionshelp, 'block_gearup');
        $mform->addRule('instructions', null, 'required', null, 'client');

        if ($isquest) {
            $mform->addElement('textarea', 'feedback', get_string('narrativeduringaftercompletequest', 'block_gearup'),
                ['rows' => 5]);
            $mform->addHelpButton('feedback', 'storylinefeedback', 'block_gearup');
            $mform->addRule('feedback', null, 'required', null, 'client');
        }

        $this->add_action_buttons(false, get_string('continue', 'core'));
    }

    protected function get_default_data() {
        $data = parent::get_default_data();
        $data->feedback = get_string('defaultfeedbackquest', 'block_gearup');
        $data->description = get_string('defaultdescriptionquest', 'block_gearup');
        if ($this->_customdata['missionhelper']->is_a_quest($this->_customdata['mission'])) {
            $data->instructions = get_string('defaultinstructionsquest', 'block_gearup');
        }
        return $data;
    }
}
