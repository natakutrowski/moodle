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
class mission_query extends query_base {

    /**
     * Add order.
     *
     * @param string $field The order field.
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     * @return self
     */
    public function add_order_by($name, $dir = SORT_ASC): self {
        if ($name === 'title') {
            $this->append_order_by('title', $dir);
        }
        return $this;
    }

    /**
     * Add order.
     *
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     * @return self
     */
    public function add_order_by_completion_rate($dir = SORT_ASC): self {
        $this->annotate_completion_rate();
        $this->append_order_by('completion_rate', $dir);
        return $this;
    }

    /**
     * Add order by state natural (draft, then active, then archived, ...).
     *
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     * @return self
     */
    public function add_order_by_state_natural($dir = SORT_ASC): self {
        $this->annotate_completion_rate();
        $this->append_order_by('state_natural', $dir);
        return $this;
    }

    /**
     * Add order.
     *
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     * @return self
     */
    public function add_order_by_inprogress_average_rate($dir = SORT_ASC): self {
        $this->annotate_inprogress_average_rate();
        $this->append_order_by('inprogress_average_rate', $dir);
        return $this;
    }

    /**
     * Add order.
     *
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     * @return self
     */
    public function add_order_by_recruit_count($dir = SORT_ASC): self {
        $this->annotate_recruit_count();
        $this->append_order_by('recruit_count', $dir);
        return $this;
    }

    /**
     * Add order.
     *
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     * @return self
     */
    public function add_order_by_average_completion_time($dir = SORT_ASC): self {
        $this->annotate_average_completion_time();
        $this->append_order_by('average_completion_time', $dir);
        return $this;
    }

    /**
     * Add order.
     *
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     * @return self
     */
    public function add_order_by_fastest_completion_time($dir = SORT_ASC): self {
        $this->annotate_fastest_completion_time();
        $this->append_order_by('fastest_completion_time', $dir);
        return $this;
    }

    /**
     * Add order.
     *
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     * @return self
     */
    public function add_order_by_slowest_completion_time($dir = SORT_ASC): self {
        $this->annotate_slowest_completion_time();
        $this->append_order_by('slowest_completion_time', $dir);
        return $this;
    }

    /**
     * Add order.
     *
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     * @return self
     */
    public function add_order_by_success_rate($dir = SORT_ASC): self {
        $this->annotate_success_rate();
        $this->append_order_by('success_rate', $dir);
        return $this;
    }

    /**
     * Annotation completion time.
     *
     * @return self
     */
    public function annotate_average_completion_time(): self {
        $this->add_annotation('average_completion_time');
        return $this;
    }

    /**
     * Annotation highest counter.
     *
     * @return self
     */
    public function annotate_highest_counter(): self {
        $this->add_annotation('highest_counter');
        return $this;
    }

    /**
     * Annotation highest current counter (not ended).
     *
     * @return self
     */
    public function annotate_highest_counter_current(): self {
        $this->add_annotation('highest_counter_current');
        return $this;
    }

    /**
     * Annotation completion time.
     *
     * @return self
     */
    public function annotate_fastest_completion_time(): self {
        $this->add_annotation('fastest_completion_time');
        return $this;
    }

    /**
     * Annotation completion time.
     *
     * @return self
     */
    public function annotate_slowest_completion_time(): self {
        $this->add_annotation('slowest_completion_time');
        return $this;
    }

    /**
     * Annotation completed count.
     *
     * This should not factor in any notion of success or failure, it
     * should just count the number of instances that have been completed.
     *
     * @return self
     */
    public function annotate_completed_count(): self {
        $this->add_annotation('completed_count');
        return $this;
    }

    /**
     * Annotation not completed count.
     *
     * This counts all instances that have not yet been completed.
     *
     * @return self
     */
    public function annotate_not_completed_count(): self {
        $this->add_annotation('not_completed_count');
        return $this;
    }

    /**
     * Annotation completion rate.
     *
     * As for the number of completed, this is the number of completed instances vs the number
     * of instances. This does not factor in any notion of success, or properly completed. You
     * will have to use another metric for challenges, else it will not be representative.
     *
     * @return self
     */
    public function annotate_completion_rate(): self {
        $this->add_annotation('completion_rate');
        return $this;
    }

    /**
     * Annotation in progress average rate.
     *
     * This represents the average progress rate of all instances that are ongoing.
     *
     * @return self
     */
    public function annotate_inprogress_average_rate(): self {
        $this->add_annotation('inprogress_average_rate');
        return $this;
    }

    /**
     * Annotation in progress partial count.
     *
     * The number of ongoing instances where the progression is greater than 0%, but less than 100%.
     *
     * @return self
     */
    public function annotate_inprogress_partial_count(): self {
        $this->add_annotation('inprogress_partial_count');
        return $this;
    }

    /**
     * Annotation in progress zero count.
     *
     * The number of ongoing instances where the progress is 0.
     *
     * @return self
     */
    public function annotate_inprogress_zero_count(): self {
        $this->add_annotation('inprogress_zero_count');
        return $this;
    }

    /**
     * Annotation recruit count.
     *
     * @return self
     */
    public function annotate_recruit_count(): self {
        $this->add_annotation('recruit_count');
        return $this;
    }

    /**
     * Annotation the success rate.
     *
     * At the moment, this is only applicable to challenges in order to determine the number of
     * instances that were successfully completed before they reached their deadline.
     *
     * @return self
     */
    public function annotate_success_rate(): self {
        $this->add_annotation('success_rate');
        return $this;
    }

    /**
     * Filter only active missions.
     *
     * What defines an active mission can evolve over time and cover a variety of mission::STATE_* constants.
     * To set a query to strictly get the mission::STATE_ACTIVE missions, use self::set_state() instead.
     *
     * @return self
     */
    public function filter_active(): self {
        $this->set_condition('active', true);
        return $this;
    }

    /**
     * Filter types.
     *
     * @param string[] $types
     * @return self
     */
    public function filter_types(array $types): self {
        $this->set_condition('types', $types);
        return $this;
    }

    /**
     * Filter has inprogress instances.
     *
     * @return self
     */
    public function filter_has_completed(): self {
        $this->set_condition('has_completed', true);
        return $this;
    }

    /**
     * Filter has inprogress instances.
     *
     * @return self
     */
    public function filter_has_inprogress(): self {
        $this->set_condition('has_inprogress', true);
        return $this;
    }

    /**
     * Filter has recruits.
     *
     * @return self
     */
    public function filter_has_recruits(): self {
        $this->set_condition('has_recruits', true);
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
     * This is used to filter out annotations and other instance data.
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
     * Set the repeat count.
     *
     * @param int|null $repeatcount The repeat count (mission::REPEAT_*).
     * @return self
     */
    public function set_repeat_count(?int $repeatcount = null): self {
        if ($repeatcount === null) {
            $this->unset_condition('repeatcount');
            return $this;
        }

        $this->set_condition('repeatcount', $repeatcount);
        return $this;
    }

    /**
     * Set the mission state.
     *
     * @param int|null $state The mission state (mission::STATE_*).
     * @return self
     */
    public function set_state(?int $state = null): self {
        if ($state === null) {
            $this->unset_condition('state');
            return $this;
        }

        $this->set_condition('state', $state);
        return $this;
    }

    /**
     * Set the mission type.
     *
     * @param string|int|null $type The mission type (And model\mission::TYPE_* for backwards compat).
     * @return self
     */
    public function set_type($type = null): self {
        if ($type === null) {
            $this->unset_condition('type');
            return $this;
        }

        if (is_int($type)) {
            debugging('Using int for type is deprecated, use the string representation instead.', DEBUG_DEVELOPER);
            $type = mission_model::convert_internal_type($type);
        }

        $this->set_condition('type', $type);
        return $this;
    }

}
