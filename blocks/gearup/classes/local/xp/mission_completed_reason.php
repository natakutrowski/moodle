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
use block_gearup\local\mission\mission_instance;

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
    compat\reason_with_location,
    compat\reason_with_short_description,
    compat\reason_with_tracking {

    /** @var mission|null|false $mission The mission, do not use directly, refer to self::get_mission() instead. */
    protected $mission = null;

    /** @var ?int The env ID. */
    protected $envid;
    /** @var ?int The object ID. */
    protected $objectid;
    /** @var ?int The parent ID. */
    protected $parentid;

    /**
     * Constructor.
     *
     * @param int|null $missioninstid Deprecated argument.
     */
    public function __construct(?int $missioninstid = null) {
        $this->set_object_id($missioninstid ?: null);
    }

    public function get_location_name() {
        $mission = $this->get_mission();
        if (!$mission) {
            return null;
        }
        return $mission->get_title();
    }

    public function get_location_url() {
        $mission = $this->get_mission();
        if (!$mission || !$mission->get_id()) {
            return null;
        }
        $urlresolver = di::get('url_resolver_factory')->get_resolver_for_context($mission->get_context(), null);
        return $urlresolver->reverse('mission_instance', [
            'missionid' => $mission->get_id(),
            'missioninstid' => $this->get_object_id(),
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
                $missioninstid = $this->get_object_id();
                $this->mission = $missioninstid ? di::get('repository')->get_mission_by_instanceid($missioninstid) : false;
            } catch (\Exception $e) {
                $this->mission = false;
            }
        }
        return $this->mission ?: null;
    }

    /**
     * Get the environment ID.
     *
     * Prior to XP 20, this would not have been set.
     *
     * @return int|null
     */
    public function get_env_id(): ?int {
        return $this->envid;
    }

    /**
     * Get the object ID.
     *
     * @return int|null
     */
    public function get_object_id(): ?int {
        return $this->objectid;
    }

    /**
     * Get the parent ID.
     *
     * @return int|null
     */
    public function get_parent_id(): ?int {
        return $this->parentid;
    }

    /**
     * Set the environment ID.
     *
     * @param int|null $envid The environment ID.
     */
    public function set_env_id(?int $envid): void {
        $this->envid = $envid;
    }

    /**
     * Set the object ID.
     *
     * @param int|null $objectid The object ID.
     */
    public function set_object_id(?int $objectid): void {
        $this->objectid = $objectid;
    }

    /**
     * Set the parent ID.
     *
     * @param int|null $parentid The parent ID.
     */
    public function set_parent_id(?int $parentid): void {
        $this->parentid = $parentid;
    }

    /**
     * Get signature.
     *
     * @deprecated Since XP 20.
     */
    public function get_signature() {
        return $this->get_object_id();
    }

    /**
     * From signature.
     *
     * @param string $signature The signature.
     * @return static
     * @deprecated Since XP 20.
     */
    public static function from_signature($signature) {
        return new static($signature);
    }

    /**
     * Make from mission instance.
     *
     * @param mission_instance $missioninst The mission instance.
     * @return static
     */
    public static function from_mission_instance(mission_instance $missioninst) {
        $reason = new static();
        $reason->set_object_id($missioninst->get_id());
        // Logs generated prior to XP 20 will not have any envid.
        $reason->set_env_id($missioninst->get_mission()->get_context()->id);
        return $reason;
    }
}
