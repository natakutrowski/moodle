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
 * Mission completed reason.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\xp;

use block_gearup\di;
use block_gearup\local\mission\mission;

/**
 * Mission completed reason.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mission_completed_reason implements
        compat\reason,
        compat\reason_with_short_description,
        compat\reason_with_location {

    /** @var mission|null|false $mission The mission, do not use directly, refer to self::get_mission() instead. */
    protected $mission = null;
    /** @var int $missioninstid The mission instance the reward originated from. */
    protected $missioninstid;

    /**
     * Constructor.
     *
     * @param int $missioninstid The mission instance the reward originated from.
     */
    public function __construct($missioninstid) {
        $this->missioninstid = $missioninstid;
    }

    public function get_location_name() {
        $mission = $this->get_mission();
        if (!$mission) {
            return null;
        }
        return $mission->get_title();
    }

    public function get_location_url() {
        $urlresolver = di::get('url_resolver');
        $missionid = $this->get_mission() ? $this->get_mission()->get_id() : null;
        if (!$missionid) {
            return null;
        }
        return $urlresolver->reverse('mission_instance', [
            'missionid' => $missionid,
            'missioninstid' => $this->missioninstid,
        ]);
    }

    /**
     * Get the mission.
     *
     * @return mission|null Null if not found.
     */
    protected function get_mission() {
        if ($this->mission === null) {
            try {
                $this->mission = di::get('repository')->get_mission_by_instanceid($this->missioninstid);
            } catch (\Exception $e) {
                $this->mission = false;
            }
        }
        return $this->mission ?: null;
    }

    public function get_signature() {
        return $this->missioninstid;
    }

    public static function from_signature($signature) {
        return new static($signature);
    }

}
