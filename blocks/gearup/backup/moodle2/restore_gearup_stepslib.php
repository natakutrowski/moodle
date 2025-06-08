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
 * Restore.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_gearup\di;
use block_gearup\local\backup\restore_context;
use block_gearup\local\backup\restore_processor;
use block_gearup\local\model\mission;

/**
 * Restore.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_gearup_block_structure_step extends restore_structure_step {

    /** @var \restore_gearup_block_task */
    protected $task;
    /** @var int[] */
    protected $fixiterationofmissions = [];

    /**
     * Execution conditions.
     *
     * @return bool
     */
    protected function execute_condition() {
        if ($this->get_courseid() == SITEID) {
            return false;
        }
        return true;
    }

    /**
     * Define structure.
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('users');

        // Define each path.
        // Note for future self. I tried and tried to set a restore step on /block but I cannot make it work. It seems
        // to be skipped and maybe that's because of a `group_parent_exists` call from `prepare_pathelements`. I have
        // to admit that I have no idea... So we're hijacking the metadata path as a pre-execute step.
        $paths[] = new restore_path_element('block_gearup_metadata', '/block/metadata');
        $paths[] = new restore_path_element('block_gearup_mission', '/block/missions/mission');
        $paths[] = new restore_path_element('block_gearup_assigner', '/block/missions/mission/assigners/assigner');
        $paths[] = new restore_path_element('block_gearup_objective', '/block/missions/mission/objectives/objective');
        $paths[] = new restore_path_element('block_gearup_outcome', '/block/missions/mission/outcomes/outcome');

        if ($userinfo) {
            $paths[] = new restore_path_element('block_gearup_missioninst', '/block/missions/mission/missioninsts/missioninst');
            $paths[] = new restore_path_element('block_gearup_objinst',
                '/block/missions/mission/missioninsts/missioninst/objinsts/objinst');
        }

        return $paths;
    }
    /**
     * Process.
     */
    protected function process_block_gearup_metadata($data) {
        global $DB;

        $target = $this->get_task()->get_target();
        $coursecontextid = $this->task->get_course_contextid();

        // The backup target expects that all content is first being removed. Since deleting the block
        // instance does not delete the data itself, we must manually delete everything.
        if ($target == backup::TARGET_CURRENT_DELETING || $target == backup::TARGET_EXISTING_DELETING) {
            $this->log('block_gearup: deleting all data in target course', backup::LOG_DEBUG);

            // Removing objective instances.
            $DB->delete_records_subquery('block_gearup_objective_inst', 'missioninstid', 'id',
                'SELECT mi.id
                   FROM {block_gearup_mission_inst} mi
                   JOIN {block_gearup_mission} m
                     ON mi.missionid = m.id
                  WHERE m.contextid = ?', [$coursecontextid]);

            // Removing mission instances.
            $DB->delete_records_subquery('block_gearup_mission_inst', 'missionid', 'id',
                'SELECT m.id
                   FROM {block_gearup_mission} m
                  WHERE m.contextid = ?', [$coursecontextid]);

            // Removing mission objectives.
            $DB->delete_records_subquery('block_gearup_objective', 'missionid', 'id',
                'SELECT m.id
                   FROM {block_gearup_mission} m
                  WHERE m.contextid = ?', [$coursecontextid]);

            // Removing mission outcomes.
            $DB->delete_records_subquery('block_gearup_outcome', 'missionid', 'id',
                'SELECT m.id
                   FROM {block_gearup_mission} m
                  WHERE m.contextid = ?', [$coursecontextid]);

            // Removing mission assigners.
            $DB->delete_records_subquery('block_gearup_assigner', 'missionid', 'id',
                'SELECT m.id
                   FROM {block_gearup_mission} m
                  WHERE m.contextid = ?', [$coursecontextid]);

            // Removing missions.
            $DB->delete_records('block_gearup_mission', ['contextid' => $coursecontextid]);
        }
    }

    /**
     * Process.
     */
    protected function process_block_gearup_mission($data) {
        global $DB, $USER;

        $context = context_course::instance($this->get_courseid());
        $oldid = $data['id'];
        $data['contextid'] = $context->id;
        $data['usermodified'] = $this->get_mappingid('user', $data['usermodified'], $USER->id);
        $data['secret'] = (new mission())->get('secret');
        unset($data['id']);

        $newid = $DB->insert_record('block_gearup_mission', $data);
        $this->set_mapping('block_gearup_mission', $oldid, $newid);
    }

    /**
     * Process.
     */
    protected function process_block_gearup_assigner($data) {
        global $DB, $USER;
        $data['missionid'] = $this->get_new_parentid('block_gearup_mission');
        $data['usermodified'] = $this->get_mappingid('user', $data['usermodified'], $USER->id);
        unset($data['id']);
        $DB->insert_record('block_gearup_assigner', $data);
    }

    /**
     * Process.
     */
    protected function process_block_gearup_objective($data) {
        global $DB, $USER;

        $oldid = $data['id'];
        $data['missionid'] = $this->get_new_parentid('block_gearup_mission');
        $data['usermodified'] = $this->get_mappingid('user', $data['usermodified'], $USER->id);
        unset($data['id']);

        $newid = $DB->insert_record('block_gearup_objective', $data);
        $this->set_mapping('block_gearup_objective', $oldid, $newid);
    }

    /**
     * Process.
     */
    protected function process_block_gearup_outcome($data) {
        global $DB, $USER;
        $data['missionid'] = $this->get_new_parentid('block_gearup_mission');
        $data['usermodified'] = $this->get_mappingid('user', $data['usermodified'], $USER->id);
        unset($data['id']);
        $DB->insert_record('block_gearup_outcome', $data);
    }

    /**
     * Process.
     */
    protected function process_block_gearup_missioninst($data) {
        global $DB, $USER;

        $oldid = $data['id'];
        $data['missionid'] = $this->get_new_parentid('block_gearup_mission');
        $data['subjectid'] = $this->get_mappingid('user', $data['subjectid']);
        $data['usermodified'] = $this->get_mappingid('user', $data['usermodified'], $USER->id);
        unset($data['id']);

        // Handle case where backups (pre Quest 1.6) did not include the iteration field, which could cause a unique
        // constraint exception when restoring iterated mission instances. So, here we apply an iteration number based
        // on the time the user was recruited, that should be unique enough. In after_execute we fix these to recreate
        // a normal iteration sequence from 0 to n. Note that we cannot just do this when an exception occurs because
        // there is no guarantee that we restore the instances in the order in which they were assigned.
        if (!isset($data['iteration'])) {
            $data['iteration'] = $data['timecreated'];
            $this->fixiterationofmissions[] = $data['missionid'];
        }

        $newid = $DB->insert_record('block_gearup_mission_inst', $data);
        $this->set_mapping('block_gearup_missioninst', $oldid, $newid);
    }

    /**
     * Process.
     */
    protected function process_block_gearup_objinst($data) {
        global $DB, $USER;
        $data['missioninstid'] = $this->get_new_parentid('block_gearup_missioninst');
        $data['objectiveid'] = $this->get_mappingid('block_gearup_objective', $data['objectiveid']);
        $data['usermodified'] = $this->get_mappingid('user', $data['usermodified'], $USER->id);
        unset($data['id']);
        $DB->insert_record('block_gearup_objective_inst', $data);
    }

    /**
     * After execute.
     */
    protected function after_execute() {
        global $DB;

        $this->add_related_files('block_gearup', 'questnarrators', null, $this->task->get_old_course_contextid());
        $this->add_related_files('block_gearup', 'achievementbadges', null, $this->task->get_old_course_contextid());

        // Fix iteration of missions that were restored without the iteration field.
        foreach ($this->fixiterationofmissions as $missionid) {
            $missioninsts = $DB->get_recordset_select('block_gearup_mission_inst', 'missionid = ? AND iteration > 0', [$missionid],
                'subjectid ASC, iteration ASC, timecreated ASC');
            foreach ($missioninsts as $missioninst) {
                $DB->execute('UPDATE {block_gearup_mission_inst}
                                 SET iteration = (
                                    SELECT COALESCE(MAX(iteration), -1) + 1
                                      FROM {block_gearup_mission_inst}
                                     WHERE missionid = :missionid
                                       AND subjectid = :subjectid
                                       AND iteration < :iteration)
                               WHERE id = :missioninstid', [
                    'missioninstid' => $missioninst->id,
                    'missionid' => $missioninst->missionid,
                    'subjectid' => $missioninst->subjectid,
                    'iteration' => $missioninst->iteration,
                ]);
            }
            $missioninsts->close();
        }
    }

    /**
     * After restore.
     */
    protected function after_restore() {
        $restore = restore_context::from_structure_step($this);

        $processor = di::get('assigner_processor');
        if ($processor instanceof restore_processor) {
            $processor->process_restore($restore);
        }

        $processor = di::get('objective_processor');
        if ($processor instanceof restore_processor) {
            $processor->process_restore($restore);
        }

        $processor = di::get('outcome_processor');
        if ($processor instanceof restore_processor) {
            $processor->process_restore($restore);
        }
    }

}
