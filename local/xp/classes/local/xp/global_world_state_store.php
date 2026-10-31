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

namespace local_xp\local\xp;

use block_xp\di;
use block_xp\local\iterator\map_recordset;
use block_xp\local\sql\limit;
use block_xp\local\utils\user_utils;
use block_xp\local\xp\levels_info;
use block_xp\local\xp\state_store_query;
use block_xp\local\xp\state_store_with_presence;
use block_xp\local\xp\state_store_with_query;
use Closure;
use context_helper;
use context_system;
use moodle_database;
use stdClass;

/**
 * Global world state store.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class global_world_state_store implements state_store_with_presence, state_store_with_query {
    /** @var moodle_database The DB. */
    protected $db;
    /** @var levels_info The levels info. */
    protected $levelsinfo;
    /** @var Closure|null The user state factory. */
    protected $userstatefactory;

    /**
     * Constructor.
     *
     * @param levels_info $levelsinfo The levels info.
     * @param Closure|null $userstatefactory The user state factory, if any.
     */
    public function __construct(levels_info $levelsinfo, $userstatefactory = null) {
        $this->db = di::get('db');
        $this->levelsinfo = $levelsinfo;
        $this->userstatefactory = $userstatefactory;
    }

    /**
     * Count matching states.
     *
     * @param state_store_query $query The query.
     * @return int
     */
    public function count(state_store_query $query): int {
        $sql = $this->prepare_query($query);
        return (int) $this->db->count_records_sql(
            "SELECT COUNT(1) FROM {$sql->from} WHERE {$sql->where}",
            $sql->params
        );
    }

    /**
     * Get a state.
     *
     * @param int $id The user ID.
     * @return user_state
     */
    public function get_state($id) {
        $userfields = user_utils::picture_fields('u', 'userid');
        $contextfields = context_helper::get_preload_record_columns_sql('ctx');

        $sql = "SELECT u.id, x.xp, x.sourcecount, $userfields, $contextfields
                  FROM {user} u
                  JOIN {context} ctx
                    ON ctx.instanceid = u.id
                   AND ctx.contextlevel = :contextlevel
             LEFT JOIN (SELECT x2.userid, SUM(x2.xp) AS xp, COUNT(x2.id) AS sourcecount
                          FROM {block_xp} x2
                          JOIN {user} u2 ON u2.id = x2.userid
                          JOIN {context} cctx
                            ON cctx.instanceid = x2.courseid AND cctx.contextlevel = :coursecontextlevel
                          JOIN {local_xp_source} src
                            ON src.sourcecontextid = cctx.id AND src.contextid = :worldcontextid
                         WHERE u2.deleted = 0
                           AND x2.userid = :xpuserid
                      GROUP BY x2.userid) x
                    ON x.userid = u.id
                 WHERE u.id = :userid";

        $params = [
            'contextlevel' => CONTEXT_USER,
            'coursecontextlevel' => CONTEXT_COURSE,
            'worldcontextid' => context_system::instance()->id,
            'xpuserid' => $id,
            'userid' => $id,
        ];

        $record = $this->db->get_record_sql($sql, $params, MUST_EXIST);
        return $this->make_state_from_record($record);
    }

    /**
     * Get supported sort fields and their SQL expressions.
     *
     * @return string[] SQL expressions keyed by query field name.
     */
    protected function get_supported_query_sort_fields(): array {
        return [
            'xp' => 'x.xp',
            'id' => 'u.id',
            'firstname' => 'u.firstname',
            'lastname' => 'u.lastname',
            'firstnamephonetic' => 'u.firstnamephonetic',
            'lastnamephonetic' => 'u.lastnamephonetic',
            'middlename' => 'u.middlename',
            'alternatename' => 'u.alternatename',
        ];
    }

    /**
     * List matching states.
     *
     * @param state_store_query $query The query.
     * @param limit $limit The limit.
     * @return iterable<user_state>
     */
    public function list(state_store_query $query, limit $limit) {
        $sql = $this->prepare_query($query);
        $recordset = $this->db->get_recordset_sql(
            "SELECT {$sql->fields} FROM {$sql->from} WHERE {$sql->where} ORDER BY {$sql->orderby}",
            $sql->params,
            $limit->get_offset(),
            $limit->get_count()
        );
        return new map_recordset($recordset, function ($record) {
            return $this->make_state_from_record($record);
        });
    }

    /**
     * Make a state from a record.
     *
     * @param stdClass $record The row.
     * @return user_state
     */
    protected function make_state_from_record(stdClass $record) {
        $user = user_utils::unalias_picture_fields($record, 'userid');
        foreach (['idnumber', 'username', 'suspended'] as $field) {
            if (property_exists($record, $field)) {
                $user->{$field} = $record->{$field};
            }
        }
        context_helper::preload_from_record($record);
        $xp = !empty($record->xp) ? $record->xp : 0;

        if (!empty($this->userstatefactory)) {
            $cb = $this->userstatefactory;
            $state = $cb($user, $xp);
        } else {
            $state = new user_state($user, $xp, $this->levelsinfo, SITEID);
        }

        if ($state instanceof user_state) {
            // Presence reflects contributing course records, including a zero total.
            $state->set_present(isset($record->xp));
            $state->set_leaderboard_participation_flag(
                isset($record->ladderparticipation) ? (int) $record->ladderparticipation : null
            );
            $state->set_source_count((int) ($record->sourcecount ?? 0));
        }
        return $state;
    }

    /**
     * Prepare the SQL parts shared by listing and counting states.
     *
     * Only includes users with XP records in selected source courses,
     * using the same user and course exclusions as get_state().
     *
     * @param state_store_query $query The query.
     * @return stdClass SQL fields, from, where, params and orderby.
     */
    protected function prepare_query(state_store_query $query): stdClass {
        $sql = new stdClass();
        $sql->fields = 'u.id, x.xp, x.sourcecount, ' . user_utils::picture_fields('u', 'userid') .
            ', u.idnumber, u.username, u.suspended, uf.ladderparticipation, ' .
            context_helper::get_preload_record_columns_sql('ctx');
        $sql->from = "{user} u
                      JOIN (SELECT x2.userid, SUM(x2.xp) AS xp, COUNT(x2.id) AS sourcecount
                              FROM {block_xp} x2
                              JOIN {user} u2 ON u2.id = x2.userid
                              JOIN {context} cctx
                                ON cctx.instanceid = x2.courseid AND cctx.contextlevel = :coursecontextlevel
                              JOIN {local_xp_source} src
                                ON src.sourcecontextid = cctx.id AND src.contextid = :worldcontextid
                             WHERE u2.deleted = 0
                          GROUP BY x2.userid) x
                        ON x.userid = u.id
                      JOIN {context} ctx
                        ON ctx.instanceid = u.id
                       AND ctx.contextlevel = :contextlevel
                 LEFT JOIN {local_xp_user_flag} uf
                        ON uf.userid = u.id
                       AND uf.contextid = :ufcontextid";
        $sql->where = '1=1';
        $sql->params = [
            'contextlevel' => CONTEXT_USER,
            'coursecontextlevel' => CONTEXT_COURSE,
            'worldcontextid' => context_system::instance()->id,
            'ufcontextid' => context_system::instance()->id,
        ];

        if ($query->has_condition('userfilter')) {
            [$filtersql, $filterparams] = $query->get_condition('userfilter')->get_sql('u.id');
            $sql->where .= " AND ($filtersql)";
            $sql->params = array_merge($sql->params, $filterparams);
        }
        if ($query->has_condition('term')) {
            [$termsql, $termparams] = user_utils::get_filter_user_by_term_sql(
                $query->get_condition('term'),
                $query->get_allowed_identity_fields()
            );
            $sql->where .= " AND ($termsql)";
            $sql->params = array_merge($sql->params, $termparams);
        }

        $orderbyaliases = $this->get_supported_query_sort_fields();
        $orderby = [];
        foreach ($query->get_order_by() ?: [['xp', SORT_DESC]] as [$field, $direction]) {
            if (!isset($orderbyaliases[$field])) {
                continue;
            }
            $orderby[$field] = $this->db->sql_order_by_null(
                $orderbyaliases[$field],
                $direction === SORT_ASC ? SORT_ASC : SORT_DESC
            );
        }
        if (!isset($orderby['id'])) {
            $orderby['id'] = 'u.id ASC';
        }
        $sql->orderby = implode(', ', $orderby);

        return $sql;
    }

    /**
     * Whether the user has a state contributing to their global total.
     *
     * @param int $id The user ID.
     * @return bool
     */
    public function has($id) {
        return $this->db->record_exists_sql(
            "SELECT 1
               FROM {block_xp} x
               JOIN {user} u ON u.id = x.userid
               JOIN {context} cctx
                 ON cctx.instanceid = x.courseid AND cctx.contextlevel = :coursecontextlevel
               JOIN {local_xp_source} src
                 ON src.sourcecontextid = cctx.id AND src.contextid = :worldcontextid
              WHERE x.userid = :userid
                AND u.deleted = 0",
            [
                'userid' => $id,
                'coursecontextlevel' => CONTEXT_COURSE,
                'worldcontextid' => context_system::instance()->id,
            ]
        );
    }

    /**
     * Increase.
     *
     * @param int $id
     * @param int $amount
     */
    public function increase($id, $amount) {
        return;
    }

    /**
     * Reset.
     */
    public function reset() {
        return;
    }

    /**
     * Set.
     *
     * @param int $id
     * @param int $amount
     */
    public function set($id, $amount) {
        return;
    }
}
