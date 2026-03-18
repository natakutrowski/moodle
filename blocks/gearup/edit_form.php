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

defined('MOODLE_INTERNAL') || die();

// Workaround code that would have been written in a way that does not load the form.
require_once($CFG->dirroot . '/blocks/edit_form.php');

/**
 * Block instance settings.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_gearup_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));
        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_gearup'));
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('select', 'config_hidetracker', get_string('trackervisibility', 'block_gearup'), [
            0 => get_string('visible', 'core'),
            1 => get_string('hidden', 'block_gearup'),
        ]);
        $mform->addHelpButton('config_hidetracker', 'trackervisibility', 'block_gearup');
    }

}
