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

use context;

/**
 * Query.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class query_base implements query {

    /** @var context The acting context. */
    private $actingcontext;
    /** @var array[] Array of annotations. */
    private $annotations = [];
    /** @var array The conditions. */
    private $conditions = [];
    /** @var array[] Array of order by and direction. */
    private $orderby = [];

    /**
     * Constructor.
     *
     * @param context $context The acting context, for permissions and visibility checks, etc.
     */
    public function __construct(context $context) {
        $this->actingcontext = $context;
    }

    /**
     * Add an annotation.
     *
     * @param string $name The annotation name.
     * @return self
     */
    final protected function add_annotation($name): self {
        $this->annotations[$name] = $name;
        return $this;
    }

    /**
     * Append order.
     *
     * The first call to this sets the most significant field.
     *
     * @param string $name The order name.
     * @param int $dir The constant SORT_ASC or SORT_DESC.
     * @return self
     */
    final protected function append_order_by($name, $dir = SORT_ASC): self {
        $entry = [$name, $dir];
        $this->orderby = array_merge($this->orderby, [$entry]);
        return $this;
    }

    /**
     * Get the acting context.
     *
     * @return context The acting context.
     */
    final public function get_acting_context(): context {
        return $this->actingcontext;
    }

    /**
     * Get the annotations.
     *
     * @return array
     */
    final public function get_annotations(): array {
        return $this->annotations;
    }

    /**
     * Get a condition.
     *
     * @param string $name The condition name.
     * @return mixed
     */
    final public function get_condition(string $name) {
        return $this->conditions[$name];
    }

    /**
     * The conditions.
     *
     * @return array Where the keys are the condition names.
     */
    final public function get_conditions(): array {
        return $this->conditions;
    }

    /**
     * The order by.
     *
     * @return array Or arrays with order by and direction.
     */
    final public function get_order_by(): array {
        return $this->orderby;
    }

    /**
     * Whether we have a annotation.
     *
     * @param string $name The annotation name.
     * @return bool
     */
    final public function has_annotation(string $name): bool {
        return array_key_exists($name, $this->annotations);
    }

    /**
     * Whether we have a condition.
     *
     * @param string $name The condition name.
     * @return bool
     */
    final public function has_condition(string $name): bool {
        return array_key_exists($name, $this->conditions);
    }

    /**
     * Reset the current order by.
     *
     * @return self
     */
    final public function reset_order_by(): self {
        $this->orderby = [];
        return $this;
    }

    /**
     * Unset a condition.
     *
     * @param string $name The condition name.
     * @param string|int|null $value The condition value.
     */
    final protected function set_condition($name, $value) {
        $this->conditions[$name] = $value;
    }

    /**
     * Unset a condition.
     *
     * @param string $name The condition name.
     */
    final protected function unset_condition($name) {
        unset($this->conditions[$name]);
    }

}
