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
use block_gearup\local\repository\user_query;
use block_gearup\local\utils\user_utils;
use core\dml\sql_join;

/**
 * Reader.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_reader extends db_reader {

    /** @var string[] */
    protected $fieldaliases;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('user', 'u');

        $this->fieldaliases = [
            'mission_instance_counter_latest' => 'mission_instance_counter_last',
            'mission_instance_counter_latest_p1' => 'mission_instance_counter_lasp1',
            'mission_instance_counter_latest_p2' => 'mission_instance_counter_lasp2',
            'mission_instance_counter_reset_count1' => 'mi_counter_reset_count1',
            'mission_instance_counter_reset_count2' => 'mi_counter_reset_count2',
        ];
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

        foreach ($this->fieldaliases as $name => $alias) {
            if (property_exists($record, $alias)) {
                $record->{$name} = $record->{$alias};
                unset($record->{$alias});
            }
        }

        return $record;
    }

    /**
     * Get the alias for a field.
     *
     * This is used to shorten the field names in the SQL query.
     *
     * @param string $name The expected name.
     * @return string The alias name.
     */
    protected function get_field_alias($name) {
        return $this->fieldaliases[$name] ?? $name;
    }

    /**
     * Join on mission instance table.
     */
    protected function join_mission_instance_table() {
        $this->add_join('mission_instance', new sql_join("JOIN {block_gearup_mission_inst} mi ON u.id = mi.subjectid"));
    }

    protected function prepare_query() {
        if (!isset($this->select['user'])) {
            $this->select = array_merge([
                'user' => 'u.*',
            ], $this->select);
        }
        if (empty($this->orderby['id'])) {
            $this->add_order_by('id', 'u.id ASC');
        }
    }

    /**
     * use a query.
     *
     * @param user_query $query
     * @return self
     */
    public function use_query(user_query $query): self {
        $this->add_where('deleted', 'u.deleted = 0');

        if ($query->has_condition('contextid')) {
            $subsql = "SELECT DISTINCT mi.subjectid
                         FROM {" . mission_inst::TABLE . "} mi
                         JOIN {" . mission::TABLE . "} m
                           ON m.id = mi.missionid
                        WHERE m.contextid = :contextid";
            $this->add_where('contextid', "u.id IN ($subsql)");
            $this->add_param('contextid', $query->get_condition('contextid'));
        }

        if ($query->has_condition('missionid')) {
            $subsql = "SELECT DISTINCT mi.subjectid
                         FROM {" . mission_inst::TABLE . "} mi
                        WHERE mi.missionid = :missionid";
            $this->add_where('missionid', "u.id IN ($subsql)");
            $this->add_param('missionid', $query->get_condition('missionid'));
        }

        if ($query->has_condition('mission:types')) {
            $insql = $this->in_sql('mission_types', array_map(function ($value) {
                return mission::get_internal_type($value);
            }, $query->get_condition('mission:types')));

            $subsql = "SELECT DISTINCT mi.subjectid
                         FROM {" . mission_inst::TABLE . "} mi
                         JOIN {" . mission::TABLE . "} m
                           ON m.id = mi.missionid
                        WHERE m.type $insql";
            $this->add_where('mission_types', "u.id IN ($subsql)");
        }

        if ($query->has_condition('groupid')) {
            $subsql = "SELECT DISTINCT gm.userid
                         FROM {groups_members} gm
                        WHERE gm.groupid = :groupid";
            $this->add_where('groupid', "u.id IN ($subsql)");
            $this->add_param('groupid', $query->get_condition('groupid'));
        }

        if ($query->has_condition('term')) {
            [$sql, $params] = user_utils::get_filter_user_by_term_sql($query->get_condition('term'),
                array_keys(user_utils::get_visible_identity_fields($query->get_acting_context()))
            );
            $this->add_where('term', $sql);
            $this->add_params($params);
        }

        if ($query->has_annotation('mission_count')) {
            if ($query->has_condition('contextid')) {
                $this->add_select('mission_count',
                    '(SELECT COUNT(DISTINCT mi.missionid)
                        FROM {' . mission_inst::TABLE . '} mi
                        JOIN {' . mission::TABLE . '} m
                          ON m.id = mi.missionid
                       WHERE mi.subjectid = u.id
                         AND m.contextid = :mission_count) AS mission_count'
                );
                $this->add_param('mission_count', $query->get_condition('contextid'));
            }
        }

        if ($query->has_annotation('mission_instance_counter_best')) {
            if ($query->has_condition('missionid')) {
                $this->add_select('mission_instance_counter_best',
                    '(SELECT MAX(mi.counter) FROM {' . mission_inst::TABLE . '} mi
                       WHERE mi.subjectid = u.id
                         AND mi.missionid = :mission_instance_counter_best) AS mission_instance_counter_best'
                );
                $this->add_param('mission_instance_counter_best', $query->get_condition('missionid'));
            }
        }

        if ($query->has_annotation('mission_instance_counter_latest')) {
            if ($query->has_condition('missionid')) {
                // We take MAX to avoid multiple results, although that should never happen.
                $annotationalias = $this->get_field_alias('mission_instance_counter_latest');
                $missionidp1 = $this->get_field_alias('mission_instance_counter_latest_p1');
                $missionidp2 = $this->get_field_alias('mission_instance_counter_latest_p2');
                $this->add_select('mission_instance_counter_latest',
                    "(SELECT MAX(mi.counter) FROM {" . mission_inst::TABLE . "} mi
                       WHERE mi.subjectid = u.id
                         AND mi.missionid = :{$missionidp1}
                         AND mi.iteration = (
                          SELECT MAX(mi2.iteration)
                            FROM {" . mission_inst::TABLE . "} mi2
                           WHERE mi2.subjectid = u.id
                             AND mi2.missionid = :{$missionidp2})
                    ) AS {$annotationalias}"
                );
                $this->add_param($missionidp1, $query->get_condition('missionid'));
                $this->add_param($missionidp2, $query->get_condition('missionid'));
            }
        }

        if ($query->has_annotation('mission_instance_counter_reset_count')) {
            if ($query->has_condition('missionid')) {
                $missionidparam = $this->get_field_alias('mission_instance_counter_reset_count1');
                $stateparam = $this->get_field_alias('mission_instance_counter_reset_count2');
                $this->add_select("mission_instance_counter_reset_count",
                    "(SELECT COUNT(mi.id) FROM {" . mission_inst::TABLE . "} mi
                       WHERE mi.subjectid = u.id
                         AND mi.missionid = :{$missionidparam}
                         AND mi.counter > 0
                         AND mi.state = :{$stateparam}) AS mission_instance_counter_reset_count"
                );
                $this->add_param($missionidparam, $query->get_condition('missionid'));
                $this->add_param($stateparam, mission_instance::STATE_ENDED);
            }
        }

        if ($query->has_annotation('mission_instance_count')) {
            if ($query->has_condition('missionid')) {
                $this->add_select('mission_instance_count',
                    '(SELECT COUNT(mi.id) FROM {' . mission_inst::TABLE . '} mi
                       WHERE mi.subjectid = u.id
                         AND mi.missionid = :mission_instance_count) AS mission_instance_count'
                );
                $this->add_param('mission_instance_count', $query->get_condition('missionid'));
            }
        }

        if ($query->has_annotation('mission_instance_100pc_count')) {
            if ($query->has_condition('missionid')) {
                $this->add_select('mission_instance_100pc_count',
                    '(SELECT COUNT(mi.id) FROM {' . mission_inst::TABLE . '} mi
                       WHERE mi.subjectid = u.id
                         AND mi.missionid = :mission_instance_100pc_count
                         AND mi.completionratio >= 1) AS mission_instance_100pc_count'
                );
                $this->add_param('mission_instance_100pc_count', $query->get_condition('missionid'));
            }
        }

        if ($query->has_annotation('mission_instance_ended_count')) {
            if ($query->has_condition('missionid')) {
                $this->add_select('mission_instance_ended_count',
                    '(SELECT COUNT(mi.id) FROM {' . mission_inst::TABLE . '} mi
                       WHERE mi.subjectid = u.id
                         AND mi.missionid = :mission_instance_ended_count1
                         AND mi.state = :mission_instance_ended_count2) AS mission_instance_ended_count'
                );
                $this->add_param('mission_instance_ended_count1', $query->get_condition('missionid'));
                $this->add_param('mission_instance_ended_count2', mission_instance::STATE_ENDED);
            }
        }

        $orderbyaliases = [
            'id' => 'u.id',
            'firstname' => 'u.firstname',
            'lastname' => 'u.lastname',
            'username' => 'u.username',
            'idnumber' => 'u.idnumber',
            'email' => 'u.email',
            'mission_instance_counter_best' => $this->get_field_alias('mission_instance_counter_best'),
            'mission_instance_counter_latest' => $this->get_field_alias('mission_instance_counter_latest'),
            'mission_instance_ended_count' => 'mission_instance_ended_count',
        ];

        foreach ($query->get_order_by() as [$orderby, $dir]) {
            $dirsql = ($dir === SORT_ASC ? 'ASC' : 'DESC');
            if (!empty($orderbyaliases[$orderby])) {
                $this->add_order_by($orderby, $orderbyaliases[$orderby] . ' ' . $dirsql);
            }
        }

        if (!isset($this->orderby['id'])) {
            $this->add_order_by('id', 'u.id ASC');
        }

        return $this;
    }

}
