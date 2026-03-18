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

namespace availability_gearup;

use block_gearup\di;

/**
 * Condition class.
 *
 * @package    availability_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** In state assigned. */
    const MODE_IS_ASSIGNED = 1;
    /** In state started. */
    const MODE_IS_STARTED = 2;
    /** In state completed. */
    const MODE_IS_COMPLETED = 3;
    /** In state ended. */
    const MODE_IS_ENDED = 4;
    /** In any state. */
    const MODE_IS_RECRUIT = 5;
    /** In started state, or following. */
    const MODE_HAS_STARTED = 6;
    /** In completed state, or following. */
    const MODE_HAS_COMPLETED = 7;

    /** @var int The mission ID. */
    protected $missionid;
    /** @var int A MODE_* constant value. */
    protected $mode;

    /** @var object The mission helper. */
    protected $missionhelper;
    /** @var object The repository. */
    protected $repository;

    /**
     * Constructor.
     *
     * @param stdClass $structure Saved data.
     */
    public function __construct($structure) {
        $this->missionid = (int) ($structure->missionid ?? 0);
        $this->mode = (int) ($structure->mode ?? 0);
        $this->repository = di::get('repository');
        $this->missionhelper = di::get('mission_helper');
    }

    /**
     * Determines whether a particular item is currently available.
     *
     * @param bool $not Set true if we are inverting the condition.
     * @param info $info Item we're checking.
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user).
     * @param int $userid User ID to check availability for.
     * @return bool True if available.
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        $available = false;

        // This appears to be a misconfiguration.
        if (!$this->missionid || !$this->mode) {
            return false;
        }

        try {
            $missioninst = $this->repository->get_instance_by_subject_id($this->missionid, $userid);
        } catch (\moodle_exception $e) {
            $missioninst = null;
        }

        // No instance, none of the modes will match.
        if (!$missioninst) {
            return $not ? true : false;
        }

        switch ($this->mode) {
            case static::MODE_IS_ASSIGNED:
                $available = $this->missionhelper->is_assigned($missioninst);
                break;

            case static::MODE_IS_STARTED:
                $available = $this->missionhelper->is_started($missioninst);
                break;

            case static::MODE_IS_COMPLETED:
                $available = $this->missionhelper->is_completed($missioninst);
                break;

            case static::MODE_IS_ENDED:
                $available = $this->missionhelper->is_ended($missioninst);
                break;

            case static::MODE_IS_RECRUIT:
                $available = true;
                break;

            case static::MODE_HAS_STARTED:
                $available = $this->missionhelper->has_started($missioninst);
                break;

            case static::MODE_HAS_COMPLETED:
                $available = $this->missionhelper->has_completed($missioninst);
                break;
        }

        return $not ? !$available : $available;
    }

    /**
     * Obtains a string describing this restriction.
     *
     * @param bool $full Set true if this is the 'full information' view.
     * @param bool $not Set true if we are inverting the condition.
     * @param info $info Item we're checking.
     * @return string Information string (for admin) about all restrictions on this item.
     */
    public function get_description($full, $not, \core_availability\info $info) {
        $mission = $this->repository->get_mission($this->missionid);
        $missionname = $mission ? $mission->get_title() : '?';

        $langstring = $not ? 'missionxnotisrecruit' : 'missionxisrecruit';
        switch ($this->mode) {
            case static::MODE_IS_ASSIGNED:
                $langstring = $not ? 'missionxnotisassigned' : 'missionxisassigned';
                break;

            case static::MODE_IS_STARTED:
                $langstring = $not ? 'missionxnotisstarted' : 'missionxisstarted';
                break;

            case static::MODE_IS_COMPLETED:
                $langstring = $not ? 'missionxnotiscompleted' : 'missionxiscompleted';
                break;

            case static::MODE_IS_ENDED:
                $langstring = $not ? 'missionxnotisended' : 'missionxisended';
                break;

            case static::MODE_HAS_STARTED:
                $langstring = $not ? 'missionxnothasstarted' : 'missionxhasstarted';
                break;

            case static::MODE_HAS_COMPLETED:
                $langstring = $not ? 'missionxnothascompleted' : 'missionxhascompleted';
                break;
        }

        return get_string($langstring, 'availability_gearup', $missionname);
    }

    /**
     * Obtains a representation of the options of this condition as a string, for debugging.
     *
     * @return string Text representation of parameters.
     */
    protected function get_debug_string() {
        return '-';
    }

    /**
     * Saves tree data back to a structure object.
     *
     * @return stdClass Structure object (ready to be made into JSON format)
     */
    public function save() {
        // We must include the type, otherwise restores don't work!
        return (object) ['type' => 'gearup', 'missionid' => $this->missionid, 'mode' => $this->mode];
    }

    /**
     * Checks whether this node should be included after restore or not.
     *
     * @param string $restoreid Restore ID
     * @param int $courseid ID of target course
     * @param \base_logger $logger Logger for any warnings
     * @param string $name Name of this item (for use in warning messages)
     * @param \base_task $task Current restore task
     * @return bool True if there was any change
     */
    public function include_after_restore($restoreid, $courseid, \base_logger $logger, $name, \base_task $task) {
        return true;
    }

    /**
     * Updates this node after restore.
     *
     * @param string $restoreid Restore ID
     * @param int $courseid ID of target course
     * @param \base_logger $logger Logger for any warnings
     * @param string $name Name of this item (for use in warning messages)
     * @return bool True if there was any change
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) {
        if (!$this->missionid) {
            return false;
        }

        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'block_gearup_mission', $this->missionid);
        if (!$rec || !$rec->newitemid) {
            $logger->process('Could not find mapping for mission ' . $this->missionid, \backup::LOG_WARNING);
        } else {
            $this->missionid = (int) $rec->newitemid;
        }

        return true;
    }

}
