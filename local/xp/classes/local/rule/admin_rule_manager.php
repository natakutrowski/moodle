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
 * Admin rule manager.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_rule_manager extends \block_xp\local\rule\admin_rule_manager {

    /**
     * Delete an admin default rule.
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
     * Fetch a single admin rule record by ID with limit JOIN.
     *
     * @param int $ruleid The rule ID.
     * @return \stdClass|false
     */
    protected function fetch_record(int $ruleid) {
        $sql = "SELECT r.*,
                       lr.limitmax, lr.limitwindow, lr.repeatwindow, lr.repeatscope
                  FROM {block_xp_rule} r
             LEFT JOIN {local_xp_rule} lr ON lr.ruleid = r.id
                 WHERE r.id = :ruleid
                   AND r.contextid = 0";
        return $this->db->get_record_sql($sql, ['ruleid' => $ruleid], IGNORE_MISSING);
    }

    /**
     * Fetch the records.
     *
     * @return \stdClass[]
     */
    protected function fetch_records(): array {
        $sql = "SELECT r.*,
                       lr.limitmax, lr.limitwindow, lr.repeatwindow, lr.repeatscope
                  FROM {block_xp_rule} r
             LEFT JOIN {local_xp_rule} lr ON lr.ruleid = r.id
                 WHERE r.contextid = 0
                   AND r.childcontextid = 0
              ORDER BY r.id ASC";
        return $this->db->get_records_sql($sql);
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

    /**
     * Reset all worlds to defaults.
     *
     * @return void
     */
    public function reset_all_worlds_to_defaults(): void {
        $tx = $this->db->start_delegated_transaction();
        $this->db->execute("DELETE FROM {local_xp_rule}
                             WHERE ruleid
                                IN (SELECT id FROM {block_xp_rule} WHERE contextid > 0)");
        parent::reset_all_worlds_to_defaults();
        $tx->allow_commit();
    }
}
