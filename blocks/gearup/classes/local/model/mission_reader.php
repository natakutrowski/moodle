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

use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\model\mission as mission_model;
use block_gearup\local\repository\mission_query;
use block_gearup\local\utils\collection_utils;
use block_gearup\local\utils\db_utils;

/**
 * Reader.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_reader extends db_reader {

    /** @var mission_query The query. */
    protected $query;
    /** @var string[] Field alises, where the keys are the original and the values are for SQL. */
    protected $fieldaliases = [];
    /** @var bool Whether the query has annotations. */
    protected $hasannotations = false;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('block_gearup_mission', 'm');

        $this->fieldaliases = [
            'inprogress_average_rate' => 'inprogress_avg_rate',
            'inprogress_partial_count' => 'inprogress_p_count',
            'inprogress_zero_count' => 'inprogress_z_count',
            'fastest_completion_time' => 'fastest_comp_time',
            'slowest_completion_time' => 'slowest_comp_time',
            'average_completion_time' => 'average_comp_time',
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

        $objects = [];
        if (array_key_exists(mission_model::class, $this->select)) {
            $objects[mission_model::class] = new mission_model(0, mission_model::extract_record($record, 'm_'));
        }

        if ($this->hasannotations) {
            $objects['annotations'] = collection_utils::unprefix_data(
                collection_utils::filter_data_with_prefix($record, 'annotation_'), 'annotation_');

            foreach ($this->fieldaliases as $name => $alias) {
                if (property_exists($objects['annotations'], $alias)) {
                    $objects['annotations']->{$name} = $objects['annotations']->{$alias};
                    unset($objects['annotations']->{$alias});
                }
            }
        }

        return $objects;
    }

    /**
     * Prepare the query.
     */
    protected function prepare_query() {
        if (!$this->query) {
            throw new \Exception('A query must be set prior to using the reader.');
        }

        $query = $this->query;
        if ($query->has_condition('contextid')) {
            $this->add_where('contextid', 'm.contextid = :contextid');
            $this->add_param('contextid', (int) $query->get_condition('contextid'));
        }

        if ($query->has_condition('active')) {
            // This broadly represents an active mission, as we add more states we could expand the cover of this.
            // If the goal is to fetch only the mission in "active" state, the state condition should be used instead.
            $this->add_where('active', 'm.state = :active');
            $this->add_param('active', mission::STATE_ACTIVE);
        }

        if ($query->has_condition('repeatcount')) {
            $this->add_where('repeatcount', 'm.repeatcount = :repeatcount');
            $this->add_param('repeatcount', (int) $query->get_condition('repeatcount'));
        }

        if ($query->has_condition('state')) {
            $this->add_where('state', 'm.state = :state');
            $this->add_param('state', (int) $query->get_condition('state'));
        }

        if ($query->has_condition('type')) {
            $this->add_where('type', 'm.type = :type');
            $this->add_param('type', mission_model::get_internal_type($query->get_condition('type')));
        }

        if ($query->has_condition('types')) {
            $insql = $this->in_sql('types', array_map(function($type) {
                return mission_model::get_internal_type($type);
            }, $query->get_condition('types')));
            $this->add_where('types', "m.type $insql");
        }

        if ($query->has_condition('has_completed')) {
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $this->add_where('hascompleted', "EXISTS (SELECT 1 FROM {" . mission_inst::TABLE . "} mi
                                                       WHERE mi.missionid = m.id
                                                         AND $groupfilter
                                                         AND mi.state IN (:hascompleted_completed, :hascompleted_ended))");
            $this->add_param('hascompleted_completed', mission_instance::STATE_COMPLETED);
            $this->add_param('hascompleted_ended', mission_instance::STATE_ENDED);
        }

        if ($query->has_condition('has_inprogress')) {
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $this->add_where('hasinprogress', "EXISTS (SELECT 1 FROM {" . mission_inst::TABLE . "} mi
                                                        WHERE mi.missionid = m.id
                                                          AND $groupfilter
                                                          AND mi.state = :hasinprogress_started)");
            $this->add_param('hasinprogress_started', mission_instance::STATE_STARTED);
        }

        if ($query->has_condition('has_recruits')) {
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $this->add_where('hasrecruits', "EXISTS (SELECT 1
                                                       FROM {" . mission_inst::TABLE . '} mi
                                                      WHERE mi.missionid = m.id
                                                        AND $groupfilter)');
        }

        if ($query->has_annotation('completed_count')) {
            $this->hasannotations = true;
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $this->add_select('completed_count',
                "(SELECT COUNT(mi.id)
                    FROM {" . mission_inst::TABLE . "} mi
                   WHERE mi.missionid = m.id
                     AND $groupfilter
                     AND mi.state IN (:completed_count_completed, :completed_count_ended)
                 ) AS annotation_completed_count");
            $this->add_param('completed_count_completed', mission_instance::STATE_COMPLETED);
            $this->add_param('completed_count_ended', mission_instance::STATE_ENDED);
        }

        if ($query->has_annotation('not_completed_count')) {
            $this->hasannotations = true;
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $this->add_select('not_completed_count',
                "(SELECT COUNT(mi.id)
                    FROM {" . mission_inst::TABLE . "} mi
                   WHERE mi.missionid = m.id
                     AND $groupfilter
                     AND mi.state IN (:not_completed_count_assigned, :not_completed_count_started)
                 ) AS annotation_not_completed_count");
            $this->add_param('not_completed_count_assigned', mission_instance::STATE_ASSIGNED);
            $this->add_param('not_completed_count_started', mission_instance::STATE_STARTED);
        }

        if ($query->has_annotation('highest_counter')) {
            $this->hasannotations = true;
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $this->add_select('highest_counter',
                "(SELECT COALESCE(MAX(mi.counter), 0)
                    FROM {" . mission_inst::TABLE . "} mi
                   WHERE mi.missionid = m.id
                     AND $groupfilter
                 ) AS annotation_highest_counter");
        }

        if ($query->has_annotation('highest_counter_current')) {
            $this->hasannotations = true;
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $this->add_select('highest_counter_current',
                "(SELECT COALESCE(MAX(mi.counter), 0)
                    FROM {" . mission_inst::TABLE . "} mi
                   WHERE mi.missionid = m.id
                     AND $groupfilter
                     AND mi.state != (:highest_counter_current_ended)
                 ) AS annotation_highest_counter_current");
            $this->add_param('highest_counter_current_ended', mission_instance::STATE_ENDED);
        }

        if ($query->has_annotation('completion_rate')) {
            $this->hasannotations = true;
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $subsql = "NULLIF((SELECT COUNT(mi.id)
                                 FROM {" . mission_inst::TABLE . "} mi
                                WHERE mi.missionid = m.id AND $groupfilter), 0)";
            $subsqlcast = db_utils::cast_as_float($subsql);

            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $this->add_select('completion_rate',
                "(SELECT COALESCE(
                 (SELECT COUNT(mi.id)
                    FROM {" . mission_inst::TABLE . "} mi
                   WHERE mi.missionid = m.id
                     AND mi.state IN (:completion_rate_completed, :completion_rate_ended)
                     AND $groupfilter
                 ) / ($subsqlcast)
                 , 0)) AS annotation_completion_rate");
            $this->add_param('completion_rate_completed', mission_instance::STATE_COMPLETED);
            $this->add_param('completion_rate_ended', mission_instance::STATE_ENDED);
        }

        if ($query->has_annotation('inprogress_average_rate')) {
            $this->hasannotations = true;
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $alias = $this->get_field_alias('inprogress_average_rate');
            $this->add_select('inprogress_average_rate',
                "(SELECT AVG(mi.completionratio)
                    FROM {" . mission_inst::TABLE . "} mi
                   WHERE mi.missionid = m.id
                     AND mi.state = :{$alias}_state
                     AND $groupfilter
                 ) AS annotation_{$alias}");
            $this->add_param("{$alias}_state", mission_instance::STATE_STARTED);
        }

        if ($query->has_annotation('inprogress_partial_count')) {
            $this->hasannotations = true;
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $alias = $this->get_field_alias('inprogress_partial_count');
            $this->add_select('inprogress_partial_count',
                "(SELECT COUNT(mi.id)
                    FROM {" . mission_inst::TABLE . "} mi
                   WHERE mi.missionid = m.id
                     AND mi.state = :{$alias}_state
                     AND mi.completionratio > 0
                     AND mi.completionratio < 1
                     AND $groupfilter
                 ) AS annotation_{$alias}");
            $this->add_param("{$alias}_state", mission_instance::STATE_STARTED);
        }

        if ($query->has_annotation('inprogress_zero_count')) {
            $this->hasannotations = true;
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $alias = $this->get_field_alias('inprogress_zero_count');
            $this->add_select('inprogress_zero_count',
                "(SELECT COUNT(mi.id)
                    FROM {" . mission_inst::TABLE . "} mi
                   WHERE mi.missionid = m.id
                     AND mi.state = :{$alias}_state
                     AND mi.completionratio = 0
                     AND $groupfilter
                 ) AS annotation_{$alias}");
            $this->add_param("{$alias}_state", mission_instance::STATE_STARTED);
        }

        if ($query->has_annotation('recruit_count')) {
            $this->hasannotations = true;
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $this->add_select('recruit_count',
                "(SELECT COUNT(DISTINCT mi.subjectid)
                    FROM {" . mission_inst::TABLE . "} mi
                   WHERE mi.missionid = m.id
                     AND $groupfilter) AS annotation_recruit_count");
        }

        if ($query->has_annotation('success_rate')) {
            $this->hasannotations = true;
            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $subsql = "NULLIF((SELECT COUNT(mi.id)
                                 FROM {" . mission_inst::TABLE . "} mi
                                WHERE mi.missionid = m.id
                                  AND mi.state IN (:success_rate_completed1, :success_rate_ended1)
                                  AND $groupfilter
                       ), 0)";
            $subsqlcast = db_utils::cast_as_float($subsql);

            $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
            $this->add_select('success_rate',
                "(SELECT CASE
                         WHEN m.type = :success_rate_challenge
                         THEN COALESCE(
                            (SELECT COUNT(DISTINCT mi.subjectid)
                               FROM {" . mission_inst::TABLE . "} mi
                              WHERE mi.missionid = m.id
                                AND mi.state IN (:success_rate_completed2, :success_rate_ended2)
                                AND mi.completionratio >= 1
                                AND $groupfilter
                            ) / ($subsqlcast), 0)
                         ELSE NULL
                          END
                 ) AS annotation_success_rate");
            $this->add_param('success_rate_completed1', mission_instance::STATE_COMPLETED);
            $this->add_param('success_rate_completed2', mission_instance::STATE_COMPLETED);
            $this->add_param('success_rate_ended1', mission_instance::STATE_ENDED);
            $this->add_param('success_rate_ended2', mission_instance::STATE_ENDED);
            $this->add_param('success_rate_challenge', mission_model::TYPE_CHALLENGE);
        }

        foreach (['fastest_completion_time', 'slowest_completion_time', 'average_completion_time'] as $name) {
            if ($query->has_annotation($name)) {
                $this->hasannotations = true;
                $this->add_completion_time_annotation($name);
            }
        }

        $orderbyaliases = [
            'title' => 'm.title',
            'completed_count' => 'annotation_' . $this->get_field_alias('completed_count'),
            'not_completed_count' => 'annotation_' . $this->get_field_alias('not_completed_count'),
            'completion_rate' => 'annotation_' . $this->get_field_alias('completion_rate'),
            'inprogress_average_rate' => 'annotation_' . $this->get_field_alias('inprogress_average_rate'),
            'inprogress_partial_count' => 'annotation_' . $this->get_field_alias('inprogress_partial_count'),
            'inprogress_zero_count' => 'annotation_' . $this->get_field_alias('inprogress_zero_count'),
            'recruit_count' => 'annotation_' . $this->get_field_alias('recruit_count'),
            'success_rate' => 'annotation_' . $this->get_field_alias('success_rate'),
            'fastest_completion_time' => 'annotation_' . $this->get_field_alias('fastest_completion_time'),
            'slowest_completion_time' => 'annotation_' . $this->get_field_alias('slowest_completion_time'),
            'average_completion_time' => 'annotation_' . $this->get_field_alias('average_completion_time'),
            'highest_counter' => 'annotation_' . $this->get_field_alias('highest_counter'),
            'highest_counter_current' => 'annotation_' . $this->get_field_alias('highest_counter_current'),
            'state_natural' => function() {
                $sql = "(CASE
                    WHEN m.state = :order_state_natural_draft THEN 1
                    WHEN m.state = :order_state_natural_active THEN 2
                    WHEN m.state = :order_state_natural_archived THEN 3
                    ELSE 4 END)";
                return [$sql, [
                    'order_state_natural_draft' => mission::STATE_WIZARD,
                    'order_state_natural_active' => mission::STATE_ACTIVE,
                    'order_state_natural_archived' => mission::STATE_ARCHIVED,
                ]];
            },
        ];

        foreach ($query->get_order_by() as [$orderby, $dir]) {
            $dirsql = ($dir === SORT_ASC ? 'ASC' : 'DESC');
            if (!empty($orderbyaliases[$orderby])) {
                $sql = $orderbyaliases[$orderby];
                $params = [];
                if (is_callable($orderbyaliases[$orderby])) {
                    [$sql, $params] = $orderbyaliases[$orderby]();
                }
                $this->add_params($params);
                $this->add_order_by($orderby, $sql . ' ' . $dirsql);
            }
        }

        if (!isset($this->orderby['id'])) {
            $this->add_order_by('id', 'm.id ASC');
        }

        if (!isset($this->select[mission_model::class])) {
            $this->select_mission();
        }
    }

    /**
     * Add completion time annotation.
     *
     * @param string $name The annotation name.
     */
    protected function add_completion_time_annotation($name) {
        if ($name === 'fastest_completion_time') {
            $aggfn = 'MIN';
        } else if ($name === 'slowest_completion_time') {
            $aggfn = 'MAX';
        } else if ($name === 'average_completion_time') {
            $aggfn = 'AVG';
        } else {
            return;
        }

        $alias = $this->get_field_alias($name);
        $paramname = $alias . '_challenge';
        $groupfilter = $this->make_filter_group_id_sql('mi.subjectid');
        $select = "(SELECT {$aggfn}(mi.timecompleted - mi.timecreated)
                      FROM {" . mission_inst::TABLE . "} mi
                     WHERE mi.missionid = m.id
                       AND mi.timestarted > 0
                       AND mi.timecompleted > 0
                       AND $groupfilter
                       AND (m.type != :{$paramname} OR mi.completionratio >= 1)) AS annotation_{$alias}";
        $params = [$paramname => mission_model::TYPE_CHALLENGE];

        $this->add_select($name, $select);
        $this->add_params($params);
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
     * Make filter by group ID sql.
     *
     * @param string $userfield The user field.
     * @return string SQL fragment.
     */
    protected function make_filter_group_id_sql($userfield) {
        static $i = 0;

        if (!$this->query->has_condition('groupid')) {
            return '(1=1)';
        }

        $paramname = 'groupid' . $i++;
        $this->add_param($paramname, $this->query->get_condition('groupid'));
        return "($userfield IN (SELECT DISTINCT gm.userid FROM {groups_members} gm WHERE gm.groupid = :$paramname))";
    }

    /**
     * Select the mission.
     *
     * @return self
     */
    public function select_mission(): self {
        $this->add_select(mission_model::class, mission_model::get_sql_fields('m', 'm_'));
        return $this;
    }

    /**
     * Set to use the query.
     *
     * @param mission_query $query The query.
     * @return self
     */
    public function use_query(mission_query $query): self {
        if ($this->is_prepared()) {
            throw new \Exception('Cannot use a query after it has been prepared.');
        }
        $this->query = $query;
        return $this;
    }

}
