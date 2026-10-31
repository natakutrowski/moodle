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
// See <https://levelup.plus>.

namespace local_xp\local\logger;

use block_xp\local\reason\reason;
use block_xp\local\rule\instance;
use stdClass;

/**
 * Static log.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class static_log extends \block_xp\local\logger\static_log implements log_with_rule {
    /** @var instance|null The rule. */
    protected ?instance $rule;

    /**
     * Constructor.
     *
     * @param stdClass $record The log record.
     * @param stdClass $user The user.
     * @param reason $reason The reason.
     * @param instance|null $rule The rule, when it still exists.
     */
    public function __construct(stdClass $record, stdClass $user, reason $reason, ?instance $rule) {
        parent::__construct($record, $user, $reason);
        $this->rule = $rule;
    }

    /**
     * Get the recorded rule ID.
     *
     * @return int|null
     */
    public function get_rule_id(): ?int {
        return !empty($this->record->ruleid) ? (int) $this->record->ruleid : null;
    }

    /**
     * Get the rule.
     *
     * @return instance|null
     */
    public function get_rule(): ?instance {
        return $this->rule;
    }
}
