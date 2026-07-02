<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

namespace local_xp\local\rule;

use block_xp\local\rule\instance;
use block_xp\local\rulefilter\handler;
use block_xp\local\ruletype\resolver;

/**
 * Rule descriptor.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_descriptor {

    /** @var resolver The rule type resolver. */
    protected $ruletyperesolver;
    /** @var handler The rule filter handler. */
    protected $rulefilterhandler;

    /**
     * Constructor.
     *
     * @param resolver $ruletyperesolver The rule type resolver.
     * @param handler $rulefilterhandler The rule filter handler.
     */
    public function __construct(resolver $ruletyperesolver, handler $rulefilterhandler) {
        $this->ruletyperesolver = $ruletyperesolver;
        $this->rulefilterhandler = $rulefilterhandler;
    }

    /**
     * Get the conditions.
     *
     * @param instance $instance The instance.
     * @return string
     */
    public function get_conditions(instance $instance): string {
        $filter = $this->rulefilterhandler->get_filter($instance->get_filter_name());
        if (!$filter) {
            return get_string('unknownconditiona', 'block_xp', $instance->get_filter_name());
        }

        $effectivectx = $instance->get_child_context() ?? $instance->get_context();
        $name = (string) $filter->get_display_name();
        $label = $filter->get_label_for_config($instance->get_filter_config(), $effectivectx);
        if ($name !== $label) {
            return $name . ' (' . $label . ')';
        }
        return $name;
    }

    /**
     * Get the description.
     *
     * @param instance $instance
     * @return string
     */
    public function get_description(instance $instance): string {
        return "+{$instance->get_points()} " . $this->get_conditions($instance);
    }

    /**
     * Get the full description.
     *
     * @param instance $instance
     * @return string
     */
    public function get_full_description(instance $instance): string {
        return $this->get_type_name($instance) . ': ' . $this->get_description($instance);
    }

    /**
     * Get the type name.
     *
     * @param instance $instance The instance.
     * @return string
     */
    public function get_type_name(instance $instance): string {
        $type = $this->ruletyperesolver->get_type($instance->get_type_name());
        return $type ? (string) $type->get_display_name() : get_string('unknowntypea', 'block_xp', $instance->get_type_name());
    }
}
