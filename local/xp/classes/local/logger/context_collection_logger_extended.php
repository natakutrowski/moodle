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

namespace local_xp\local\logger;

use block_xp\di;
use DateTime;
use block_xp\local\factory\reason_from_log_entry_factory;
use block_xp\local\reason\reason;
use block_xp\local\ruletype\limit_spec;

/**
 * Collection logger.
 *
 * @package    local_xp
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_collection_logger_extended extends \block_xp\local\logger\context_collection_logger implements
    collection_counts_indicator,
    reason_collection_counts_indicator {

    /** @var reason_from_log_entry_factory */
    protected $reasonfactory;

    public function count_collections_since($userid, DateTime $since) {
        $conditions = [
            'contextid = :contextid',
            'timerecorded >= :timerecorded',
            'userid = :userid',
        ];
        $params = [
            'contextid' => $this->contextid,
            'userid' => $userid,
            'timerecorded' => $since->getTimestamp(),
        ];
        return $this->db->count_records_select($this->table, implode(' AND ', $conditions), $params);
    }

    /**
     * Count collections since a date, only for events.
     *
     * This is a convenient method, not from any interface, refrain from using it.
     *
     * @param int $userid The user ID.
     * @param DateTime $since The date.
     * @return int
     */
    public function count_collections_since_in_event_rules($userid, DateTime $since) {
        $conditions = [
            'contextid = :contextid',
            'timerecorded >= :timerecorded',
            'userid = :userid',
            'reason = :reason',
            'ruleid IS NULL',
        ];
        $params = [
            'contextid' => $this->contextid,
            'userid' => $userid,
            'timerecorded' => $since->getTimestamp(),
            'reason' => 'event',
        ];
        return $this->db->count_records_select($this->table, implode(' AND ', $conditions), $params);
    }

    public function count_collections_with_reason_since($userid, reason $reason, DateTime $since) {
        $conditions = [
            'contextid = :contextid',
            'userid = :userid',
            'timerecorded >= :timerecorded',
        ];
        $params = [
            'contextid' => $this->contextid,
            'userid' => $userid,
            'timerecorded' => $since->getTimestamp(),
        ];

        [$reasonsql, $reasonparams] = $this->get_reason_filter_sql($reason);
        $conditions[] = $reasonsql;
        $params = array_merge($params, $reasonparams);

        return $this->db->count_records_select($this->table, implode(' AND ', $conditions), $params);
    }

    public function get_collected_points_since($userid, DateTime $since) {
        $conditions = [
            'contextid = :contextid',
            'timerecorded >= :timerecorded',
            'userid = :userid',
        ];
        $params = [
            'contextid' => $this->contextid,
            'userid' => $userid,
            'timerecorded' => $since->getTimestamp(),
        ];
        return $this->db->get_field_select($this->table, 'COALESCE(SUM(points), 0)', implode(' AND ', $conditions), $params);
    }

    /**
     * Get collections points since, only for events.
     *
     * This is a convenient method, not from any interface, refrain from using it.
     *
     * @param int $userid The user ID.
     * @param DateTime $since The date.
     * @return int
     */
    public function get_collected_points_since_in_event_rules($userid, DateTime $since) {
        $conditions = [
            'contextid = :contextid',
            'timerecorded >= :timerecorded',
            'userid = :userid',
            'reason = :reason',
            'ruleid IS NULL',
        ];
        $params = [
            'contextid' => $this->contextid,
            'userid' => $userid,
            'timerecorded' => $since->getTimestamp(),
            'reason' => 'event',
        ];
        return $this->db->get_field_select($this->table, 'COALESCE(SUM(points), 0)', implode(' AND ', $conditions), $params);
    }

    protected function get_limit_time_window_filter_sql(int $timewindow): array {
        if ($timewindow === limit_spec::WINDOW_DAILY) {
            $dt = di::get('clock')->now()->setTime(0, 0, 0, 0);
            return ['(timerecorded >= :limittimewindow)', ['limittimewindow' => $dt->getTimestamp()]];

        } else if ($timewindow === limit_spec::WINDOW_WEEKLY) {
            $dt = di::get('clock')->now()->modify('monday this week')->setTime(0, 0, 0, 0);
            return ['(timerecorded >= :limittimewindow)', ['limittimewindow' => $dt->getTimestamp()]];

        } else if ($timewindow === limit_spec::WINDOW_MONTHLY) {
            $dt = di::get('clock')->now()->modify('first day of this month')->setTime(0, 0, 0, 0);
            return ['(timerecorded >= :limittimewindow)', ['limittimewindow' => $dt->getTimestamp()]];
        }

        return parent::get_limit_time_window_filter_sql($timewindow);
    }

    public function get_points_collected_with_reason_since($userid, reason $reason, DateTime $since) {
        $conditions = [
            'contextid = :contextid',
            'userid = :userid',
            'timerecorded >= :timerecorded',
        ];
        $params = [
            'contextid' => $this->contextid,
            'userid' => $userid,
            'timerecorded' => $since->getTimestamp(),
        ];

        [$reasonsql, $reasonparams] = $this->get_reason_filter_sql($reason);
        $conditions[] = $reasonsql;
        $params = array_merge($params, $reasonparams);

        return $this->db->get_field_select(
            $this->table,
            'COALESCE(SUM(points), 0)',
            implode(' AND ', $conditions),
            $params
        );
    }

    /**
     * Has the reason ever happened, excluding from rules.
     *
     * This is a convenient method, not from any interface, refrain from using it.
     *
     * @param int $id The ID.
     * @param reason $reason The reason.
     * @param DateTime $since The date. This is using DateTime for historical reasons.
     * @return bool
     */
    public function has_reason_happened_since_excluding_from_rules($userid, reason $reason, DateTime $since) {
        $conditions = [
            'contextid = :contextid',
            'userid = :userid',
            'timerecorded >= :timerecorded',
            'ruleid IS NULL',
        ];
        $params = [
            'contextid' => $this->contextid,
            'userid' => $userid,
            'timerecorded' => $since->getTimestamp(),
        ];

        [$reasonsql, $reasonparams] = $this->get_reason_filter_sql($reason);
        $conditions[] = $reasonsql;
        $params = array_merge($params, $reasonparams);

        return $this->db->record_exists_select($this->table, implode(' AND ', $conditions), $params);
    }

    /**
     * Set the reason factory.
     *
     * @param reason_from_log_entry_factory $factory
     */
    public function set_reason_from_log_entry_factory(reason_from_log_entry_factory $factory) {
        $this->reasonfactory = $factory;
    }

}
