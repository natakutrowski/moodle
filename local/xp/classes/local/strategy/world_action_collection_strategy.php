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

namespace local_xp\local\strategy;

use block_xp\local\action\action;
use block_xp\local\logger\reason_limit_indicator;
use block_xp\local\logger\ruletype_occurrence_indicator;
use block_xp\local\rule\instance;
use block_xp\local\ruletype\limit_spec;
use block_xp\local\ruletype\ruletype;
use block_xp\local\ruletype\ruletype_with_limit;
use local_xp\local\rule\rule_with_limit;
use local_xp\local\ruletype\cm_completion;
use local_xp\local\ruletype\course_completion;
use local_xp\local\ruletype\section_completion;

/**
 * World action collection strategy.
 *
 * @package    local_xp
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class world_action_collection_strategy extends \block_xp\local\strategy\world_action_collection_strategy {

    /**
     * Get the rule limits.
     *
     * @param instance $rule The rule.
     * @return (?\block_xp\local\ruletype\limit_spec)[]
     */
    protected function get_rule_limits(instance $rule): array {
        $type = $this->ruletyperesolver->get_type($rule->get_type_name());
        $defaultlimit = $type instanceof ruletype_with_limit ? $type->get_default_limit() : null;
        $defaultrepeatlimit = $type instanceof ruletype_with_limit ? $type->get_default_repeat_limit() : null;

        $limit = $rule instanceof rule_with_limit ? $rule->get_limit() : null;
        $repeatlimit = $rule instanceof rule_with_limit ? $rule->get_repeat_limit() : null;

        return [
            $limit ?? $defaultlimit,
            $repeatlimit ?? $defaultrepeatlimit,
        ];
    }

    /**
     * Whether the type limit is reached.
     *
     * @param ruletype $type The type.
     * @return bool
     */
    protected function is_type_limit_reached(ruletype $type, action $action): bool {
        if (parent::is_type_limit_reached($type, $action)) {
            return true;
        }

        $iscompletion = ($type instanceof cm_completion
            || $type instanceof section_completion || $type instanceof course_completion);

        if ($iscompletion) {
            if (!$this->logger instanceof ruletype_occurrence_indicator) {
                debugging('The logger is not a reason limit indicator.', DEBUG_DEVELOPER);
                return true;
            }

            $reason = $type->make_reason($action);
            $limit = new limit_spec(1, limit_spec::WINDOW_NONE, limit_spec::SCOPE_ENV | limit_spec::SCOPE_OBJECT);
            if ($this->logger->is_ruletype_reason_limit_reached($action->get_user_id(), $type, $reason, $limit)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalise child context.
     *
     * @param \context $context The context.
     * @return \context|null
     */
    protected function normalise_child_context(\context $context): ?\context {
        $worldcontext = $this->world->get_context();
        /** @var \context|null $childcontext */
        $childcontext = $context->get_course_context(false) ?: null;
        if (!$childcontext || !$worldcontext->is_parent_of($childcontext, false)) {
            return null;
        }
        return $childcontext;
    }
}
