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

use block_gearup\local\utils\json_utils;

/**
 * Upgrade all the things!
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade function.
 *
 * @param int $oldversion Old version.
 * @return true
 */
function xmldb_block_gearup_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021100351) {

        // Define field timelimit to be added to block_gearup_mission.
        $table = new xmldb_table('block_gearup_mission');
        $field = new xmldb_field('timelimit', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'startmode');

        // Conditionally launch add field timelimit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2021100351, 'gearup');
    }

    if ($oldversion < 2021100352) {

        // Define field deadline to be added to block_gearup_mission_inst.
        $table = new xmldb_table('block_gearup_mission_inst');
        $field = new xmldb_field('deadline', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'completionratio');

        // Conditionally launch add field deadline.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2021100352, 'gearup');
    }

    if ($oldversion < 2021100353) {

        // Define index statedeadlineidx (not unique) to be added to block_gearup_mission_inst.
        $table = new xmldb_table('block_gearup_mission_inst');
        $index = new xmldb_index('statedeadlineidx', XMLDB_INDEX_NOTUNIQUE, ['state', 'deadline']);

        // Conditionally launch add index statedeadlineidx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2021100353, 'gearup');
    }

    if ($oldversion < 2021100354) {

        // Define field iteration to be added to block_gearup_mission_inst.
        $table = new xmldb_table('block_gearup_mission_inst');
        $field = new xmldb_field('iteration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'missionid');

        // Conditionally launch add field iteration.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2021100354, 'gearup');
    }

    if ($oldversion < 2021100355) {

        // Define index subjmissioniteridx (unique) to be added to block_gearup_mission_inst.
        $table = new xmldb_table('block_gearup_mission_inst');
        $index = new xmldb_index('subjmissioniteridx', XMLDB_INDEX_UNIQUE, ['subjectid', 'missionid', 'iteration']);

        // Conditionally launch add index subjmissioniteridx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2021100355, 'gearup');
    }

    if ($oldversion < 2023032201) {

        // Rename field repeat on table block_gearup_mission to repeatcount.
        $table = new xmldb_table('block_gearup_mission');
        $field = new xmldb_field('repeat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'feedback');

        // Launch rename field to repeatcount, only if repeat exists.
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'repeatcount');
        }

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2023032201, 'gearup');
    }

    if ($oldversion < 2023032202) {

        // Define field repeatcount to be added to block_gearup_mission.
        $table = new xmldb_table('block_gearup_mission');
        $field = new xmldb_field('repeatcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'feedback');

        // Conditionally launch add field repeatcount.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2023032202, 'gearup');
    }

    if ($oldversion < 2024010600) {

        // Changing precision of field visual on table block_gearup_mission to (280).
        $table = new xmldb_table('block_gearup_mission');
        $field = new xmldb_field('visual', XMLDB_TYPE_CHAR, '280', null, null, null, null, 'visibility');

        // Launch change of precision for field visual.
        $dbman->change_field_precision($table, $field);

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2024010600, 'gearup');
    }

    if ($oldversion < 2024052901) {

        // Define field supportingurl to be added to block_gearup_objective.
        $table = new xmldb_table('block_gearup_objective');
        $field = new xmldb_field('supportingurl', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'configdata');

        // Conditionally launch add field supportingurl.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2024052901, 'gearup');
    }

    if ($oldversion < 2025012900) {

        // Define field counter to be added to block_gearup_mission_inst.
        $table = new xmldb_table('block_gearup_mission_inst');
        $field = new xmldb_field('counter', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'state');

        // Conditionally launch add field counter.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2025012900, 'gearup');
    }

    if ($oldversion < 2025020200) {

        // Convert preference to needs attention on instances.
        $records = $DB->get_records('user_preferences', ['name' => 'block_gearup_achievements']);
        foreach ($records as $record) {
            $missioninstids = json_utils::decode_to_list($record->value);
            if (empty($missioninstids)) {
                continue;
            }
            [$insql, $inparams] = $DB->get_in_or_equal($missioninstids);
            $DB->set_field_select('block_gearup_mission_inst', 'needsattention', 1, "id $insql", $inparams);
        }

        // Remove all preferences.
        $DB->delete_records('user_preferences', ['name' => 'block_gearup_achievements']);

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2025020200, 'gearup');
    }

    if ($oldversion < 2025020202) {

        // Flag the contexts in which users have achievements to announce.
        $ctxids = $DB->sql_group_concat('m.contextid', ',', 'm.contextid');
        $sql = "SELECT mi.subjectid, $ctxids AS ctxids
                  FROM {block_gearup_mission_inst} mi
                  JOIN {block_gearup_mission} m
                    ON m.id = mi.missionid
                  WHERE m.type = 0
                    AND mi.state = 10
                    AND mi.needsattention = 1
               GROUP BY mi.subjectid";
        $recordset = $DB->get_recordset_sql($sql, []);
        foreach ($recordset as $record) {
            $ctxids = array_unique(array_map('intval', explode(',', $record->ctxids)));
            set_user_preference('block_gearup_achievements_ctxids', json_utils::encode_as_list($ctxids), $record->subjectid);
        }

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2025020202, 'gearup');
    }

    if ($oldversion < 2025060300) {

        // Define field voiceid to be added to block_gearup_mission.
        $table = new xmldb_table('block_gearup_mission');
        $field = new xmldb_field('voiceid', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'visual');

        // Conditionally launch add field voiceid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2025060300, 'gearup');
    }

    if ($oldversion < 2025061503) {

        $task = new \block_gearup\task\sync_metadata_adhoc();
        \core\task\manager::queue_adhoc_task($task, true);

        // Gearup savepoint reached.
        upgrade_block_savepoint(true, 2025061503, 'gearup');
    }

    return true;
}
