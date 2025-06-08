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
 * Static action.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\action;

use context;

/**
 * Static action.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class static_action implements action {

    protected $userid;
    protected $objectid;
    protected $context;
    protected $contextid;
    protected $targetuserid;
    protected $time;

    public function __construct($userid, $contextorid, $objectid = null, $targetuserid = null) {
        $this->userid = (int) $userid;
        $this->objectid = $objectid !== null ? (int) $objectid : null;
        $this->context = $contextorid instanceof \context ? $contextorid : null;
        $this->contextid = (int) ($this->context ? $this->context->id : $contextorid);
        $this->targetuserid = $targetuserid !== null ? (int) $targetuserid : $targetuserid;
        $this->time = new \DateTimeImmutable();
    }

    public function get_user_id(): int {
        return $this->userid;
    }

    public function get_object_id(): ?int {
        return $this->objectid;
    }

    public function get_context(): \context {
        if (!$this->context) {
            $this->context = context::instance_by_id($this->contextid);
        }
        return $this->context;
    }

    public function get_target_user_id(): ?int {
        return $this->targetuserid;
    }

    public function get_time(): \DateTimeImmutable {
        return $this->time;
    }

}
