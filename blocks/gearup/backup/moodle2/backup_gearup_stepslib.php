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

use block_gearup\di;
use block_gearup\local\backup\backup_facade;
use block_gearup\local\backup\backup_processor;

/**
 * Backup.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_gearup_block_structure_step extends backup_block_structure_step {

    /**
     * Define structure.
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('users');

        $context = context::instance_by_id($this->get_task()->get_contextid());
        $coursecontext = $context->get_course_context(true);

        // Define each element.
        $metadata = new backup_nested_element('metadata', [], ['coursecontextid']);
        $missions = new backup_nested_element('missions');
        $mission = new backup_nested_element('mission', ['id'], [
            'type', 'state', 'title', 'description', 'instructions', 'feedback', 'repeatcount', 'secret', 'startmode',
            'timelimit', 'visibility', 'visual', 'voiceid', 'usermodified', 'timecreated', 'timemodified']);
        $objectives = new backup_nested_element('objectives');
        $objective = new backup_nested_element('objective', ['id'], [
            'type', 'label', 'countneeded', 'configdata', 'supportingurl', 'usermodified', 'timecreated', 'timemodified']);
        $outcomes = new backup_nested_element('outcomes');
        $outcome = new backup_nested_element('outcome', ['id'], [
            'type', 'label', 'visibility', 'configdata', 'usermodified', 'timecreated', 'timemodified']);
        $assigners = new backup_nested_element('assigners');
        $assigner = new backup_nested_element('assigner', ['id'], [
            'type', 'label', 'enabled', 'configdata', 'usermodified', 'timecreated', 'timemodified']);
        $missioninsts = new backup_nested_element('missioninsts');
        $missioninst = new backup_nested_element('missioninst', ['id'], [
            'subjectid', 'state', 'iteration', 'counter', 'completionratio', 'deadline', 'needsattention',
            'timestarted', 'timecompleted', 'timeended', 'usermodified', 'timecreated', 'timemodified']);
        $objinsts = new backup_nested_element('objinsts');
        $objinst = new backup_nested_element('objinst', ['objectiveid'], [
            'subjectid', 'state', 'counter', 'statedata', 'dormantuntil', 'stalefrom',
            'usermodified', 'timecreated', 'timemodified']);

        // Prepare the structure.
        $gearup = $this->prepare_block_structure($metadata);

        $assigners->add_child($assigner);
        $objectives->add_child($objective);
        $outcomes->add_child($outcome);

        $mission->add_child($assigners);
        $mission->add_child($objectives);
        $mission->add_child($outcomes);

        $missions->add_child($mission);
        $gearup->add_child($missions);

        if ($userinfo) {
            $objinsts->add_child($objinst);
            $missioninst->add_child($objinsts);
            $missioninsts->add_child($missioninst);
            $mission->add_child($missioninsts);
        }

        // Define sources.
        // Is there really no other way to use the course context here? I've tried but this is the only way that it worked.
        $mission->set_source_sql('SELECT * FROM {block_gearup_mission} WHERE contextid = ?', [['sqlparam' => $coursecontext->id]]);
        $assigner->set_source_table('block_gearup_assigner', ['missionid' => backup::VAR_PARENTID]);
        $objective->set_source_table('block_gearup_objective', ['missionid' => backup::VAR_PARENTID]);
        $outcome->set_source_table('block_gearup_outcome', ['missionid' => backup::VAR_PARENTID]);
        $metadata->set_source_array([(object) ['coursecontextid' => $coursecontext->id]]);

        if ($userinfo) {
            $missioninst->set_source_table('block_gearup_mission_inst', ['missionid' => backup::VAR_PARENTID]);
            $objinst->set_source_table('block_gearup_objective_inst', ['missioninstid' => backup::VAR_PARENTID]);
        }

        // Annotations.
        $missioninst->annotate_ids('user', 'subjectid');

        // File annotations.
        $gearup->annotate_files('block_gearup', 'questnarrators', null, $coursecontext->id);
        $gearup->annotate_files('block_gearup', 'achievementbadges', null, $coursecontext->id);

        // Persistent joy.
        $mission->annotate_ids('user', 'usermodified');
        $objective->annotate_ids('user', 'usermodified');
        $outcome->annotate_ids('user', 'usermodified');
        $assigner->annotate_ids('user', 'usermodified');
        $missioninst->annotate_ids('user', 'usermodified');
        $objinst->annotate_ids('user', 'usermodified');

        $this->internal_backup_process();

        // Return the root element.
        return $gearup;
    }

    protected function internal_backup_process() {
        $backup = backup_facade::from_structure_step($this);

        $processor = di::get('assigner_processor');
        if ($processor instanceof backup_processor) {
            $processor->process_backup($backup);
        }

        $processor = di::get('objective_processor');
        if ($processor instanceof backup_processor) {
            $processor->process_backup($backup);
        }

        $processor = di::get('outcome_processor');
        if ($processor instanceof backup_processor) {
            $processor->process_backup($backup);
        }
    }
}
