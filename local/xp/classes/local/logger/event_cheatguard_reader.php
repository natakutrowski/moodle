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

/**
 * Cheatguard reader.
 *
 * @package    local_xp
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\logger;

use DateTime;
use block_xp\local\logger\reason_occurrence_indicator;
use block_xp\local\reason\reason;

/**
 * Cheatguard reader.
 *
 * @package    local_xp
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_cheatguard_reader implements
    collection_counts_indicator,
    reason_collection_counts_indicator,
    reason_occurrence_indicator {

    /** @var context_collection_logger_extended */
    protected $logger;

    /**
     * Constructor.
     *
     * @param context_collection_logger_extended $logger The new logger.
     */
    public function __construct(context_collection_logger_extended $logger) {
        $this->logger = $logger;
    }

    public function count_collections_since($id, DateTime $since) {
        // Only count from the event rules.
        return $this->logger->count_collections_since_in_event_rules($id, $since);
    }

    public function count_collections_with_reason_since($id, reason $reason, DateTime $since) {
        // Simply pipe this through, the reasons from event rules should not overlap due reason declaring a rule ID.
        return $this->logger->count_collections_with_reason_since($id, $reason, $since);
    }

    public function get_collected_points_since($id, DateTime $since) {
        // Only count from the event rules.
        return $this->logger->get_collected_points_since_in_event_rules($id, $since);
    }

    public function get_points_collected_with_reason_since($id, reason $reason, DateTime $since) {
        // Simply pipe this through, the reasons from event rules should not overlap due reason declaring a rule ID.
        return $this->logger->get_points_collected_with_reason_since($id, $reason, $since);
    }

    public function has_reason_happened_since($id, reason $reason, DateTime $since) {
        // This deserves a bit of an explaination. So, when we introduced the completion rules, they were logged
        // into the local_xp_log table, and were given a ruleid. The logs without a ruleid indicate that they were
        // created from the events rules. In XP+ 1.16, the cheat guard would not reward the completion of anything
        // if it has already taken place in either the events rules, or the completion rules. So we need to maintain
        // this behaviour. However, our cheat guard for the events rules checks whether an event is repeated within
        // a particular time frame. To ensure that there is no unexpected overlap between the logs from the action
        // rules and the event rules, we exclude the logs from the action rules. Checking whether the timestamp is
        // greated than 0 tells us that we're checking a time frame for repetition.
        if ($since->getTimestamp() > 0) {
            return $this->logger->has_reason_happened_since_excluding_from_rules($id, $reason, $since);
        }
        return $this->logger->has_reason_happened_since($id, $reason, $since);
    }

}
