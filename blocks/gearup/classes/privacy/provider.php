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
 * Provider
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\privacy;

use block_gearup\local\model\mission;
use block_gearup\local\model\mission_inst;
use block_gearup\local\model\objective;
use block_gearup\local\model\objective_inst;
use block_gearup\local\utils\json_utils;
use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Provider
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider ,
    \core_privacy\local\request\user_preference_provider {

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table('block_gearup_mission', [
            'type' => 'privacy:metadata:mission:type',
            'title' => 'privacy:metadata:mission:title',
            'usermodified' => 'privacy:metadata:generic:usermodified',
            'timecreated' => 'privacy:metadata:generic:timecreated',
            'timemodified' => 'privacy:metadata:generic:timemodified',
        ], 'privacy:metadata:mission');

        $collection->add_database_table('block_gearup_assigner', [
            'type' => 'privacy:metadata:assigner:type',
            'label' => 'privacy:metadata:assigner:label',
            'usermodified' => 'privacy:metadata:generic:usermodified',
            'timecreated' => 'privacy:metadata:generic:timecreated',
            'timemodified' => 'privacy:metadata:generic:timemodified',
        ], 'privacy:metadata:assigner');

        $collection->add_database_table('block_gearup_objective', [
            'type' => 'privacy:metadata:objective:type',
            'label' => 'privacy:metadata:objective:label',
            'usermodified' => 'privacy:metadata:generic:usermodified',
            'timecreated' => 'privacy:metadata:generic:timecreated',
            'timemodified' => 'privacy:metadata:generic:timemodified',
        ], 'privacy:metadata:objective');

        $collection->add_database_table('block_gearup_outcome', [
            'type' => 'privacy:metadata:outcome:type',
            'label' => 'privacy:metadata:outcome:label',
            'usermodified' => 'privacy:metadata:generic:usermodified',
            'timecreated' => 'privacy:metadata:generic:timecreated',
            'timemodified' => 'privacy:metadata:generic:timemodified',
        ], 'privacy:metadata:outcome');

        $collection->add_database_table('block_gearup_mission_inst', [
            'subjectid' => 'privacy:metadata:missioninst:subjectid',
            'state' => 'privacy:metadata:missioninst:state',
            'completionratio' => 'privacy:metadata:missioninst:completionratio',
            'timestarted' => 'privacy:metadata:missioninst:timestarted',
            'timecompleted' => 'privacy:metadata:missioninst:timecompleted',
            'timeended' => 'privacy:metadata:missioninst:timeended',
            'usermodified' => 'privacy:metadata:generic:usermodified',
            'timecreated' => 'privacy:metadata:generic:timecreated',
            'timemodified' => 'privacy:metadata:generic:timemodified',
        ], 'privacy:metadata:missioninst');

        $collection->add_database_table('block_gearup_objective_inst', [
            'subjectid' => 'privacy:metadata:objinst:subjectid',
            'state' => 'privacy:metadata:objinst:state',
            'counter' => 'privacy:metadata:objinst:counter',
            'statedata' => 'privacy:metadata:objinst:statedata',
            'usermodified' => 'privacy:metadata:generic:usermodified',
            'timecreated' => 'privacy:metadata:generic:timecreated',
            'timemodified' => 'privacy:metadata:generic:timemodified',
        ], 'privacy:metadata:objinst');

        return $collection;
    }

    /**
     * Export all user preferences.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        // The preference block_gearup_achievements_ctxids does not contain any personal data.
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "
            SELECT ctx.id
              FROM {block_gearup_mission_inst} mi
              JOIN {block_gearup_mission} m
                ON mi.missionid = m.id
              JOIN {context} ctx
                ON m.contextid = ctx.id
             WHERE mi.subjectid = :userid";
        $params = ['userid' => $userid];
        $contextlist->add_from_sql($sql, $params);

        // Persistent joy!
        $sql = "
            SELECT ctx.id
              FROM {block_gearup_mission} m
              JOIN {context} ctx ON m.contextid = ctx.id
         LEFT JOIN {block_gearup_objective} mo ON mo.missionid = m.id
         LEFT JOIN {block_gearup_outcome} mt ON mt.missionid = m.id
         LEFT JOIN {block_gearup_assigner} ma ON ma.missionid = m.id
         LEFT JOIN {block_gearup_mission_inst} mi ON mi.missionid = m.id
         LEFT JOIN {block_gearup_objective_inst} oi ON oi.missioninstid = mi.id
             WHERE m.usermodified = ?
                OR mo.usermodified = ?
                OR mt.usermodified = ?
                OR ma.usermodified = ?
                OR mi.usermodified = ?
                OR oi.usermodified = ?";
        $params = array_fill(0, 7, $userid);
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users.
     */
    public static function get_users_in_context(userlist $userlist) {
        $sql = "
            SELECT mi.subjectid
              FROM {block_gearup_mission_inst} mi
              JOIN {block_gearup_mission} m
                ON mi.missionid = m.id
             WHERE m.contextid = :contextid";
        $params = ['contextid' => $userlist->get_context()->id];
        $userlist->add_from_sql('subjectid', $sql, $params);

        // Persistent joy.
        $sql = "
            SELECT m.usermodified
              FROM {block_gearup_mission} m
             WHERE m.contextid = ?";
        $userlist->add_from_sql('usermodified', $sql, [$userlist->get_context()->id]);

        $sql = "
            SELECT mo.usermodified
              FROM {block_gearup_mission} m
         LEFT JOIN {block_gearup_objective} mo ON mo.missionid = m.id
             WHERE m.contextid = ?";
        $userlist->add_from_sql('usermodified', $sql, [$userlist->get_context()->id]);

        $sql = "
            SELECT mt.usermodified
              FROM {block_gearup_mission} m
         LEFT JOIN {block_gearup_outcome} mt ON mt.missionid = m.id
             WHERE m.contextid = ?";
        $userlist->add_from_sql('usermodified', $sql, [$userlist->get_context()->id]);

        $sql = "
            SELECT ma.usermodified
              FROM {block_gearup_mission} m
         LEFT JOIN {block_gearup_assigner} ma ON ma.missionid = m.id
             WHERE m.contextid = ?";
        $userlist->add_from_sql('usermodified', $sql, [$userlist->get_context()->id]);

        $sql = "
            SELECT mi.usermodified
              FROM {block_gearup_mission} m
         LEFT JOIN {block_gearup_mission_inst} mi ON mi.missionid = m.id
             WHERE m.contextid = ?";
        $userlist->add_from_sql('usermodified', $sql, [$userlist->get_context()->id]);

        $sql = "
            SELECT oi.usermodified
              FROM {block_gearup_mission} m
         LEFT JOIN {block_gearup_mission_inst} mi ON mi.missionid = m.id
         LEFT JOIN {block_gearup_objective_inst} oi ON oi.missioninstid = mi.id
             WHERE m.contextid = ?";
        $userlist->add_from_sql('usermodified', $sql, [$userlist->get_context()->id]);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $pluginname = get_string('pluginname', 'block_gearup');
        $contextids = $contextlist->get_contextids();
        list($insql, $inparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);

        $mfields = mission::get_sql_fields('m', 'm_');
        $mifields = mission_inst::get_sql_fields('mi', 'mi_');
        $ofields = objective::get_sql_fields('o', 'o_');
        $oifields = objective_inst::get_sql_fields('oi', 'oi_');

        // Fetch the missions.
        $sql = "SELECT $mifields, $mfields
                  FROM {block_gearup_mission_inst} mi
                  JOIN {block_gearup_mission} m
                    ON m.id = mi.missionid
                 WHERE m.contextid $insql
                   AND mi.subjectid = :userid
              ORDER BY m.contextid";
        $params = $inparams + ['userid' => $user->id];

        $path = [$pluginname, get_string('privacy:path:missions', 'block_gearup')];
        $flushlogs = function($contextid, $data) use ($path) {
            $context = context::instance_by_id($contextid);
            writer::with_context($context)->export_data($path, (object) ['data' => $data]);
        };

        $data = [];
        $lastcontextid = null;
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $mission = mission::extract_record($record, 'm_');
            $missioninst = mission_inst::extract_record($record, 'mi_');

            if ($lastcontextid && $lastcontextid != $mission->contextid) {
                $flushlogs($lastcontextid, $data);
                $data = [];
            }

            $sql = "SELECT $oifields, $ofields
                      FROM {block_gearup_objective_inst} oi
                      JOIN {block_gearup_objective} o
                        ON o.id = oi.objectiveid
                     WHERE oi.missioninstid = :missioninstid
                       AND oi.subjectid = :userid
                  ORDER BY o.id";
            $params = ['missioninstid' => $missioninst->id, 'userid' => $user->id];

            $objs = [];
            $objectiverecords = $DB->get_records_sql($sql, $params);
            foreach ($objectiverecords as $objectiverecord) {
                $objs[] = [
                    objective::extract_record($objectiverecord, 'o_'),
                    objective_inst::extract_record($objectiverecord, 'oi_'),
                ];
            }

            $data[] = (object) [
                'name' => $mission->title,
                'subjectid' => transform::user($missioninst->subjectid),
                'completionratio' => $missioninst->completionratio,
                'timeassigned' => $missioninst->timecreated ? transform::datetime($missioninst->timecreated) : '-',
                'timestarted' => $missioninst->timestarted ? transform::datetime($missioninst->timestarted) : '-',
                'timecompleted' => $missioninst->timecompleted ? transform::datetime($missioninst->timecompleted) : '-',
                'timeended' => $missioninst->timeended ? transform::datetime($missioninst->timeended) : '-',
                'objectives' => array_values(array_map(function($data) {
                    [$obj, $objinst] = $data;
                    return (object) [
                        'type' => $obj->type,
                        'name' => $obj->label,
                        'counter' => $objinst->counter,
                        'countneeded' => $obj->countneeded,
                        'statedata' => $objinst->statedata,
                    ];
                }, $objs)),
            ];

            $lastcontextid = $mission->contextid;
        }
        $recordset->close();

        // Flush the last iteration.
        if ($lastcontextid) {
            $flushlogs($lastcontextid, $data);
        }

    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        $sql = "SELECT mi.id
                  FROM {block_gearup_mission_inst} mi
                  JOIN {block_gearup_mission} m
                    ON mi.missionid = m.id
                 WHERE m.contextid = ?";
        $DB->delete_records_subquery('block_gearup_objective_inst', 'missioninstid', 'id', $sql, [$context->id]);

        $sql = "SELECT m.id
                  FROM {block_gearup_mission} m
                 WHERE m.contextid = ?";
        $DB->delete_records_subquery('block_gearup_mission_inst', 'missionid', 'id', $sql, [$context->id]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $contextids = $contextlist->get_contextids();
        if (empty($contextids)) {
            return;
        }

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);

        $sql = "DELETE FROM {block_gearup_objective_inst}
                      WHERE subjectid = :userid AND missioninstid IN (
                            SELECT mi.id
                              FROM {block_gearup_mission_inst} mi
                              JOIN {block_gearup_mission} m
                                ON mi.missionid = m.id
                             WHERE m.contextid $contextsql)";
        $DB->execute($sql, ['userid' => $contextlist->get_user()->id] + $contextparams);

        $sql = "DELETE FROM {block_gearup_mission_inst}
                      WHERE subjectid = :userid AND missionid IN (
                            SELECT m.id
                              FROM {block_gearup_mission} m
                             WHERE m.contextid $contextsql)";
        $DB->execute($sql, ['userid' => $contextlist->get_user()->id] + $contextparams);

        // Agressively delete this.
        unset_user_preference('block_gearup_achievements_ctxids', $contextlist->get_user());
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($userssql, $usersparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $sql = "DELETE FROM {block_gearup_objective_inst}
                      WHERE subjectid $userssql AND missioninstid IN (
                            SELECT mi.id
                              FROM {block_gearup_mission_inst} mi
                              JOIN {block_gearup_mission} m
                                ON mi.missionid = m.id
                             WHERE m.contextid = :contextid)";
        $DB->execute($sql, ['contextid' => $userlist->get_context()->id] + $usersparams);

        $sql = "DELETE FROM {block_gearup_mission_inst}
                      WHERE subjectid $userssql AND missionid IN (
                            SELECT m.id
                              FROM {block_gearup_mission} m
                             WHERE m.contextid = :contextid)";
        $DB->execute($sql, ['contextid' => $userlist->get_context()->id] + $usersparams);

        // Agressively delete this.
        foreach ($userids as $userid) {
            unset_user_preference('block_gearup_achievements_ctxids', $userid);
        }

    }

}
