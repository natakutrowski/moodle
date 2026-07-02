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
 * Dictator.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated Since XP 20, the dictatorship was overthrown.
 */
class dictator extends \block_xp\local\rule\the_dictator {

    /**
     * Fetch rules in context.
     *
     * @param \context $storecontext The context.
     * @param \context|null $childcontext The child context.
     * @return instance[]
     */
    protected function fetch_rules_in_context(\context $storecontext, ?\context $childcontext = null) {
        $sql = "SELECT r.*,
                       lr.limitmax, lr.limitwindow, lr.repeatwindow, lr.repeatscope,
                       ctx.contextlevel AS contextlevel, childctx.contextlevel AS childcontextlevel
                  FROM {block_xp_rule} r
                  JOIN {context} ctx ON ctx.id = r.contextid
             LEFT JOIN {local_xp_rule} lr ON lr.ruleid = r.id
             LEFT JOIN {context} childctx ON childctx.id = r.childcontextid
                 WHERE r.contextid = :contextid
                   AND r.childcontextid = :childcontextid
              ORDER BY r.id";
        $params = [
            'contextid' => $storecontext->id,
            'childcontextid' => $this->normalise_childcontext_id($storecontext, $childcontext),
        ];
        $records = $this->db->get_records_sql($sql, $params);

        return array_values(array_map(function ($record) {
            return new static_instance($record);
        }, $records));
    }

}
