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

/**
 * World rule manager.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class world_rule_manager extends \block_xp\local\rule\world_rule_manager {

    /**
     * Delete a rule.
     *
     * @param int $ruleid The rule ID.
     */
    public function delete_rule(int $ruleid): void {
        $tx = $this->db->start_delegated_transaction();
        $this->db->delete_records('local_xp_rule', ['ruleid' => $ruleid]);
        parent::delete_rule($ruleid);
        $tx->allow_commit();
    }

    /**
     * Delete all rules.
     *
     * @return void
     */
    protected function delete_all_rules(): void {
        $storecontext = $this->world->get_context();
        $tx = $this->db->start_delegated_transaction();
        $this->db->execute("DELETE FROM {local_xp_rule}
                             WHERE ruleid
                                IN (SELECT id FROM {block_xp_rule} WHERE contextid = :contextid)", [
            'contextid' => $storecontext->id,
        ]);
        parent::delete_all_rules($storecontext);
        $tx->allow_commit();
    }

    /**
     * Fetch a single rule record.
     *
     * @param int $ruleid The rule ID.
     * @return \stdClass|false
     */
    protected function fetch_record(int $ruleid) {
        $sql = "SELECT r.*,
                       lr.limitmax, lr.limitwindow, lr.repeatwindow, lr.repeatscope
                  FROM {block_xp_rule} r
                  JOIN {context} ctx ON ctx.id = r.contextid
             LEFT JOIN {local_xp_rule} lr ON lr.ruleid = r.id
             LEFT JOIN {context} childctx ON childctx.id = r.childcontextid
                 WHERE r.id = :ruleid
                   AND r.contextid = :contextid";
        $params = [
            'ruleid' => $ruleid,
            'contextid' => $this->world->get_context()->id,
        ];
        return $this->db->get_record_sql($sql, $params, IGNORE_MISSING);
    }

    /**
     * Fetch rules in context.
     *
     * @param \context $storecontext The store context.
     * @param \context|null $childcontext The child context.
     * @return instance[]
     */
    protected function fetch_records_in_context(\context $storecontext, ?\context $childcontext = null): array {
        $sql = "SELECT r.*,
                       lr.limitmax, lr.limitwindow, lr.repeatwindow, lr.repeatscope
                  FROM {block_xp_rule} r
                  JOIN {context} ctx ON ctx.id = r.contextid
             LEFT JOIN {local_xp_rule} lr ON lr.ruleid = r.id
             LEFT JOIN {context} childctx ON childctx.id = r.childcontextid
                 WHERE r.contextid = :contextid
                   AND r.childcontextid = :childcontextid
              ORDER BY r.id";
        $params = [
            'contextid' => $storecontext->id,
            'childcontextid' => $this->normalise_childcontext_id($childcontext),
        ];
        return $this->db->get_records_sql($sql, $params);
    }

    /**
     * Insert a record.
     *
     * @param \stdClass $record The record.
     * @return int
     */
    protected function insert_record(\stdClass $record): int {
        $tx = $this->db->start_delegated_transaction();
        $id = parent::insert_record($record);
        $this->db->insert_record('local_xp_rule', (object) [
            'ruleid' => $id,
            'limitmax' => $record->limitmax,
            'limitwindow' => $record->limitwindow,
            'repeatwindow' => $record->repeatwindow,
            'repeatscope' => $record->repeatscope,
        ]);
        $tx->allow_commit();
        return $id;
    }

    /**
     * Make an instance.
     *
     * @param \stdClass $record The record.
     * @return instance
     */
    protected function make_instance(\stdClass $record): instance {
        return new static_instance($record);
    }
}
