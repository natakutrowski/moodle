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
 * Dummy form extender.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\form;

/**
 * Dummy form extender.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dummy_extender implements extender {

    /**
     * Definition of the form.
     *
     * @param object $mform The form.
     * @return array Must return the elements added to the form.
     */
    public function definition($mform): array {
        // Do not add any logic here, extending classes should not have to call the parent.
        return [];
    }

    /**
     * Last chance to manipulate the form data.
     *
     * @param object $data The entirely submitted form data.
     * @return object
     */
    public function get_data($data) {
        // Do not add any logic here, extending classes should not have to call the parent.
        return $data;
    }

    /**
     * Apply validation.
     *
     * @param object $data The data.
     * @param array $files The files.
     * @return array Where keys are fields names, and values are error messages.
     */
    public function validation($data, $files) {
        // Do not add any logic here, extending classes should not have to call the parent.
        return [];
    }

}
