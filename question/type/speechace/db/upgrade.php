<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * speechace question type plugin upgrade code.
 *
 * @package    qtype
 * @subpackage speechace
 * @copyright  2017 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../speechacelib.php');

/**
 * Upgrade code for the speechace question type.
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_speechace_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017030800) {
        $table = new xmldb_table('qtype_speechace_opts');
        $field1 = new xmldb_field('scoringinfo', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'timelimit');
        $field2 = new xmldb_field('translationinfo', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'scoringinfo');

        // Conditionally add scoringinfo field
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }

        // Conditionally add translationinfo field
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        // Copy the question name to speechace scoringinfo
        $sql = "
                    FROM {qtype_speechace_opts} sp
                    JOIN {question} q ON sp.questionid = q.id
                    WHERE q.qtype = 'speechace'
                    AND ((sp.scoringinfo = '') or (sp.scoringinfo IS NULL))";

        $count = $DB->count_records_sql("
            SELECT COUNT(1) $sql");

        if ($count) {
            $progressbar = new progress_bar('speechace27', 500, true);
            $done = 0;

            $toupdate = $DB->get_recordset_sql("
                SELECT sp.id,
                       sp.scoringinfo,
                       q.name
                $sql");

            foreach ($toupdate as $data) {
                $progressbar->update($done, $count, "Updating speechace scoring info ($done/$count).");
                $updatedata = new stdClass();
                $updatedata->id = $data->id;
                $updatedata->scoringinfo = $data->name;
                $DB->update_record('qtype_speechace_opts', $updatedata);
                $done = $done + 1;
            }

            $toupdate->close();
        }

        // speechace savepoint reached
        upgrade_plugin_savepoint(true, 2017030800, 'qtype', 'speechace');
    }

    if ($oldversion < 2017031503) {
	    // Update question name and question text
        $sql = "
                    FROM {qtype_speechace_opts} sp
                    JOIN {question} q ON sp.questionid = q.id
                    WHERE q.qtype = 'speechace'
                    AND ((sp.scoringinfo != '') or (sp.scoringinfo IS NOT NULL))";

        $count = $DB->count_records_sql("SELECT COUNT(1) $sql");
        if ($count) {
            $progressbar = new progress_bar('speechace27', 500, true);
            $done = 0;

            $toupdate = $DB->get_recordset_sql("
                SELECT sp.id,
                       sp.questionid,
                       q.name,
                       q.questiontext,
                       sp.scoringinfo
                $sql");

            foreach ($toupdate as $data) {
                $progressbar->update($done, $count, "Updating speechace question name and text and scoringinfo ($done/$count).");
                $newScoringInfo = qtype_speechace_transform_scoringinfo_to_json($data->scoringinfo);
                if ($newScoringInfo) {
                    list($scoringText, $extra) = qtype_speechace_extract_scoring_text_from_question_text($data->questiontext);
                    $newQuestionText = null;
                    if ($scoringText) {
                        $newScoringInfo->text = $scoringText;
                        if ($extra) {
                            $newQuestionText = $extra;
                        } else {
                            $newQuestionText = '&nbsp;';
                        }
                    } else {
                        $scoringText = $newScoringInfo->text;
                    }
                    $questionNewData = new stdClass();
                    $questionNewData->id = $data->questionid;
                    $questionNewData->name = $scoringText;
                    if ($newQuestionText) {
                        $questionNewData->questiontext = $newQuestionText;
                    }
                    $DB->update_record('question', $questionNewData);

                    $speechaceQuestionNewData = new stdClass();
                    $speechaceQuestionNewData->id = $data->id;
                    $speechaceQuestionNewData->scoringinfo = json_encode($newScoringInfo);
                    $DB->update_record('qtype_speechace_opts', $speechaceQuestionNewData);
                }

                $done = $done + 1;
            }

            $toupdate->close();
        }

        upgrade_plugin_savepoint(true, 2017031503, 'qtype', 'speechace');
    }

    if ($oldversion < 2017032001) {
        $table = new xmldb_table('qtype_speechace_opts');

        $fields[] = new xmldb_field('responseformat');
        $fields[] = new xmldb_field('responsefieldlines');
        $fields[] = new xmldb_field('attachments');
        $fields[] = new xmldb_field('graderinfo');
        $fields[] = new xmldb_field('graderinfoformat');
        $fields[] = new xmldb_field('backimage');
        $fields[] = new xmldb_field('boardsize');
        $fields[] = new xmldb_field('translationinfo');
        $fields[] = new xmldb_field('timelimit');

        foreach ($fields as $field_item)
        if ($dbman->field_exists($table, $field_item)) {
            $dbman->drop_field($table, $field_item);
        }

        // delete files of graderinfo, backimage
        $fs = get_file_storage();
        $sql = "
                    FROM {files}
                    WHERE component = 'qtype_speechace'
                    AND ((filearea = 'graderinfo') or (filearea = 'backimage'))";
        $count = $DB->count_records_sql("SELECT COUNT(1) $sql");
        if ($count) {
            $progressbar = new progress_bar('speechace27', 500, true);
            $done = 0;
            $rs = $DB->get_recordset_sql("SELECT * $sql");
            foreach ($rs as $record) {
                $progressbar->update($done, $count, "Delete speechace graderinfo and backimage files ($done/$count).");
                $filerecord = $fs->get_file_instance($record);
                $filerecord->delete();
                $done = $done + 1;
            }
        }

	    upgrade_plugin_savepoint(true, 2017032001, 'qtype', 'speechace');
    }

    if ($oldversion < 2017051600) {
        $table = new xmldb_table('qtype_speechace_opts');
        $field1 = new xmldb_field('showanswer', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, QTYPE_SPEECHACE_SHOW_ANSWER_ALWAYS, 'scoringinfo');
        $field2 = new xmldb_field('showresult', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, QTYPE_SPEECHACE_SHOW_RESULT_IMMEDIATELY, 'showanswer');

        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }

        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        upgrade_plugin_savepoint(true, 2017051600, 'qtype', 'speechace');
    }

    if ($oldversion < 2017092801) {
        $table = new xmldb_table('qtype_speechace_opts');
        $field1 = new xmldb_field('dialect', XMLDB_TYPE_CHAR, '32', null, null, null, "", 'showresult');

        if (!$dbman->field_exists($table, $field1)){
            $dbman->add_field($table, $field1);
        }

        upgrade_plugin_savepoint(true, 2017092801, 'qtype', 'speechace');
    }

    if ($oldversion < 2017112114) {
        $table = new xmldb_table('qtype_speechace_opts');
        $field1 = new xmldb_field('referenceaudio', XMLDB_TYPE_CHAR, '32', null, null, null, "", 'dialect');

        if (!$dbman->field_exists($table, $field1)){
            $dbman->add_field($table, $field1);
        }

        upgrade_plugin_savepoint(true, 2017112114, 'qtype', 'speechace');
    }

    if ($oldversion < 2017121300) {
        $table = new xmldb_table('qtype_speechace_opts');
        $field1 = new xmldb_field('referenceaudio', XMLDB_TYPE_CHAR, '32', null, null, null, "", 'dialect');

        if ($dbman->field_exists($table, $field1)){
            $dbman->rename_field($table, $field1, 'showexpertaudio');
        }

        upgrade_plugin_savepoint(true, 2017121300, 'qtype', 'speechace');
    }

    if ($oldversion < 2017122300) {
        $table = new xmldb_table('qtype_speechace_opts');
        $field1 = new xmldb_field('shownumericscore', XMLDB_TYPE_CHAR, '32', null, null, null, "", 'showexpertaudio');

        if (!$dbman->field_exists($table, $field1)){
            $dbman->add_field($table, $field1);
        }

        upgrade_plugin_savepoint(true, 2017122300, 'qtype', 'speechace');
    }

    if ($oldversion < 2017122301) {
        $table = new xmldb_table('qtype_speechace_opts');
        $field1 = new xmldb_field('shownumericscore', XMLDB_TYPE_CHAR, '32', null, null, null, "", 'showexpertaudio');

        if (!$dbman->field_exists($table, $field1)){
            $dbman->drop_field($table, $field1);
        }

        upgrade_plugin_savepoint(true, 2017122301, 'qtype', 'speechace');
    }



    return true;
}
