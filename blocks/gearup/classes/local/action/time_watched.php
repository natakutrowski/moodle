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

use context;

/**
 * Action.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class time_watched extends static_action {

    /** @var int The time watched in seconds. */
    protected $duration;
    /** @var string The source unique identifier. */
    protected $sourceid;

    public function __construct($userid, context $context, int $duration, string $sourceid) {
        $this->duration = (int) $duration;
        $this->sourceid = $sourceid;
        return parent::__construct($userid, $context);
    }

    /**
     * Get the duration
     *
     * @return int The duration
     */
    public function get_duration(): int {
        return $this->duration;
    }

    /**
     * Get the source ID.
     *
     * @return string The source ID.
     */
    public function get_source_id(): string {
        return $this->sourceid;
    }

}
