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

namespace block_gearup\local\repository;

use block_gearup\local\model\mission as mission_model;

/**
 * Query.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_instance_query extends query_base {

    /** @var string[] One of the acceptable statuses. */
    protected static $statuses = [
        // Is in progress.
        'inprogress_zero',
        'inprogress_partial',
        // Is this exact state.
        'is_assigned',
        'is_started',
        'is_completed',
        'is_ended',
        // Is in state, or later.
        'has_started',
        'has_completed',
        // Is not yet in state.
        'not_started',
        'not_completed',
        'not_ended',
    ];

    /**
     * Add order.
     *
     * The first call to this sets the most significant field.
     *
     * @param string $field The order field.
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     * @return self
     */
    public function add_order_by($name, $dir = SORT_ASC): self {
        if (in_array($name, ['firstname', 'lastname', 'email', 'username', 'id', 'idnumber'])) {
            $name = 'subject:' . $name;
        }
        return $this->append_order_by($name, $dir);
    }

    /**
     * Filter by context IDs.
     *
     * @param int[] $contextids The IDs.
     * @return self
     */
    public function filter_by_context_ids(array $ids): self {
        if (empty($ids)) {
            $this->unset_condition('contextids');
            return $this;
        }
        $this->set_condition('contextids', $ids);
        return $this;
    }

    /**
     * Filter by counter.
     *
     * @param int $value The counter.
     * @return self
     */
    public function filter_by_counter_gte(int $value): self {
        if (empty($value)) {
            $this->unset_condition('counter_gte');
            return $this;
        }
        $this->set_condition('counter_gte', $value);
        return $this;
    }

    /**
     * Filter by mission types.
     *
     * @param string|null $types One of the types to match.
     * @return self
     */
    public function filter_by_mission_types(?array $types = null): self {
        if (empty($types)) {
            $this->unset_condition('mission:types');
            return $this;
        }
        $this->set_condition('mission:types', $types);
        return $this;
    }

    /**
     * Filter by status.
     *
     * @param string|null $status One of the statuses.
     * @return self
     */
    public function filter_by_status($status = null): self {
        if ($status === null) {
            $this->unset_condition('status');
            return $this;
        }
        if (!in_array($status, self::$statuses)) {
            throw new \InvalidArgumentException("Invalid status: $status");
        }
        $this->set_condition('status', $status);
        return $this;
    }

    /**
     * Filter subject by term.
     *
     * @param string|null $term The string to match.
     * @return self
     */
    public function filter_subject_by_term(?string $term = null): self {
        $term = trim($term ?? '');
        if (empty($term)) {
            $this->unset_condition('subject:term');
            return $this;
        }
        $this->set_condition('subject:term', $term);
        return $this;
    }

    /**
     * Set the context ID.
     *
     * @param int|null $contextid The context ID.
     * @return self
     */
    public function set_context_id(?int $contextid): self {
        if (!$contextid) {
            $this->unset_condition('contextid');
            return $this;
        }
        $this->set_condition('contextid', $contextid);
        return $this;
    }

    /**
     * Set the group ID.
     *
     * @param int|null $groupid The group ID.
     * @return self
     */
    public function set_group_id(?int $groupid): self {
        if (!$groupid) {
            $this->unset_condition('groupid');
            return $this;
        }
        $this->set_condition('groupid', $groupid);
        return $this;
    }

    /**
     * Set the mission ID.
     *
     * @param int|null $missionid The mission ID.
     * @return self
     */
    public function set_mission_id(?int $missionid): self {
        if (!$missionid) {
            $this->unset_condition('missionid');
            return $this;
        }
        $this->set_condition('missionid', $missionid);
        return $this;
    }

    /**
     * Set the mission state.
     *
     * @param int|null $state The mission state from mission::STATE_*.
     * @return self
     */
    public function set_mission_state(?int $state): self {
        if ($state === null) {
            $this->unset_condition('mission:state');
            return $this;
        }
        $this->set_condition('mission:state', $state);
        return $this;
    }

    /**
     * Set the mission type.
     *
     * @param string|int|null $type The mission type (And model\mission::TYPE_* for backwards compat).
     * @return self
     */
    public function set_mission_type($type): self {
        if ($type === null) {
            $this->unset_condition('mission:type');
            return $this;
        }

        if (is_int($type)) {
            debugging('Using int for type is deprecated, use the string representation instead.', DEBUG_DEVELOPER);
            $type = mission_model::convert_internal_type($type);
        }

        $this->set_condition('mission:type', $type);
        return $this;
    }

    /**
     * Set the needs attention.
     *
     * @param int $na The needs attention.
     * @return self
     */
    public function set_needs_attention(?bool $na): self {
        if ($na === null) {
            $this->unset_condition('needsattention');
            return $this;
        }
        $this->set_condition('needsattention', $na);
        return $this;
    }

    /**
     * Set the state.
     *
     * @param int $state The state.
     * @return self
     */
    public function set_state(?int $state): self {
        if ($state === null) {
            $this->unset_condition('state');
            return $this;
        }
        $this->set_condition('state', $state);
        return $this;
    }

    /**
     * Set the subject ID.
     *
     * @param int|null $subjectid The subject ID.
     * @return self
     */
    public function set_subject_id(?int $subjectid): self {
        if (!$subjectid) {
            $this->unset_condition('subjectid');
            return $this;
        }
        $this->set_condition('subjectid', $subjectid);
        return $this;
    }

}
