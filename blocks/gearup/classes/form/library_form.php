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
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\form;

defined('MOODLE_INTERNAL') || die();

use block_gearup\di;
use moodleform;

require_once($CFG->libdir . '/formslib.php');

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class library_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('filemanager',
            'questnarrators',
            get_string('questnarrators', 'block_gearup'),
            ['class' => 'gu-accepted-types-hidden'],
            static::get_quest_narrators_options()
        );
        $mform->addElement('static', 'questnarratorsdesc', '', get_string('questnarratorsdesc', 'block_gearup'));

        $mform->addElement('filemanager',
            'achievementbadges',
            get_string('achievementbadges', 'block_gearup'),
            ['class' => 'gu-accepted-types-hidden'],
            static::get_achievement_badges_options()
        );
        $mform->addElement('static', 'achievementbadgesdesc', '', get_string('achievementbadgesdesc', 'block_gearup'));

        $this->add_action_buttons();
    }

    public static function get_achievement_badges_options() {
        $lm = di::get('lm');
        $maxfiles = $lm->max_achievement_badges();
        $maxfiles = $maxfiles === null ? -1 : max(1, $maxfiles);
        return ['subdirs' => 0, 'maxfiles' => $maxfiles, 'accepted_types' => ['image/jpeg', 'image/png']];
    }

    public static function get_quest_narrators_options() {
        $lm = di::get('lm');
        $maxfiles = $lm->max_quest_narrators();
        $maxfiles = $maxfiles === null ? -1 : max(1, $maxfiles);
        return ['subdirs' => 0, 'maxfiles' => $maxfiles, 'accepted_types' => ['image/jpeg', 'image/png']];
    }

}
