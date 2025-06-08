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
 * Action.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\action;

use grade_grade;

/**
 * Action.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class grade_action extends static_action {

    /** @var grade_grade|false The grade object. */
    protected $gradeobject;

    /**
     * Get the grade object.
     *
     * @return grade_grade|false
     */
    public function get_grade_object() {
        if ($this->gradeobject === null) {
            $this->gradeobject = \grade_grade::fetch(['id' => $this->get_object_id()]);
        }
        return $this->gradeobject;
    }

    /**
     * Get the final grade.
     *
     * @return float|null
     */
    public function get_final_grade() {
        $gradeobject = $this->get_grade_object();
        if (!$gradeobject) {
            return null;
        }
        return $gradeobject->finalgrade !== null ? $gradeobject->finalgrade : null;
    }

    /**
     * Set the grade object.
     *
     * @param grade_grade|false|null $gradeobject The grade object, or falsey.
     */
    public function set_grade_object($gradeobject) {
        $this->gradeobject = $gradeobject ?: false;
    }

}
