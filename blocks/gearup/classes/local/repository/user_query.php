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

/**
 * Query.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_query extends query_base {

    /** @var string[] The valid order bys. */
    protected $validorderbys = ['firstname', 'lastname', 'username', 'idnumber', 'email', 'id',
        'mission_instance_counter_best', 'mission_instance_counter_latest'];

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
        if (!in_array($name, $this->validorderbys)) {
            throw new \InvalidArgumentException("Invalid order by: $name");
        }
        return $this->append_order_by($name, $dir);
    }

    /**
     * Annotation mission count.
     *
     * @return self
     */
    public function annotate_mission_count(): self {
        $this->add_annotation('mission_count');
        return $this;
    }

    /**
     * Annotation mission instance best counter.
     *
     * @return self
     */
    public function annotate_mission_instance_counter_best(): self {
        $this->add_annotation('mission_instance_counter_best');
        return $this;
    }

    /**
     * Annotation mission instance latest counter.
     *
     * @return self
     */
    public function annotate_mission_instance_counter_latest(): self {
        $this->add_annotation('mission_instance_counter_latest');
        return $this;
    }

    /**
     * Annotation mission instance counter reset count.
     *
     * @return self
     */
    public function annotate_mission_instance_counter_reset_count(): self {
        $this->add_annotation('mission_instance_counter_reset_count');
        return $this;
    }

    /**
     * Annotation mission instance count.
     *
     * @return self
     */
    public function annotate_mission_instance_count(): self {
        $this->add_annotation('mission_instance_count');
        return $this;
    }

    /**
     * Annotation mission instance 100% count.
     *
     * @return self
     */
    public function annotate_mission_instance_100pc_count(): self {
        $this->add_annotation('mission_instance_100pc_count');
        return $this;
    }

    /**
     * Annotation mission instance 100% count.
     *
     * @return self
     */
    public function annotate_mission_instance_ended_count(): self {
        $this->add_annotation('mission_instance_ended_count');
        return $this;
    }

    /**
     * Filter by term.
     *
     * @param string|null $term The string to match.
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
     * Filter by term.
     *
     * @param string|null $term The string to match.
     * @return self
     */
    public function filter_by_term(?string $term = null): self {
        $term = trim($term ?? '');
        if (empty($term)) {
            $this->unset_condition('term');
            return $this;
        }
        $this->set_condition('term', $term);
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

}
