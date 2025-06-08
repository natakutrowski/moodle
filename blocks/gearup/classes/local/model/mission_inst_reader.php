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

use block_gearup\local\mission\mission_instance;
use block_gearup\local\model\mission as mission_model;
use block_gearup\local\model\mission_inst as mission_inst_model;
use block_gearup\local\repository\mission_instance_query;
use block_gearup\local\utils\collection_utils;
use block_gearup\local\utils\user_utils;
use core\dml\sql_join;
use core_user\fields;

/**
 * Reader.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_inst_reader extends db_reader {

    /** @var mission_instance_query The query. */
    protected $query;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('block_gearup_mission_inst', 'mi');
    }

    /**
     * Filter by status.
     *
     * @param string $status
     */
    protected function add_filter_by_status(string $status) {
        $sql = '1=1';
        $params = [];

        if (!$status || $status === 'any') {
            // Nothing to do.
        } else if ($status === 'inprogress_zero') {
            $sql = 'mi.state = :status AND mi.completionratio = 0';
            $params['status'] = mission_instance::STATE_STARTED;
        } else if ($status === 'inprogress_partial') {
            $sql = 'mi.state = :status AND mi.completionratio > 0 AND mi.completionratio < 1';
            $params['status'] = mission_instance::STATE_STARTED;
        } else if ($status === 'is_assigned') {
            $sql = 'mi.state = :status';
            $params['status'] = mission_instance::STATE_ASSIGNED;
        } else if ($status === 'is_started') {
            $sql = 'mi.state = :status';
            $params['status'] = mission_instance::STATE_STARTED;
        } else if ($status === 'is_completed') {
            $sql = 'mi.state = :status';
            $params['status'] = mission_instance::STATE_COMPLETED;
        } else if ($status === 'is_ended') {
            $sql = 'mi.state = :status';
            $params['status'] = mission_instance::STATE_ENDED;
        } else if ($status === 'has_started') {
            $sql = 'mi.state != :status';
            $params['status'] = mission_instance::STATE_ASSIGNED;
        } else if ($status === 'has_completed') {
            $sql = 'mi.state IN (:statuscompleted, :statusended)';
            $params['statuscompleted'] = mission_instance::STATE_COMPLETED;
            $params['statusended'] = mission_instance::STATE_ENDED;
        } else if ($status === 'not_started') {
            $sql = 'mi.state = :status';
            $params['status'] = mission_instance::STATE_ASSIGNED;
        } else if ($status === 'not_completed') {
            $sql = 'mi.state IN (:statusassigned, :statusstarted)';
            $params['statusassigned'] = mission_instance::STATE_ASSIGNED;
            $params['statusstarted'] = mission_instance::STATE_STARTED;
        } else if ($status === 'not_ended') {
            $sql = 'mi.state != :status';
            $params['status'] = mission_instance::STATE_ENDED;
        } else {
            debugging("Invalid or unknown status to filter by: {$status}", DEBUG_DEVELOPER);
        }

        $this->add_where('status', $sql);
        $this->add_params($params);
    }

    /**
     * Convert the database record.
     *
     * @param object $record The database row.
     * @return array Where the keys are the objects, and the values are the objects.
     */
    protected function convert_record($record) {
        if (empty($this->select)) {
            return $record;
        }

        $objects = [];
        if (array_key_exists(mission_inst_model::class, $this->select)) {
            $objects[mission_inst_model::class] = new mission_inst_model(0, mission_inst_model::extract_record($record, 'mi_'));
        }
        if (array_key_exists(mission_model::class, $this->select)) {
            $objects[mission_model::class] = new mission_model(0, mission_model::extract_record($record, 'm_'));
        }
        if (array_key_exists('subject', $this->select)) {
            $objects['subject'] = collection_utils::filter_data_with_prefix($record, 's_');
        }

        if (empty($objects)) {
            return $record;
        }

        return $objects;
    }

    /**
     * Join on mission table.
     */
    protected function join_mission_table() {
        $this->add_join('mission', new sql_join("JOIN {block_gearup_mission} m ON m.id = mi.missionid"));
    }

    /**
     * Join on user table.
     */
    protected function join_subject_table() {
        $this->add_join('subject', new sql_join("JOIN {user} s ON s.id = mi.subjectid"));
    }

    /**
     * Prepare the query.
     */
    protected function prepare_query() {
        global $DB;

        if (!$this->query) {
            throw new \Exception('A query must be set prior to using the reader.');
        }

        $query = $this->query;
        if ($query->has_condition('contextid')) {
            $this->join_mission_table();
            $this->add_where('contextid', 'm.contextid = :contextid');
            $this->add_param('contextid', (int) $query->get_condition('contextid'));
        }
        if ($query->has_condition('contextids')) {
            $this->join_mission_table();
            $insql = $this->in_sql('contextids', array_map('intval', $query->get_condition('contextids')));
            $this->add_where('contextids', "m.contextid $insql");
        }
        if ($query->has_condition('groupid')) {
            $subsql = "SELECT DISTINCT gm.userid
                         FROM {groups_members} gm
                        WHERE gm.groupid = :groupid";
            $this->add_where('groupid', "mi.subjectid IN ($subsql)");
            $this->add_param('groupid', $query->get_condition('groupid'));
        }
        if ($query->has_condition('missionid')) {
            $this->add_where('missionid', 'mi.missionid = :missionid');
            $this->add_param('missionid', (int) $query->get_condition('missionid'));
        }
        if ($query->has_condition('subjectid')) {
            $this->add_where('subjectid', 'mi.subjectid = :subjectid');
            $this->add_param('subjectid', (int) $query->get_condition('subjectid'));
        }
        if ($query->has_condition('counter_gte')) {
            $this->add_where('counter_gte', 'mi.counter >= :counter_gte');
            $this->add_param('counter_gte', (int) $query->get_condition('counter_gte'));
        }
        if ($query->has_condition('needsattention')) {
            $this->add_where('needsattention', 'mi.needsattention = :needsattention');
            $this->add_param('needsattention', (int) $query->get_condition('needsattention'));
        }
        if ($query->has_condition('state')) {
            $this->add_where('state', 'mi.state = :state');
            $this->add_param('state', (int) $query->get_condition('state'));
        }
        if ($query->has_condition('status')) {
            $this->add_filter_by_status($query->get_condition('status'));
        }
        if ($query->has_condition('mission:state')) {
            $this->join_mission_table();
            $this->add_where('missionstate', 'm.state = :missionstate');
            $this->add_param('missionstate', (int) $query->get_condition('mission:state'));
        }
        if ($query->has_condition('mission:type')) {
            $this->join_mission_table();
            $this->add_where('missiontype', 'm.type = :missiontype');
            $this->add_param('missiontype', mission_model::get_internal_type($query->get_condition('mission:type')));
        }
        if ($query->has_condition('mission:types')) {
            $this->join_mission_table();
            $sql = $this->in_sql('missiontypes', array_map(function($type) {
                return mission_model::get_internal_type($type);
            }, $query->get_condition('mission:types') ?? []));
            $this->add_where('missiontypes', "m.type $sql");
        }
        if ($query->has_condition('subject:term')) {
            $this->join_subject_table();
            $permitteduseridentityfields = array_keys(user_utils::get_visible_identity_fields($query->get_acting_context()));
            [$sql, $params] = user_utils::get_filter_user_by_term_sql($query->get_condition('subject:term'),
                $permitteduseridentityfields, 's');
            $this->add_where('subjectterm', $sql);
            $this->add_params($params);
        }

        $orderbys = $query->get_order_by() ?: [['id', SORT_ASC]];
        foreach ($orderbys as $orderby) {
            [$name, $dir] = $orderby;

            $sql = '';
            if ($name === 'id') {
                $sql = 'mi.id';
            } else if ($name === 'timecreated' || $name === 'timeassigned') {
                $sql = 'mi.timecreated';
            } else if ($name === 'timestarted') {
                $sql = 'mi.timestarted';
            } else if ($name === 'timeended') {
                $sql = 'mi.timeended';
            } else if ($name === 'timecompleted') {
                $sql = 'mi.timecompleted';
            } else if ($name === 'deadline') {
                $sql = 'mi.deadline';
            } else if ($name === 'completionratio') {
                $sql = 'mi.completionratio';
            } else if ($name === 'iteration') {
                $sql = 'mi.iteration';
            } else if ($name === 'mission:title') {
                $this->join_mission_table();
                $sql = 'm.title';
            } else if (substr($name, 0, 8) === 'subject:') {
                $subname = explode(':', $name, 2)[1];
                if (in_array($subname, ['firstname', 'lastname', 'email', 'idnumber', 'username', 'id'])) {
                    $this->join_subject_table();
                    $sql = "s.{$subname}";
                } else {
                    debugging("Unknown ordery by {$name}.", DEBUG_DEVELOPER);
                }
            } else {
                debugging("Unknown order by {$name}.", DEBUG_DEVELOPER);
            }

            if (!empty($sql)) {
                $sql = $sql . ' ' . ($dir === SORT_ASC ? 'ASC' : 'DESC');
                $this->add_order_by($name, $sql);
            }

        }
    }

    /**
     * Select the mission instance ID.
     *
     * @return self
     */
    public function select_id(): self {
        $this->add_select('id', 'mi.id');
        return $this;
    }

    /**
     * Select the mission.
     *
     * @return self
     */
    public function select_mission(): self {
        $this->join_mission_table();
        $this->add_select(mission_model::class, mission_model::get_sql_fields('m', 'm_'));
        return $this;
    }

    /**
     * Select the mission instance.
     *
     * @return self
     */
    public function select_mission_instance(): self {
        $this->add_select(mission_inst_model::class, mission_inst_model::get_sql_fields('mi', 'mi_'));
        return $this;
    }

    /**
     * Select the user fields.
     *
     * @param fields|null $fields The fields.
     * @return self
     */
    public function select_subject(?fields $fields) {
        if (!$fields) {
            $this->remove_select('subject');
            return;
        }
        $this->join_subject_table();
        $sql = $fields->get_sql('s', true, 's_', '', false);
        $this->add_select('subject', $sql->selects);
        return $this;
    }

    /**
     * Set to use the query.
     *
     * @param mission_instance_query $query The query.
     * @return self
     */
    public function use_query(mission_instance_query $query): self {
        if ($this->is_prepared()) {
            throw new \Exception('Cannot use a query after it has been prepared.');
        }
        $this->query = $query;
        return $this;
    }

}
