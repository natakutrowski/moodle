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
 * Processor.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\processor;

use block_gearup\local\backup\backup_facade;
use block_gearup\local\backup\backup_processor;
use block_gearup\local\objective\resolver\resolver;
use block_gearup\local\objective\persisted_objective;
use block_gearup\local\objective\type\type_with_backup_handling;
use block_gearup\local\objective\type\type_with_update_after_restore;
use block_gearup\local\backup\restore_context;
use block_gearup\local\backup\restore_processor;
use block_gearup\local\mission\persisted_achievement;
use block_gearup\local\mission\persisted_challenge;
use block_gearup\local\mission\persisted_quest;
use block_gearup\local\mission\persisted_streak;
use block_gearup\local\model\objective as objective_model;
use block_gearup\local\model\mission as mission_model;

/**
 * Processor.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class default_processor implements backup_processor, restore_processor {

    /** @var resolver The type resolver. */
    protected $objectivetyperesolver;
    /** @var object The repository. */
    protected $repository;

    /**
     * Constructor.
     *
     * @param resolver $objectivetyperesolver The type resolver.
     * @param object $repository The repository.
     */
    public function __construct($objectivetyperesolver, $repository) {
        $this->objectivetyperesolver = $objectivetyperesolver;
        $this->repository = $repository;
    }

    public function process_backup(backup_facade $backup) {
        global $DB;

        // Filter the objectives that we can work with.
        $types = array_filter($this->objectivetyperesolver->get_types(), function ($type) {
            return $type instanceof type_with_backup_handling;
        });
        if (empty($types)) {
            return;
        }
        $typenames = array_values(array_map(function ($type) {
            return $this->objectivetyperesolver->get_type_name($type);
        }, $types));

        // Request the missions within the context, and compatible with event.
        [$typeinsql, $typeinparams] = $DB->get_in_or_equal($typenames, SQL_PARAMS_NAMED);
        $extraconditions = [
            "m.contextid = :contextid",
            "o.type $typeinsql",
        ];
        $extraparams = array_merge([
            'contextid' => $backup->get_course_context()->id,
        ], $typeinparams);

        // Do the things!
        foreach ($this->get_missions($extraconditions, $extraparams) as [$objective, $mission]) {
            $type = $objective->get_type();
            if (!$type instanceof type_with_backup_handling) {
                continue;
            }
            $type->extend_backup($backup, $objective, $mission);
        }
    }

    public function process_restore(restore_context $restore) {
        global $DB;

        // Filter the objectives that we can work with.
        $types = array_filter($this->objectivetyperesolver->get_types(), function ($type) {
            return $type instanceof type_with_update_after_restore;
        });
        if (empty($types)) {
            return;
        }
        $typenames = array_values(array_map(function ($type) {
            return $this->objectivetyperesolver->get_type_name($type);
        }, $types));

        // Request the missions within the context, and compatible with event.
        [$typeinsql, $typeinparams] = $DB->get_in_or_equal($typenames, SQL_PARAMS_NAMED);
        $extraconditions = [
            "m.contextid = :contextid",
            "o.type $typeinsql",
        ];
        $extraparams = array_merge([
            'contextid' => $restore->get_course_context()->id,
        ], $typeinparams);

        // Do the things!
        foreach ($this->get_missions($extraconditions, $extraparams) as [$objective, $mission]) {
            $type = $objective->get_type();
            if (!$type instanceof type_with_update_after_restore) {
                continue;
            }
            if (!$restore->get_mapping_old_id('block_gearup_mission', $mission->get_id())) {
                continue; // It was not just restored.
            }
            $type->update_after_restore($restore, $objective, $mission);
        }
    }

    /**
     * Get the missions.
     *
     * @param string[] $extraconditions SQL fragments.
     * @param array $extraparams SQL params.
     */
    protected function get_missions($extraconditions = null, $extraparams = null) {
        global $DB;

        $extrasql = '1=1';
        if (!empty($extraconditions)) {
            $extrasql = implode(' AND ', $extraconditions);
        }

        $ofields = objective_model::get_sql_fields('o', 'o_');
        $mfields = mission_model::get_sql_fields('m', 'm_');
        $sql = "SELECT $ofields, $mfields
                  FROM {block_gearup_objective} o
                  JOIN {block_gearup_mission} m
                    ON o.missionid = m.id
                 WHERE $extrasql";
        $params = $extraparams;

        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $missionmodel = new mission_model(0, mission_model::extract_record($record, 'm_'));
            $objectivemodel = new objective_model(0, objective_model::extract_record($record, 'o_'));

            $objectivesgetter = function () use ($missionmodel) {
                return $this->repository->get_objectives($missionmodel->get('id'));
            };
            if ($missionmodel->is_achievement()) {
                $mission = new persisted_achievement($missionmodel, $objectivesgetter);
            } else if ($missionmodel->is_challenge()) {
                $mission = new persisted_challenge($missionmodel, $objectivesgetter);
            } else if ($missionmodel->is_quest()) {
                $mission = new persisted_quest($missionmodel, $objectivesgetter);
            } else if ($missionmodel->is_streak()) {
                $mission = new persisted_streak($missionmodel, $objectivesgetter);
            } else {
                debugging('Unknown mission type when processing objectives.', DEBUG_DEVELOPER);
                continue;
            }

            $objective = new persisted_objective($objectivemodel, $this->objectivetyperesolver);
            yield [$objective, $mission];
        }

        $recordset->close();
    }
}
