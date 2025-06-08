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

/**
 * Repository.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\repository;

use block_gearup\di;
use block_gearup\local\assigner\persisted_assigner;
use block_gearup\local\assigner\resolver\resolver as assigner_resolver;
use block_gearup\local\mission\achievement;
use block_gearup\local\mission\challenge;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\mission\persisted_achievement;
use block_gearup\local\mission\persisted_challenge;
use block_gearup\local\mission\persisted_mission_instance;
use block_gearup\local\mission\persisted_quest;
use block_gearup\local\mission\persisted_streak;
use block_gearup\local\mission\quest;
use block_gearup\local\mission\streak;
use block_gearup\local\model\assigner as assigner_model;
use block_gearup\local\model\mission as mission_model;
use block_gearup\local\model\mission_inst as mission_inst_model;
use block_gearup\local\model\mission_inst_reader;
use block_gearup\local\model\mission_reader;
use block_gearup\local\model\objective as objective_model;
use block_gearup\local\model\objective_inst as objective_inst_model;
use block_gearup\local\model\outcome as outcome_model;
use block_gearup\local\model\user_reader;
use block_gearup\local\objective\persisted_objective;
use block_gearup\local\objective\persisted_objective_instance;
use block_gearup\local\objective\resolver\resolver as objective_resolver;
use block_gearup\local\outcome\persisted_outcome;
use block_gearup\local\outcome\resolver\resolver as outcome_resolver;
use context;
use context_system;

/**
 * Repository.
 *
 * This is a temporary implementation. None of the public functions can be removed
 * without notice. Third party developers should not extend, or use this class.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository {

    protected $objtyperesolver;
    protected $outcometyperesolver;
    protected $assignertyperesolver;
    /** @var \core\clock */
    protected $clock;

    public function __construct(objective_resolver $objtyperesolver, outcome_resolver $outcometyperesolver,
            assigner_resolver $assignertyperesolver) {
        $this->objtyperesolver = $objtyperesolver;
        $this->outcometyperesolver = $outcometyperesolver;
        $this->assignertyperesolver = $assignertyperesolver;
        $this->clock = di::get('clock');
    }

    /**
     * Undocumented function
     *
     * @param int $subjectid
     * @param array|null $state
     * @param context|null $context
     */
    public function count_achievement_instances_by_subject_id(int $subjectid, $state = null, ?context $context = null) {
        return $this->count_instances_by_subject_id(mission_model::TYPE_ACHIEVEMENT, $subjectid, $state, $context);
    }

    /**
     * @deprecated Since Quest 1.6, do not use.
     */
    public function count_challenge_instances_by_subject_id(int $subjectid, $state = null, ?context $context = null) {
        debugging('The method count_challenge_instances_by_subject_id is deprecated, do not use.', DEBUG_DEVELOPER);
        return 0;
    }

    /**
     * Count assigners.
     *
     * @param int $missionid The mission ID.
     * @param int|null $isenabled Optionally, whether the assigner is enabled or not.
     */
    public function count_assigners(int $missionid, ?int $isenabled = null) {
        $params = ['missionid' => $missionid];
        if ($isenabled !== null) {
            $params['enabled'] = (bool) $isenabled;
        }
        return assigner_model::count_records($params);
    }

    /**
     * @deprecated Since Quest 1.6, do not use.
     */
    public function count_available_quests(int $subjectid, ?context $context = null) {
        debugging('The method count_available_quests is deprecated, do not use.', DEBUG_DEVELOPER);
        return 0;
    }

    public function count_instances_from_query(mission_instance_query $query): int {
        return (new mission_inst_reader())->use_query($query)->count();
    }

    /**
     * @deprecated Since Quest 1.6, do not use.
     */
    public function count_instances(int $missionid, ?int $subjectid = null) {
        debugging('The method count_instances is deprecated, do not use.', DEBUG_DEVELOPER);
        return 0;
    }

    public function count_instances_completed(int $missionid) {
        global $DB;
        list($insql, $inparams) = $DB->get_in_or_equal([
            mission_instance::STATE_COMPLETED,
            mission_instance::STATE_ENDED,
        ], SQL_PARAMS_NAMED);
        return mission_inst_model::count_records_select(
            "missionid = :missionid AND state $insql",
            array_merge([
                'missionid' => $missionid,
            ], $inparams)
        );
    }

    public function count_missions(?context $context = null) {
        $filters = [];
        if ($context) {
            $filters['contextid'] = $context->id;
        }
        return mission_model::count_records($filters);
    }

    /**
     * Count the visible quest instances by subject ID.
     *
     * @param int $subjectid
     * @param array|null $state
     * @param context|null $context
     * @return int
     */
    public function count_quest_instances_by_subject_id(int $subjectid, $state = null, ?context $context = null) {
        return $this->count_instances_by_subject_id(mission_model::TYPE_QUEST, $subjectid, $state, $context);
    }

    /**
     * Count user missions.
     *
     * @param int $subjectid
     * @param context|null $context
     */
    public function count_user_missions(int $subjectid, ?context $context = null) {
        global $DB;

        $wheres = ['mi.subjectid = ?'];
        $params = [$subjectid];
        if ($context) {
            $wheres[] = 'm.contextid = ?';
            $params[] = $context->id;
        }

        $sql = "SELECT COUNT(DISTINCT mi.missionid)
                  FROM {block_gearup_mission_inst} mi
                  JOIN {block_gearup_mission} m
                    ON m.id = mi.missionid
                 WHERE " . implode(' AND ', $wheres);
        return (int) $DB->get_field_sql($sql, $params);
    }

    public function count_users(int $missionid) {
        return mission_inst_model::count_subjects_by_missionid($missionid);
    }

    public function count_users_from_query(user_query $query) {
        return (new user_reader())->use_query($query)->count();
    }

    /**
     * Get the visible achievement instances of a user.
     *
     * @param int $subjectid
     * @param array|null $state
     * @param context|null $context
     * @param array|null $order
     * @param int $offset
     * @param int $limit
     * @return achievement[]
     */
    public function get_achievement_instances_by_subject_id(int $subjectid, $state = null, ?context $context = null,
            ?array $order = null, $offset = 0, $limit = 0) {
        return $this->get_type_instances_by_subject_id(achievement::class, $subjectid, $state, $context, $order, $offset, $limit);
    }

    public function get_achievements($context = null, $offset = 0, $limit = 0) {
        $filters = ['type' => mission_model::TYPE_ACHIEVEMENT, 'state' => mission::STATE_ACTIVE];
        if ($context) {
            $filters['contextid'] = $context->id;
        }
        return array_map(function($model) {
            return $this->make_mission_from_model($model);
        }, mission_model::get_records($filters, 'title', 'ASC', $offset, $limit));
    }

    /**
     * Get the IDs of the achievements to announce.
     *
     * @param context $context The context.
     * @param int $subjectid The subject ID.
     */
    public function get_achievement_ids_to_announce($context, $subjectid) {
        $query = (new mission_instance_query($context))
            ->set_context_id($context->id)
            ->set_subject_id($subjectid)
            ->set_mission_type(achievement::class)
            ->set_mission_state(mission::STATE_ACTIVE)
            ->set_state(mission_instance::STATE_ENDED)
            ->set_needs_attention(true);
        $reader = (new mission_inst_reader())
            ->use_query($query)
            ->select_id();
        return array_map(function($record) {
            return (int) $record->id;
        }, iterator_to_array($reader->list()));
    }

    public function get_assigners(int $missionid) {
        $filters = ['missionid' => $missionid];
        return array_map(function($model) {
            return new persisted_assigner($model, $this->assignertyperesolver);
        }, assigner_model::get_records($filters, 'id', 'ASC'));
    }

    public function get_available_quests(int $subjectid, ?context $context = null, $offset = 0, $limit = 0) {
        /** Used 1x */
        $sql = "    m.type = ?
                AND m.state = ?
                AND mi.state = ?
                AND mi.subjectid = ?
                AND m.visibility = ?";
        $params = [
            mission_model::TYPE_QUEST,
            mission::STATE_ACTIVE,
            mission_instance::STATE_ASSIGNED,
            $subjectid,
            mission::VISIBLE_ALWAYS,
        ];
        if ($context) {
            $sql .= " AND m.contextid = ?";
            $params[] = $context->id;
        }
        return $this->fetch_instances_select($sql, $params, 'mi.timecreated DESC', $offset, $limit);
    }

    public function get_challenges_by_subject_id(int $subjectid, ?context $context = null, $offset = 0, $limit = 0) {
        global $DB;

        $filters = ['m.type = :type'];
        $params = ['type' => mission_model::TYPE_CHALLENGE];

        if ($context) {
            $filters[] = 'm.contextid = :contextid';
            $params['contextid'] = $context->id;
        }

        $sql = "SELECT m.*
                  FROM {block_gearup_mission} m
                 WHERE EXISTS (SELECT mi.id
                                 FROM {block_gearup_mission_inst} mi
                                WHERE mi.missionid = m.id
                                  AND mi.subjectid = :subjectid)
                   AND " . implode(' AND ', $filters);
        $params['subjectid'] = $subjectid;

        return array_map(function($record) {
            return $this->make_mission_from_model(new mission_model(0, $record));
        }, $DB->get_records_sql($sql, $params, $offset, $limit));
    }

    /**
     * Get the visible challenge instances of a user.
     *
     * @param int $subjectid
     * @param array|null $state
     * @param context|null $context
     * @param array|null $order
     * @param int $offset
     * @param int $limit
     */
    public function get_challenge_instances_by_subject_id(int $subjectid, $state = null, ?context $context = null,
            ?array $order = null, $offset = 0, $limit = 0) {
        return $this->get_type_instances_by_subject_id(challenge::class, $subjectid, $state, $context, $order, $offset, $limit);
    }

    public function get_challenges($context = null, $offset = 0, $limit = 0) {
        $filters = ['type' => mission_model::TYPE_CHALLENGE, 'state' => mission::STATE_ACTIVE];
        if ($context) {
            $filters['contextid'] = $context->id;
        }
        return array_map(function($model) {
            return $this->make_mission_from_model($model);
        }, mission_model::get_records($filters, 'title', 'ASC', $offset, $limit));
    }

    /**
     * Get the current visible streaks of a user.
     *
     * @param int $subjectid The subject ID.
     * @param context|null $context The context.
     * @return ?mission_instance
     */
    public function get_current_streaks(int $subjectid, context $context) {
        // Return the current streaks. We know there should only be one.
        return $this->get_type_instances_by_subject_id(
            streak::class,
            $subjectid,
            [mission_instance::STATE_STARTED,
            mission_instance::STATE_COMPLETED],
            $context,
            ['mi.timecreated ASC', 'm.id ASC'],
            0,
            1
        );
    }

    /**
     * Get the missions that have expired.
     *
     * Those are the missions where the deadline has passed, or where they have been completed
     * and should be dismissed such as a one-off completed challenge.
     *
     * @return mission_instance[]
     */
    public function get_expired_mission_instances() {
        $stateors = [
            '(mi.state IN (:state1, :state2, :state3) AND mi.deadline > 0 AND mi.deadline <= :deadlinenow)',
            '(mi.state = :state4 AND mi.deadline = 0 AND mi.timecompleted < :timethreshold)',
        ];
        $stateparams = [
            // Has deadline, and deadline expired.
            'state1' => mission_instance::STATE_ASSIGNED,
            'state2' => mission_instance::STATE_STARTED,
            'state3' => mission_instance::STATE_COMPLETED,
            'deadlinenow' => time(),

            // No deadline, but completed within a day.
            'state4' => mission_instance::STATE_COMPLETED,
            'timethreshold' => time() - DAYSECS,
        ];
        $statewhere = '(' . implode(') OR (', $stateors) . ')';

        $sql = "m.state = :missionactive AND ($statewhere)";
        $params = ['missionactive' => mission::STATE_ACTIVE] + $stateparams;
        return $this->fetch_instances_select($sql, $params, 'mi.id ASC');
    }

    /**
     * Get incomplete objective instances of types.
     *
     * This is only considering objectives that are current (active, not dormant, etc.).
     *
     * @param array $types
     * @param int $userid
     * @param context $context
     * @return persisted_objective_instance[]
     */
    public function get_incomplete_objective_instances_of_types(array $types, int $userid, context $context) {
        global $DB;
        if (empty($types)) {
            return [];
        }

        $typenames = array_map(function($type) {
            return $this->objtyperesolver->get_type_name($type);
        }, $types);

        list($insql, $inparams) = $DB->get_in_or_equal($typenames, SQL_PARAMS_NAMED);
        $oifields = objective_inst_model::get_sql_fields('oi', 'oi_');
        $ofields = objective_model::get_sql_fields('o', 'o_');

        $sql = "SELECT $oifields, $ofields
                  FROM {block_gearup_objective_inst} oi
                  JOIN {block_gearup_objective} o
                    ON o.id = oi.objectiveid
                  JOIN {block_gearup_mission_inst} mi
                    ON mi.id = oi.missioninstid
                  JOIN {block_gearup_mission} m
                    ON m.id = mi.missionid
                  JOIN {context} ctx
                    ON ctx.id = m.contextid
                 WHERE o.type $insql
                   AND m.state = :missionactive
                   AND oi.state = 0
                   AND (ctx.id = :contextid
                        OR {$DB->sql_like('ctx.path', ':contextparentpath')}
                        OR :contextchildpath LIKE {$DB->sql_concat('ctx.path', "'/%'")}
                       )
                   AND (oi.dormantuntil IS NULL OR oi.dormantuntil < :nowdormant)
                   AND mi.state = :state
                   AND mi.subjectid = :userid";
        $params = $inparams + [
            'state' => mission_instance::STATE_STARTED,
            'userid' => $userid,
            'nowdormant' => $this->clock->time(),
            'missionactive' => mission::STATE_ACTIVE,

            // Filter the context as per the action and mission contexts. At this point, if
            // mission and action do not share a context path, then the action should be
            // irrelevant to anybody. We select where the context is a descendant of the mission's,
            // or the mission's is a descendant of the context, or they are the same.
            'contextid' => $context->id,
            'contextparentpath' => $DB->sql_like_escape($context->path) . '/%',
            'contextchildpath' => $context->path . '/',
        ];
        $records = $DB->get_records_sql($sql, $params);

        return array_map(function($record) {
            $oipersistent = new objective_inst_model(0, objective_inst_model::extract_record($record, 'oi_'));
            $opersistent = new objective_model(0, objective_model::extract_record($record, 'o_'));
            $obj = new persisted_objective($opersistent, $this->objtyperesolver);
            $objinst = new persisted_objective_instance($oipersistent, $obj);
            return $objinst;
        }, $records);
    }

    /**
     * Get which types have incomplete objective instances amongst the given types.
     *
     * This is only considering objectives that are current (active, not dormant, etc.).
     *
     * @param array $types
     * @param int $userid
     * @param context $context
     * @return string[]
     */
    public function get_incomplete_objective_instance_type_names_amongst_types(array $types, int $userid, context $context) {
        global $DB;
        if (empty($types)) {
            return [];
        }

        $typenames = array_map(function($type) {
            return $this->objtyperesolver->get_type_name($type);
        }, $types);

        list($insql, $inparams) = $DB->get_in_or_equal($typenames, SQL_PARAMS_NAMED);

        $sql = "SELECT DISTINCT o.type
                  FROM {block_gearup_objective_inst} oi
                  JOIN {block_gearup_objective} o
                    ON o.id = oi.objectiveid
                  JOIN {block_gearup_mission_inst} mi
                    ON mi.id = oi.missioninstid
                  JOIN {block_gearup_mission} m
                    ON m.id = mi.missionid
                  JOIN {context} ctx
                    ON ctx.id = m.contextid
                 WHERE o.type $insql
                   AND oi.state = 0
                   AND m.state = :missionactive
                   AND (ctx.id = :contextid
                        OR {$DB->sql_like('ctx.path', ':contextparentpath')}
                        OR :contextchildpath LIKE {$DB->sql_concat('ctx.path', "'/%'")}
                       )
                   AND (oi.dormantuntil IS NULL OR oi.dormantuntil < :nowdormant)
                   AND mi.state = :state
                   AND mi.subjectid = :userid";
        $params = $inparams + [
            'state' => mission_instance::STATE_STARTED,
            'userid' => $userid,
            'nowdormant' => $this->clock->time(),
            'missionactive' => mission::STATE_ACTIVE,

            // Filter the context as per the action and mission contexts. At this point, if
            // mission and action do not share a context path, then the action should be
            // irrelevant to anybody. We select where the action is a descendant of the mission,
            // or the mission is a descendant of the action, or they are the same.
            'contextid' => $context->id,
            'contextparentpath' => $DB->sql_like_escape($context->path) . '/%',
            'contextchildpath' => $DB->sql_like_escape($context->path) . '/%',
        ];
        return array_values($DB->get_fieldset_sql($sql, $params));
    }

    /**
     * Get an instance.
     *
     * This does not filter on anything such as mission state, etc.
     *
     * @param int $missioninstid
     * @return ?mission_instance
     */
    public function get_instance(int $missioninstid) {
        $missioninsts = $this->fetch_instances_select("mi.id = ?", [$missioninstid]);
        if (empty($missioninsts)) {
            throw new \moodle_exception('notfound', 'block_gearup');
        }
        return reset($missioninsts);
    }

    /**
     * Get the instance of a subject.
     *
     * This does not filter on anything such as mission state, etc.
     *
     * @param int $missionid
     * @param int $subjectid
     * @return ?mission_instance
     */
    public function get_instance_by_subject_id(int $missionid, int $subjectid) {
        $missioninsts = $this->fetch_instances_select("mi.missionid = ? AND mi.subjectid = ?", [$missionid, $subjectid],
            'mi.timestarted ASC, mi.id ASC');
        if (empty($missioninsts)) {
            throw new \moodle_exception('notfound');
        }
        return reset($missioninsts);
    }

    public function get_instances_from_query(mission_instance_query $query, int $offset = 0, int $limit = 0) {
        $reader = (new mission_inst_reader())
            ->use_query($query)
            ->select_mission_instance()
            ->select_mission();
        return $this->make_instances_from_reader_records(iterator_to_array($reader->list($offset, $limit)));
    }

    /**
     * Get instances, but only from active missions.
     *
     * @param int $missionid
     * @param int $offset
     * @param int $limit
     * @param array $orderby
     * @param ?array $state
     * @param ?int $subjectid
     */
    public function get_instances(int $missionid, int $offset = 0, int $limit = 0, array $orderby = [], $state = null,
            $subjectid = null) {
        global $DB;

        // TODO We should not be creating SQL order like that, we should use a class.
        // We expecte the format [['column', SORT_ASC], ['column', SORT_DESC]], but we also accept ['column' => SORT_ASC].
        $orderby = !empty($orderby) && !is_array($orderby[0]) ? [$orderby] : $orderby;
        $orderparts = array_map(function($part) {
            $column = $part[0];
            $sort = $part[1];
            $prefix = 'mi.';
            if ($column === 'lastname' || $column === 'firstname') {
                $prefix = 'u.';
            } else if ($column === 'timeassigned') {
                $column = 'timecreated';
            }
            return $prefix . preg_replace('/[^a-z0-9_]/', '', $column) . ($sort == SORT_ASC ? ' ASC' : ' DESC');
        }, $orderby);
        $orderby = implode(', ', $orderparts);

        $sqlparts = [
            'm.state = :missionactive',
            'm.id = :missionid',
        ];
        $params = [
            'missionactive' => mission::STATE_ACTIVE,
            'missionid' => $missionid,
        ];
        if ($subjectid) {
            $sqlparts[] = 'mi.subjectid = :subjectid';
            $params['subjectid'] = $subjectid;
        }
        if ($state !== null) {
            list($statesql, $stateparams) = $DB->get_in_or_equal((array) $state, SQL_PARAMS_NAMED);
            $sqlparts[] = "mi.state $statesql";
            $params += $stateparams;
        }
        $sql = implode(' AND ', $sqlparts);

        return $this->fetch_instances_select($sql, $params, $orderby, $offset, $limit);
    }

    /**
     * Get instances, without filter.
     *
     * @param int[] $missioninstids
     * @return mission_instance[]
     */
    public function get_instances_by_ids($missioninstids) {
        global $DB;
        if (empty($missioninstids)) {
            return [];
        }
        list($insql, $inparams) = $DB->get_in_or_equal($missioninstids, SQL_PARAMS_NAMED);
        return $this->fetch_instances_select("mi.id $insql", $inparams);
    }

    /**
     * @deprecated Since Quest 1.6, do not use.
     */
    public function get_instances_by_subject_id(int $subjectid) {
        debugging('The method get_instances_by_subject_id is deprecated, use something else.', DEBUG_DEVELOPER);
    }

    /**
     * Get all the latest streak instances of a user.
     *
     * This excludes non-active streak missions.
     *
     * @param int $subjectid The subject ID.
     * @param context|null $context The context.
     * @return mission_instance[]
     */
    public function get_latest_streak_instances(int $subjectid, context $context) {
        $subsql = "NOT EXISTS (
                   SELECT 1
                     FROM {block_gearup_mission_inst} mi2
                    WHERE mi2.subjectid = mi.subjectid
                      AND mi2.missionid = mi.missionid
                      AND mi2.iteration > mi.iteration)";
        $wheresql = "mi.subjectid = :subjectid
                 AND m.contextid = :contextid
                 AND m.type = :type
                 AND m.state = :state
                 AND $subsql";
        $params = [
            'subjectid' => $subjectid,
            'contextid' => $context->id,
            'state' => mission::STATE_ACTIVE,
            'type' => mission_model::get_internal_type(streak::class),
        ];
        $ordersql = 'm.title ASC';
        return $this->fetch_instances_select($wheresql, $params, $ordersql, 0, 0);
    }

    /**
     * Get a mission, without filter.
     *
     * @return ?mission
     */
    public function get_mission(int $id) {
        $mission = mission_model::get_record(['id' => $id]);
        return $mission ? $this->make_mission_from_model($mission) : null;
    }

    public function get_mission_by_instanceid(int $id) {
        global $DB;
        $mfields = mission_model::get_sql_fields('m', '');
        $sql = "SELECT $mfields
                  FROM {block_gearup_mission_inst} mi
                  JOIN {block_gearup_mission} m
                    ON m.id = mi.missionid
                 WHERE mi.id = :id";
        $params = ['id' => $id];
        $record = $DB->get_record_sql($sql, $params, MUST_EXIST);
        $mission = $this->make_mission_from_model(new mission_model(0, $record));
        if (!$mission) {
            throw new \moodle_exception('notfound', 'block_gearup');
        }
        return $mission;
    }

    /**
     * @deprecated Since Quest 1.6, do not use.
     */
    public function get_missions(?context $context = null, $offset = 0, $limit = 0) {
        // This method should no longer be used. It used to obtain all missions, but now that have
        // introduced the concept of streaks and archived ones, we need to restrict the scope of
        // this method to maintain its behaviour. We also added an exclusion of draft missions as
        // they probably were never intended to be read from here.
        $query = (new mission_query($context ?? context_system::instance()))
            ->filter_types([
                quest::class,
                achievement::class,
                challenge::class,
            ])
            ->filter_active();

        if ($context) {
            $query->set_context_id($context->id);
        }
        return array_map(function($row) {
            return $row->mission;
        }, iterator_to_array($this->get_missions_from_query($query, $offset, $limit)));
    }

    public function count_missions_from_query(mission_query $query): int {
        return (new mission_reader())->use_query($query)->count();
    }

    /**
     * Count the visible instances in.
     *
     * @param int $subjectid The user.
     * @param context $context The context.
     * @return int
     */
    public function count_visible_instances_in(int $subjectid, context $context, ?string $type, ?array $state) {
        global $DB;
        [$where, $params] = $this->get_visible_instances_in_sql($subjectid, $context);

        $typewhere = '1=1';
        if ($type !== null) {
            $typewhere = "m.type = :missiontype";
            $params += ['missiontype' => mission_model::get_internal_type($type)];
        }

        $statewhere = '1=1';
        if ($state !== null) {
            list($insql, $inparams) = $DB->get_in_or_equal((array) $state);
            $statewhere = "mi.state $insql";
            $params += $inparams;
        }

        $sql = "SELECT COUNT(mi.id)
                  FROM {block_gearup_mission_inst} mi
                  JOIN {block_gearup_mission} m
                    ON m.id = mi.missionid
                 WHERE ($where)
                   AND $typewhere
                   AND $statewhere";
        return $DB->count_records_sql($sql, $params);
    }

    public function get_missions_from_query(mission_query $query, $offset = 0, $limit = 0): iterable {
        // We can add an argument here to return the mission objects only. Or we can update the
        // reader so that it does not return an array of objects we only query the mission and
        // do not join/annotate anything.
        $list = (new mission_reader())->use_query($query)->list($offset, $limit);
        foreach ($list as $objects) {
            $mission = $this->make_mission_from_model($objects[mission_model::class]);
            if (!$mission) {
                continue;
            }

            yield (object) [
                'annotations' => (object) ($objects['annotations'] ?? []),
                'mission' => $mission,
            ];
        }
    }

    /**
     * Get an objective, without filter.
     *
     * @return ?objective
     */
    public function get_objective(int $objid) {
        try {
            $opersistent = new objective_model($objid);
        } catch (\moodle_exception $e) {
            return null;
        }
        return new persisted_objective($opersistent, $this->objtyperesolver);
    }

    /**
     * Get the objectives of a mission.
     *
     * @param int $missionid
     * @return objective[]
     */
    public function get_objectives(int $missionid) {
        $filters = ['missionid' => $missionid];
        return array_map(function($model) {
            return new persisted_objective($model, $this->objtyperesolver);
        }, objective_model::get_records($filters, 'id', 'ASC'));
    }

    /**
     * Get the outcomes of a mission.
     *
     * @param int $missionid
     * @param bool $visibleonly
     * @return outcome[]
     */
    public function get_outcomes(int $missionid, bool $visibleonly = false) {
        $filters = ['missionid' => $missionid];
        if ($visibleonly) {
            $filters['visibility'] = 1;
        }
        return array_map(function($model) {
            return new persisted_outcome($model, $this->outcometyperesolver);
        }, outcome_model::get_records($filters, 'id', 'ASC'));
    }

    /**
     * Get the visible quest instances of a user.
     *
     * @param int $subjectid
     * @param array|null $state
     * @param context|null $context
     * @param array|null $order
     * @param int $offset
     * @param int $limit
     */
    public function get_quest_instances_by_subject_id(int $subjectid, $state = null, ?context $context = null,
            ?array $order = null, $offset = 0, $limit = 0) {
        return $this->get_type_instances_by_subject_id(quest::class, $subjectid, $state, $context, $order, $offset, $limit);
    }

    public function get_quests($context = null, $offset = 0, $limit = 0) {
        $filters = ['type' => mission_model::TYPE_QUEST, 'state' => mission::STATE_ACTIVE];
        if ($context) {
            $filters['contextid'] = $context->id;
        }
        return array_map(function($model) {
            return $this->make_mission_from_model($model);
        }, mission_model::get_records($filters, 'title', 'ASC', $offset, $limit));
    }

    /**
     * Get stale mission instances for user.
     *
     * Those are missions that appear to have an inconsistent state and should be re-evaluated,
     * no only based on their state but also based on the state of their objectives.
     *
     * Note that this method is not currently used, and should be tested first!
     */
    public function get_stale_mission_instances_for_subject_id(context $context, int $subjectid) {
        // TODO This method should check that the mission is active.
        debugging('The method get_stale_mission_instances_for_subject_id has not been tested!', DEBUG_DEVELOPER);

        $wheres = [
            'm.contextid = :contextid',
            'mi.subjectid = :subjectid',
        ];
        $params = [
            'contextid' => $context->id,
            'subjectid' => $subjectid,
        ];
        $ors = [];

        // Stale objectives.
        $ors[] = 'EXISTS (SELECT 1 FROM {block_gearup_objective_inst} oi
                            JOIN {block_gearup_objective} o
                              ON o.id = oi.objectiveid
                           WHERE oi.missioninstid = mi.id
                             AND oi.state = 0
                             AND (oi.stalefrom IS NOT NULL AND oi.stalefrom <= :objstalenow))';
        $params += ['objstalenow' => time()];

        // Expired missions, this is mainly for challenges.
        $ors[] = '(mi.state IN (:chst1, :chst2, :chst3) AND mi.deadline > 0 AND mi.deadline <= :chdl)
               OR (mi.state = :chst4 AND mi.deadline = 0 AND mi.timecompleted < :chtc)';
        $params += [
            // Has deadline, and deadline expired.
            'chst1' => mission_instance::STATE_ASSIGNED,
            'chst2' => mission_instance::STATE_STARTED,
            'chst3' => mission_instance::STATE_COMPLETED,
            'chdl' => time(),
            // No deadline, but completed within a day (challenges are not ended immediately).
            'chst4' => mission_instance::STATE_COMPLETED,
            'chtc' => time() - DAYSECS,
        ];

        // Potentially completed, but for some reason not?
        $ors[] = 'mi.state = :mistarted AND (
                    mi.completionratio >= 1
                OR (SELECT SUM(oi.counter) - COUNT(o.countneeded) FROM {block_gearup_objective_inst} oi
                      JOIN {block_gearup_objective} o
                        ON o.id = oi.objectiveid
                     WHERE oi.missioninstid = mi.id
                  ) >= 0)';
        $params += ['mistarted' => mission_instance::STATE_STARTED];

        // Combine and fetch.
        $wheres[] = '((' . implode(') OR (', $ors) . '))';
        return $this->fetch_instances_select(implode(' AND ', $wheres), $params);
    }

    /**
     * Get stale objective instances.
     *
     * This only considers current objectives (active, not dormant, etc.).
     *
     * @return persisted_objective_instance[]
     */
    public function get_stale_objective_instances() {
        // TODO Make interface return an iterator, or traversable.
        global $DB;

        $oifields = objective_inst_model::get_sql_fields('oi', 'oi_');
        $ofields = objective_model::get_sql_fields('o', 'o_');

        $sql = "SELECT $oifields, $ofields
                  FROM {block_gearup_objective_inst} oi
                  JOIN {block_gearup_objective} o
                    ON o.id = oi.objectiveid
                  JOIN {block_gearup_mission_inst} mi
                    ON mi.id = oi.missioninstid
                  JOIN {block_gearup_mission} m
                    ON m.id = mi.missionid
                 WHERE oi.state = 0
                   AND (oi.stalefrom IS NOT NULL AND oi.stalefrom <= :nowstale)
                   AND mi.state = :state
                   AND m.state = :missionactive
              ORDER BY oi.stalefrom";
        $params = [
            'state' => mission_instance::STATE_STARTED,
            'missionactive' => mission::STATE_ACTIVE,
            'nowstale' => $this->clock->time(),
        ];
        $records = $DB->get_records_sql($sql, $params);

        return array_map(function($record) {
            $oipersistent = new objective_inst_model(0, objective_inst_model::extract_record($record, 'oi_'));
            $opersistent = new objective_model(0, objective_model::extract_record($record, 'o_'));
            $obj = new persisted_objective($opersistent, $this->objtyperesolver);
            $objinst = new persisted_objective_instance($oipersistent, $obj);
            return $objinst;
        }, $records);
    }

    /**
     * Get the visible instances of a type by subject ID.
     *
     * @param string $type The type interface.
     * @param int $subjectid The subject ID.
     * @param int|array $state The state, if any
     * @param context|null $context The context.
     * @param array|null $order The order.
     * @param int $offset The offet.
     * @param int $limit The limit.
     * @return mission_instance[]
     */
    public function get_type_instances_by_subject_id(string $type, int $subjectid, $state = null, ?context $context = null,
            ?array $order = null, $offset = 0, $limit = 0) {
        return $this->fetch_instances_by_subject_id($subjectid, mission_model::get_internal_type($type), $state, $context,
            $order, $offset, $limit);
    }

    /**
     * @deprecated Since Quest 1.6, do not use.
     */
    public function get_users(int $missionid, int $offset = 0, int $limit = 0, array $orderby = []) {
        debugging('The method get_users is deprecated, do not use.', DEBUG_DEVELOPER);
        return [];
    }

    public function get_users_from_query(user_query $query, int $offset = 0, int $limit = 0) {
        return iterator_to_array((new user_reader())->use_query($query)->list($offset, $limit));
    }

    /**
     * Get the visible instance types for a user in a context.
     *
     * @param int $userid The user.
     * @param context $context The context.
     * @return array The visible instance types (interfaces).
     */
    public function get_visible_instance_types_in(int $userid, context $context) {
        global $DB;
        [$where, $params] = $this->get_visible_instances_in_sql($userid, $context);
        $sql = "SELECT DISTINCT m.type
                  FROM {block_gearup_mission_inst} mi
                  JOIN {block_gearup_mission} m
                    ON m.id = mi.missionid
                 WHERE $where";
        $records = $DB->get_fieldset_sql($sql, $params);
        return array_values(array_filter(array_map(function($type) {
            try {
                return mission_model::convert_internal_type($type);
            } catch (\moodle_exception $e) {
                return null;
            }
        }, $records)));
    }

    /**
     * Get the SQL fragmentto find visible instances for a user in a context.
     *
     * @param int $subjectid The subject ID.
     * @param context $context The context.
     * @return array An array with the SQL fragment and the parameters.
     */
    protected function get_visible_instances_in_sql(int $subjectid, context $context) {
        // Any visible assigned, or not visible and at least started, except for challenges/streaks that are not visible when ended.
        $wheres = [
            "m.state = :missionactive",
            "mi.subjectid = :subjectid",
            "m.contextid = :contextid",
            "m.visibility = :visibility OR mi.state != :stateassigned",
            "mi.state != :stateended OR m.type NOT IN (:typechallenge, :typestreak)",
        ];
        $params = [
            'missionactive' => mission::STATE_ACTIVE,
            'subjectid' => $subjectid,
            'contextid' => $context->id,
            'visibility' => mission::VISIBLE_ALWAYS,
            'stateassigned' => mission_instance::STATE_ASSIGNED,
            'stateended' => mission_instance::STATE_ENDED,
            'typechallenge' => mission_model::TYPE_CHALLENGE,
            'typestreak' => mission_model::TYPE_STREAK,
        ];
        return ['((' . implode(') AND (', $wheres) . '))', $params];
    }

    /**
     * Whether has visible achievement instances in.
     *
     * @param int $subjectid
     * @param context $context
     * @return bool
     */
    public function has_achievement_instances_in(int $subjectid, context $context) {
        $sql = "m.state = ? AND m.type = ? AND m.contextid = ? AND mi.subjectid = ?";
        $params = [mission::STATE_ACTIVE, mission_model::TYPE_ACHIEVEMENT, $context->id, $subjectid];
        return $this->exists_instances_select($sql, $params);
    }

    public function has_achievements_in(context $context) {
        return $this->has_missions_in(mission_model::TYPE_ACHIEVEMENT, $context);
    }

    public function has_challenges_in(context $context) {
        return $this->has_missions_in(mission_model::TYPE_CHALLENGE, $context);
    }

    public function has_quests_in(context $context) {
        return $this->has_missions_in(mission_model::TYPE_QUEST, $context);
    }

    /**
     * Whether missions of type exist in context.
     *
     * @param int $type
     * @param context $context
     * @return bool
     */
    protected function has_missions_in(int $type, context $context) {
        return mission_model::record_exists_select('type = ? AND contextid = ?', [$type, $context->id]);
    }

    public function has_any_instances(mission_instance_query $query) {
        return (new mission_inst_reader())->use_query($query)->exists();
    }

    /**
     * Whether any instances exist for user in context.
     *
     * @param int $subjectid
     * @param context $context
     * @return bool
     */
    public function has_any_instances_in(int $subjectid, context $context) {
        $sql = "mi.subjectid = ? AND m.contextid = ?";
        $params = [$subjectid, $context->id];
        return $this->exists_instances_select($sql, $params);
    }

    public function has_any_visible_instances_in(int $subjectid, context $context) {
        [$sql, $params] = $this->get_visible_instances_in_sql($subjectid, $context);
        return $this->exists_instances_select($sql, $params);
    }

    public function has_non_started_instances(int $missionid) {
        $sql = "mi.missionid = ? AND mi.state = ?";
        $params = [$missionid, mission_instance::STATE_ASSIGNED];
        return $this->exists_instances_select($sql, $params);
    }

    public function has_outcomes(int $missionid, bool $visibleonly = false) {
        global $DB;
        $filters = ['missionid' => $missionid];
        if ($visibleonly) {
            $filters['visibility'] = 1;
        }
        return $DB->record_exists(outcome_model::TABLE, $filters);
    }

    public function is_assigned_mission(int $subjectid, int $missionid) {
        return mission_inst_model::record_exists_select('subjectid = ? AND missionid = ?', [$subjectid, $missionid]);
    }

    /**
     * Count the instances of a type by subject ID.
     *
     * This excludes the missions that are not active.
     *
     * @param int $type
     * @param int $subjectid
     * @param array|null $state
     * @param context|null $context
     * @return int
     */
    protected function count_instances_by_subject_id(int $type, int $subjectid, $state = null, ?context $context = null) {
        global $DB;
        $sql = "m.state = ? AND m.type = ? AND mi.subjectid = ?";
        $params = [mission::STATE_ACTIVE, $type, $subjectid];
        if ($state !== null) {
            list($insql, $inparams) = $DB->get_in_or_equal((array) $state);
            $sql .= " AND mi.state $insql";
            $params = array_merge($params, $inparams);
        }
        if ($context) {
            $sql .= " AND m.contextid = ?";
            $params[] = $context->id;
        }
        return $this->count_instances_select($sql, $params);
    }

    protected function count_instances_select($wheresql, $whereparams) {
        global $DB;
        $sql = "SELECT COUNT(1) AS n
                  FROM {block_gearup_mission_inst} mi
                  JOIN {block_gearup_mission} m
                    ON m.id = mi.missionid
                 WHERE $wheresql";
        $params = $whereparams;
        return $DB->count_records_sql($sql, $params);
    }

    protected function exists_instances_select($wheresql, $whereparams) {
        global $DB;

        $sql = "SELECT 1
                  FROM {block_gearup_mission_inst} mi
                  JOIN {block_gearup_mission} m
                    ON m.id = mi.missionid
                 WHERE $wheresql";
        $params = $whereparams;

        return $DB->record_exists_sql($sql, $params);
    }

    /**
     * Fetch the visible instances of a user.
     *
     * @param int $type
     * @param int $subjectid
     * @param array|null $state
     * @param context|null $context
     * @param array|null $order
     * @param int $offset
     * @param int $limit
     * @return mission_instance[]
     */
    protected function fetch_instances_by_subject_id(int $subjectid, ?int $type = null, $state = null, ?context $context = null,
            ?array $order = null, $offset = 0, $limit = 0) {
        global $DB;
        $sql = "mi.subjectid = ? AND m.state = ?";
        $params = [$subjectid, mission::STATE_ACTIVE];
        if ($type !== null) {
            $sql .= " AND m.type = ?";
            $params[] = $type;
        }
        if ($state !== null) {
            list($insql, $inparams) = $DB->get_in_or_equal((array) $state);
            $sql .= " AND mi.state $insql";
            $params = array_merge($params, $inparams);
        }
        if ($context) {
            $sql .= " AND m.contextid = ?";
            $params[] = $context->id;
        }
        $ordersql = null;
        if (!empty($order)) {
            $ordersql = implode(', ', $order);
        }
        return $this->fetch_instances_select($sql, $params, $ordersql, $offset, $limit);
    }

    protected function fetch_instances_select($wheresql, $whereparams, $order = null, $offset = 0, $limit = 0) {
        global $DB;

        $mfields = mission_model::get_sql_fields('m', 'm_');
        $mifields = mission_inst_model::get_sql_fields('mi', 'mi_');

        $ordersql = 'mi.timecreated DESC';
        if (!empty($order)) {
            $ordersql = $order;
        }

        // Just fetch the missions instances.
        // TODO Can we assume that the subject is a user?
        $sql = "SELECT $mifields, $mfields
                  FROM {block_gearup_mission_inst} mi
                  JOIN {block_gearup_mission} m
                    ON m.id = mi.missionid
                  JOIN {user} u
                    ON u.id = mi.subjectid
                 WHERE $wheresql
              ORDER BY $ordersql, mi.id";
        $params = $whereparams;

        $missions = [];
        $missioninsts = [];
        $recordset = $DB->get_recordset_sql($sql, $params, $offset, $limit);
        foreach ($recordset as $record) {
            $missionid = $record->m_id;
            if (!isset($missions[$missionid])) {
                $missions[$missionid] = new mission_model(0, mission_model::extract_record($record, 'm_'));
            }
            $missioninsts[$record->mi_id] = new mission_inst_model(0, mission_inst_model::extract_record($record, 'mi_'));
        }
        $recordset->close();

        return $this->make_instances_from_models($missioninsts, $missions);
    }

    /**
     * Make the instances from the result of a reader.
     *
     * @param array $records All the records.
     * @return mission_instance[]
     */
    protected function make_instances_from_reader_records(array $records) {
        if (empty($records)) {
            return [];
        }
        $missions = [];
        $missioninsts = [];
        foreach ($records as $record) {
            $mission = $record[mission_model::class];
            $missioninst = $record[mission_inst_model::class];
            $missions[$mission->get('id')] = $mission;
            $missioninsts[$missioninst->get('id')] = $missioninst;
        }
        return $this->make_instances_from_models($missioninsts, $missions);
    }

    /**
     * Make the instances from models.
     *
     * @param array $missioninsts Mission instance models.
     * @param array $missions Mission models.
     * @return mission_instance[]
     */
    protected function make_instances_from_models(array $missioninsts, array $missions) {
        global $DB;

        if (empty($missioninsts)) {
            return [];
        }

        $ofields = objective_model::get_sql_fields('o', 'o_');
        $oifields = objective_inst_model::get_sql_fields('oi', 'oi_');

        // Fetch the objectives.
        list($inidsql, $inidparams) = $DB->get_in_or_equal(array_keys($missions), SQL_PARAMS_NAMED);
        list($inmistidsql, $inmistidparams) = $DB->get_in_or_equal(array_keys($missioninsts), SQL_PARAMS_NAMED);
        $uniqid = $DB->sql_concat_join("'-'", ['o.id', 'COALESCE(oi.id, 0)']);
        $sql = "SELECT $uniqid AS uniqid, $ofields, $oifields
                  FROM {block_gearup_objective} o
             LEFT JOIN {block_gearup_objective_inst} oi
                    ON oi.objectiveid = o.id
                   AND oi.missioninstid $inmistidsql
                 WHERE o.missionid $inidsql";
        $params = $inidparams + $inmistidparams;

        $objectivesbymissionid = [];
        $objectivesbyid = [];
        $objinstsbymissioninstid = [];

        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $objid = $record->o_id;
            if (!isset($objectivesbyid[$objid])) {
                $opersistent = new objective_model(0, objective_model::extract_record($record, 'o_'));
                $obj = new persisted_objective($opersistent, $this->objtyperesolver);

                $objectivesbyid[$objid] = $obj;
                $objectivesbymissionid[$record->o_missionid] = $objectivesbymissionid[$record->o_missionid] ?? [];
                $objectivesbymissionid[$record->o_missionid][] = $obj;
            }

            $objinstid = $record->oi_id;
            if (!empty($objinstid)) {
                $missioninstid = $record->oi_missioninstid;
                $oipersistent = new objective_inst_model(0, objective_inst_model::extract_record($record, 'oi_'));
                $objinst = new persisted_objective_instance($oipersistent, $objectivesbyid[$objid]);
                $objinstsbymissioninstid[$missioninstid] = $objinstsbymissioninstid[$missioninstid] ?? [];
                $objinstsbymissioninstid[$missioninstid][] = $objinst;
            }
        }
        $recordset->close();

        // Combine the objects. We use an array_reduce to drop the keys that array_map would keep.
        return array_reduce($missioninsts, function($carry, $mi)
                use ($missions, $objinstsbymissioninstid, $objectivesbymissionid) {
            $m = $missions[$mi->get('missionid')];

            $mission = $this->make_mission_from_model($m, $objectivesbymissionid[$m->get('id')] ?? []);
            if (!$mission) {
                return $carry;
            }

            $objinsts = $objinstsbymissioninstid[$mi->get('id')] ?? [];
            $missioninst = new persisted_mission_instance($mi, $mission, $objinsts);
            $carry[] = $missioninst;
            return $carry;
        }, []);
    }

    /**
     * Make a mission from the model.
     *
     * @param mission_model $model The model.
     * @param Closure|array|null $objectivesorcallable The objectives.
     * @return mission|null
     */
    protected function make_mission_from_model(mission_model $model, $objectivesorcallable = null) {
        if ($objectivesorcallable !== null && !is_callable($objectivesorcallable) && !is_array($objectivesorcallable)) {
            throw new \coding_exception('Expected an null, array or callable but got something else.');
        }
        $getter = function() use ($model) {
            return $this->get_objectives($model->get('id'));
        };
        $objectivearg = $objectivesorcallable ? $objectivesorcallable : $getter;
        if ($model->is_achievement()) {
            return new persisted_achievement($model, $objectivearg);
        } else if ($model->is_challenge()) {
            return new persisted_challenge($model, $objectivearg);
        } else if ($model->is_quest()) {
            return new persisted_quest($model, $objectivearg);
        } else if ($model->is_streak()) {
            return new persisted_streak($model, $objectivearg);
        }
        debugging('Unknown type of mission ' . $model->geT('id'), DEBUG_DEVELOPER);
        return null;
    }
}
