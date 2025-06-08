<?php
// This file is part of Level Up Quest.
//
// Level Up Quest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up Quest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up Quest.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

namespace block_gearup\local\model;

use core\dml\sql_join;
use Generator;

/**
 * Reader.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class db_reader {

    /** @var sql_join[] An array of joins. */
    protected $joins = [];
    /** @var array[] Array of column names and direction. */
    protected $orderby = [];
    /** @var mixed[] A list of parameters. */
    protected $params = [];
    /** @var string[] A list select. */
    protected $select = [];
    /** @var string[] A list where conditions. */
    protected $wheres = [];

    /** @var string The table name. */
    protected $tablename;
    /** @var string The table alias. */
    protected $tablealias;

    /** @var bool Whether the query is prepared. */
    private $isprepared = false;
    /** @var bool Whether to debug the query. */
    private $debug = false;

    /**
     * Constructor.
     *
     * @param string $tablename The table name.
     * @param string $tablealias The table alias.
     */
    public function __construct($tablename, $tablealias) {
        $this->tablename = $tablename;
        $this->tablealias = $tablealias;
    }

    /**
     * Convert the record.
     *
     * @param object $record The database record.
     */
    protected function convert_record($record) {
        return $record;
    }

    /**
     * Count the number of records.
     *
     * @return int
     */
    public function count(): int {
        global $DB;
        $this->prepare_query();
        $sql = "SELECT COUNT(1)
                  FROM {{$this->tablename}} {$this->tablealias} {$this->get_joins()}
                 WHERE {$this->get_where()}";
        if ($this->debug) {
            $DB->set_debug(true);
        }
        $count = $DB->count_records_sql($sql, $this->get_params());
        if ($this->debug) {
            $DB->set_debug(false);
        }
        return $count;
    }

    /**
     * Add a join.
     *
     * @param string $localname The local name.
     * @param sql_join $join
     */
    final protected function add_join(string $localname, sql_join $join) {
        $this->joins[$localname] = $join;
    }

    /**
     * Add order by.
     *
     * @param string $localname The local name.
     * @param string $sql
     */
    final protected function add_order_by(string $localname, string $sql) {
        $this->orderby[$localname] = $sql;
    }

    /**
     * Add param.
     *
     * @param string $name The param name.
     * @param string $value
     */
    final protected function add_param(string $name, $value) {
        if (strlen($name) > 30) {
            // We can increase the limit here to 63 once we drop support for Moodle 4.1.
            throw new \coding_exception('Parameter name is limited to 30 chars for compatibility reasons.');
        }
        $this->params[$name] = $value;
    }

    /**
     * Add params.
     *
     * @param array $assoc Where the keys are param names, and values are values.
     */
    final protected function add_params($assoc) {
        foreach ($assoc as $localname => $value) {
            $this->add_param($localname, $value);
        }
    }

    /**
     * Add select.
     *
     * @param string $localname The local name.
     * @param string $sql
     */
    final protected function add_select(string $localname, string $sql) {
        $this->select[$localname] = $sql;
    }

    /**
     * Add where.
     *
     * @param string $localname The local name.
     * @param string $sql
     */
    final protected function add_where(string $localname, string $sql) {
        $this->wheres[$localname] = $sql;
    }

    /**
     * Checks if a matching record exists.
     *
     * @return bool
     */
    public function exists(): bool {
        global $DB;
        $this->prepare_query();
        $sql = "SELECT 1
                  FROM {{$this->tablename}} {$this->tablealias} {$this->get_joins()}
                 WHERE {$this->get_where()}";
        if ($this->debug) {
            $DB->set_debug(true);
        }
        $count = $DB->record_exists_sql($sql, $this->get_params());
        if ($this->debug) {
            $DB->set_debug(false);
        }
        return $count;
    }

    /**
     * Get the joins.
     *
     * @return string
     */
    final protected function get_joins() {
        return implode(' ', array_map(function($join) {
            return $join->joins;
        }, $this->joins));
    }

    /**
     * Get order by.
     *
     * @return string
     */
    final protected function get_order_by() {
        if (empty($this->orderby)) {
            return '';
        }
        return implode(', ', $this->orderby);
    }

    /**
     * Get the params.
     *
     * @return array
     */
    final protected function get_params() {
        $params = $this->params;
        foreach ($this->joins as $join) {
            if (!empty($join->wheres)) {
                $wheres[] = '(' . $join->wheres . ')';
            }
            $params += $join->params;
        }
        return $params;
    }

    /**
     * Get the select part.
     *
     * @return string
     */
    final protected function get_select() {
        return implode(', ', $this->select) ?: "1";
    }

    /**
     * Get where fragment.
     *
     * @return string
     */
    final protected function get_where() {
        $wheres = $this->wheres;
        $sql = '1=0';
        if (!empty($wheres)) {
            $sql = '(' . implode(') AND (', $wheres) . ')';
        }
        return $sql;
    }

    /**
     * In SQL.
     *
     * Prepares an IN or equal SQL fragment and its parameters.
     *
     * @return string The SQL fragment.
     */
    final protected function in_sql($paramprefix, $values) {
        global $DB;

        // This is to prevent get_in_or_equal from unpredictably generating long parameters.
        if (strlen($paramprefix) > 25) {
            throw new \coding_exception('Parameter prefix is limited to 25 chars for compatibility reasons.');
        }

        [$insql, $inparams] = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED, $paramprefix);
        $this->add_params($inparams);
        return $insql;
    }

    /**
     * Whether the query is already prepared.
     *
     * @return bool
     */
    final protected function is_prepared() {
        return $this->isprepared;
    }

    /**
     * List the results.
     *
     * @param int $offset The offset, or 0 for unlimited.
     * @param int $limit The quantity, or 0 for unlimited.
     * @return \Generator Of objects.
     */
    public function list($offset = 0, $limit = 0): Generator {
        global $DB;
        $this->prepare_query();

        $sql = "SELECT {$this->get_select()}
                  FROM {{$this->tablename}} {$this->tablealias} {$this->get_joins()}
                 WHERE {$this->get_where()}";
        if ($orderby = $this->get_order_by()) {
            $sql .= " ORDER BY {$orderby}";
        }

        if ($this->debug) {
            $DB->set_debug(true);
        }
        $recordset = $DB->get_recordset_sql($sql, $this->get_params(), $offset, $limit);
        if ($this->debug) {
            $DB->set_debug(false);
        }
        foreach ($recordset as $record) {
            yield $this->convert_record($record);
        }
        $recordset->close();
    }

    /**
     * Prepare query.
     *
     * Override to add custom logic.
     *
     * @return void
     */
    protected function prepare_query() {
    }

    /**
     * Prepare query.
     *
     * Override to add custom logic.
     *
     * @return void
     */
    final protected function prepare_query_if_needed() {
        if ($this->isprepared) {
            return;
        }
        $this->prepare_query();
        $this->isprepared = true;
    }

    /**
     * Remove a select.
     *
     * @param string $localname The local name.
     */
    final protected function remove_select($localname) {
        unset($this->select[$localname]);
    }

    /**
     * Set debug.
     *
     * @return static
     */
    final public function set_debug(bool $debug): self {
        $this->debug = $debug;
        return $this;
    }
}
