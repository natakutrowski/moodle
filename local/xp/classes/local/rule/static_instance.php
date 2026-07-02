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

use block_xp\local\ruletype\limit_spec;

/**
 * Rule.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class static_instance extends \block_xp\local\rule\static_instance implements rule_with_limit {

    /**
     * Get the limit.
     *
     * @return limit_spec|null When null, it means unset, use defaults.
     */
    public function get_limit(): ?limit_spec {
        $max = $this->record->limitmax ?? null;
        if ($max === null) {
            // No explicit value; plugin defaults apply.
            return null;
        }

        // Values from the database are string-typed.
        $max = (int) $max;
        $window = $this->record->limitwindow ?? limit_spec::WINDOW_NONE;
        return new limit_spec($max, (int) $window);
    }

    /**
     * Get the repeat limit.
     *
     * Note that a repetition scope of SCOPE_NONE means that it does not apply. That is because
     * setting a repetition of NONE is essentially saying that the overall limit is 1 because
     * anything would be considered a duplicate. As such, SCOPE_NONE is considered to be
     * the value that defines no reptition limit. If we need to change this in the future,
     * we can either introduce `repeatmax` to set it to 1. We could also imagine adding
     * SCOPE_ANY but that probably won't work because SCOPE_NONE is already considered to
     * be mean 'any scope field values'.
     *
     * @return limit_spec|null When null, it means unset, use defaults.
     */
    public function get_repeat_limit(): ?limit_spec {
        $scope = $this->record->repeatscope ?? null;
        if ($scope === null) {
            // No explicit value; plugin defaults apply.
            return null;
        }

        // Values from the database are string-typed.
        $scope = (int) $scope;
        if ($scope === limit_spec::SCOPE_NONE) {
            return new limit_spec(0); // Unlimited.
        }

        $window = $this->record->repeatwindow ?? limit_spec::WINDOW_NONE;
        return new limit_spec(1, (int) $window, $scope);
    }

}
