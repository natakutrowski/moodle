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

use block_xp\di;
use block_xp\local\factory\reason_from_log_entry_factory;
use block_xp\local\iterator\map_recordset;
use block_xp\local\logger\collection_logger_query;
use block_xp\local\logger\collection_logger_with_query;
use block_xp\local\sql\limit;
use block_xp\local\utils\user_utils;
use context_system;
use DateTime;
use local_xp\local\rule\static_instance;
use moodle_database;
use stdClass;

/**
 * Global world collection logger.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class global_world_collection_logger implements collection_logger_with_query {
    /** @var moodle_database The DB. */
    protected $db;
    /** @var reason_from_log_entry_factory The reason factory. */
    protected $reasonfactory;
    /** @var array The base rule columns. */
    protected $rulecolumns = ['id', 'contextid', 'childcontextid', 'points', 'type', 'filter',
        'filtercourseid', 'filtercmid', 'filterint1', 'filterchar1'];
    /** @var array The XP+ rule columns. */
    protected $localrulecolumns = ['limitmax', 'limitwindow', 'repeatwindow', 'repeatscope'];

    /**
     * Constructor.
     */
    public function __construct() {
        $this->db = di::get('db');
        $this->reasonfactory = di::get('reason_from_log_entry_factory');
    }

    /**
     * List matching logs.
     *
     * @param collection_logger_query $query The query.
     * @param limit $limit The limit.
     * @return iterable<log_with_rule>
     */
    public function list(collection_logger_query $query, limit $limit) {
        $sql = $this->prepare_query($query);
        $recordset = $this->db->get_recordset_sql(
            "SELECT {$sql->fields} FROM {$sql->from} WHERE {$sql->where} ORDER BY {$sql->orderby}",
            $sql->params,
            $limit->get_offset(),
            $limit->get_count()
        );
        return new map_recordset($recordset, function ($record) {
            return $this->make_log_from_record($record);
        });
    }

    /**
     * Count matching logs.
     *
     * @param collection_logger_query $query The query.
     * @return int
     */
    public function count(collection_logger_query $query): int {
        $sql = $this->prepare_query($query);
        return (int) $this->db->count_records_sql(
            "SELECT COUNT(1) FROM {$sql->from} WHERE {$sql->where}",
            $sql->params
        );
    }

    /**
     * Make a log from a record.
     *
     * @param stdClass $record The row.
     * @return log_with_rule
     */
    protected function make_log_from_record(stdClass $record): log_with_rule {
        $user = user_utils::unalias_picture_fields($record, 'userid');
        foreach (['idnumber', 'username', 'suspended'] as $field) {
            if (property_exists($record, $field)) {
                $user->{$field} = $record->{$field};
            }
        }
        $reason = $this->reasonfactory->get_reason_from_log_entry($record->reason, $record);

        $rule = null;
        if (!empty($record->rule_id)) {
            $rulerecord = new stdClass();
            foreach (array_merge($this->rulecolumns, $this->localrulecolumns) as $column) {
                $rulerecord->{$column} = $record->{'rule_' . $column};
            }
            $rule = new static_instance($rulerecord);
        }
        return new static_log($record, $user, $reason, $rule);
    }

    /**
     * Prepare the SQL parts shared by listing and counting logs.
     *
     * Includes logs from courses contributing to global totals, using the same
     * user and course exclusions as the global state store.
     *
     * @param collection_logger_query $query The query.
     * @return stdClass SQL fields, from, where, params and orderby.
     */
    protected function prepare_query(collection_logger_query $query): stdClass {
        $sql = new stdClass();
        $sql->fields = 'x.*, ' . user_utils::picture_fields('u', 'userid') . ', u.idnumber, u.username, u.suspended';
        $sql->from = "{block_xp_logs} x
                      JOIN {user} u ON u.id = x.userid
                      JOIN {context} logctx ON logctx.id = x.contextid AND logctx.contextlevel = :contextlevel
                      JOIN {local_xp_source} src
                        ON src.sourcecontextid = logctx.id AND src.contextid = :worldcontextid
                 LEFT JOIN {block_xp_rule} r ON r.id = x.ruleid
                 LEFT JOIN {local_xp_rule} lr ON lr.ruleid = r.id
                 LEFT JOIN {context} ctx ON ctx.id = r.contextid
                 LEFT JOIN {context} childctx ON childctx.id = r.childcontextid";
        $sql->where = 'u.deleted = 0';
        $sql->params = [
            'contextlevel' => CONTEXT_COURSE,
            'worldcontextid' => context_system::instance()->id,
        ];

        $sql->fields .= ', ' . implode(', ', array_map(function ($col) {
            return 'r.' . $col . ' AS rule_' . $col;
        }, $this->rulecolumns));
        $sql->fields .= ', ' . implode(', ', array_map(function ($col) {
            return 'lr.' . $col . ' AS rule_' . $col;
        }, $this->localrulecolumns));
        $sql->fields .= ', ctx.id AS rule_contextid, childctx.id AS rule_childcontextid';

        if ($query->has_condition('userid')) {
            $sql->where .= ' AND x.userid = :userid';
            $sql->params['userid'] = (int) $query->get_condition('userid');
        }
        if ($query->has_condition('ruleid')) {
            $sql->where .= ' AND x.ruleid = :ruleid';
            $sql->params['ruleid'] = (int) $query->get_condition('ruleid');
        }
        if ($query->has_condition('ruleids')) {
            [$rulesql, $ruleparams] = $this->db->get_in_or_equal($query->get_condition('ruleids'), SQL_PARAMS_NAMED, 'ruleid');
            $sql->where .= " AND x.ruleid $rulesql";
            $sql->params = array_merge($sql->params, $ruleparams);
        }
        if ($query->has_condition('timefrom')) {
            $sql->where .= ' AND x.timerecorded >= :timefrom';
            $sql->params['timefrom'] = $query->get_condition('timefrom')->getTimestamp();
        }
        if ($query->has_condition('timeto')) {
            $sql->where .= ' AND x.timerecorded <= :timeto';
            $sql->params['timeto'] = $query->get_condition('timeto')->getTimestamp();
        }
        if ($query->has_condition('userfilter')) {
            [$filtersql, $filterparams] = $query->get_condition('userfilter')->get_sql('u.id');
            $sql->where .= " AND ($filtersql)";
            $sql->params = array_merge($sql->params, $filterparams);
        }
        if ($query->has_condition('term')) {
            [$termsql, $termparams] = user_utils::get_filter_user_by_term_sql($query->get_condition('term'));
            $sql->where .= " AND ($termsql)";
            $sql->params = array_merge($sql->params, $termparams);
        }

        $orderbyaliases = [
            'timerecorded' => 'x.timerecorded',
            'points' => 'x.points',
            'firstname' => 'u.firstname',
            'lastname' => 'u.lastname',
            'firstnamephonetic' => 'u.firstnamephonetic',
            'lastnamephonetic' => 'u.lastnamephonetic',
            'middlename' => 'u.middlename',
            'alternatename' => 'u.alternatename',
        ];
        $orderby = [];
        foreach ($query->get_order_by() ?: [['timerecorded', SORT_DESC]] as [$key, $direction]) {
            if (!isset($orderbyaliases[$key])) {
                continue;
            }
            $orderby[$key] = $this->db->sql_order_by_null(
                $orderbyaliases[$key],
                $direction === SORT_ASC ? SORT_ASC : SORT_DESC
            );
        }
        $orderby[] = 'x.id DESC';
        $sql->orderby = implode(', ', $orderby);

        return $sql;
    }

    /**
     * Delete older than.
     *
     * @param DateTime $dt
     */
    public function delete_older_than(DateTime $dt) {
        return;
    }

    /**
     * Log.
     *
     * @param int $id
     * @param int $points
     * @param string $signature
     * @param DateTime|null $time
     */
    public function log($id, $points, $signature, ?DateTime $time = null) {
        return;
    }

    /**
     * Reset.
     */
    public function reset() {
        return;
    }
}
