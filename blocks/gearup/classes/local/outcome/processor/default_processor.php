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

namespace block_gearup\local\outcome\processor;

use block_gearup\local\backup\backup_facade;
use block_gearup\local\backup\backup_processor;
use block_gearup\local\outcome\resolver\resolver;
use block_gearup\local\outcome\persisted_outcome;
use block_gearup\local\outcome\type\type_with_backup_handling;
use block_gearup\local\outcome\type\type_with_update_after_restore;
use block_gearup\local\backup\restore_context;
use block_gearup\local\backup\restore_processor;
use block_gearup\local\mission\persisted_achievement;
use block_gearup\local\mission\persisted_challenge;
use block_gearup\local\mission\persisted_quest;
use block_gearup\local\mission\persisted_streak;
use block_gearup\local\model\outcome as outcome_model;
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
    protected $outcometyperesolver;
    /** @var object The repository. */
    protected $repository;

    /**
     * Constructor.
     *
     * @param resolver $outcometyperesolver The type resolver.
     * @param object $repository The repository.
     */
    public function __construct($outcometyperesolver, $repository) {
        $this->outcometyperesolver = $outcometyperesolver;
        $this->repository = $repository;
    }

    public function process_backup(backup_facade $backup) {
        global $DB;

        // Filter the outcomes that we can work with.
        $types = array_filter($this->outcometyperesolver->get_types(), function($type) {
            return $type instanceof type_with_backup_handling;
        });
        if (empty($types)) {
            return;
        }
        $typenames = array_values(array_map(function($type) {
            return $this->outcometyperesolver->get_type_name($type);
        }, $types));

        // Request the missions within the context, and compatible with event.
        list($typeinsql, $typeinparams) = $DB->get_in_or_equal($typenames, SQL_PARAMS_NAMED);
        $extraconditions = [
            "m.contextid = :contextid",
            "o.type $typeinsql",
        ];
        $extraparams = array_merge([
            'contextid' => $backup->get_course_context()->id,
        ], $typeinparams);

        // Do the things!
        foreach ($this->get_missions($extraconditions, $extraparams) as [$outcome, $mission]) {
            $type = $outcome->get_type();
            if (!$type instanceof type_with_backup_handling) {
                continue;
            }
            $type->extend_backup($backup, $outcome, $mission);
        }
    }

    public function process_restore(restore_context $restore) {
        global $DB;

        // Filter the outcomes that we can work with.
        $types = array_filter($this->outcometyperesolver->get_types(), function($type) {
            return $type instanceof type_with_update_after_restore;
        });
        if (empty($types)) {
            return;
        }
        $typenames = array_values(array_map(function($type) {
            return $this->outcometyperesolver->get_type_name($type);
        }, $types));

        // Request the missions within the context, and compatible with event.
        list($typeinsql, $typeinparams) = $DB->get_in_or_equal($typenames, SQL_PARAMS_NAMED);
        $extraconditions = [
            "m.contextid = :contextid",
            "o.type $typeinsql",
        ];
        $extraparams = array_merge([
            'contextid' => $restore->get_course_context()->id,
        ], $typeinparams);

        // Do the things!
        foreach ($this->get_missions($extraconditions, $extraparams) as [$outcome, $mission]) {
            $type = $outcome->get_type();
            if (!$type instanceof type_with_update_after_restore) {
                continue;
            }
            if (!$restore->get_mapping_old_id('block_gearup_mission', $mission->get_id())) {
                continue; // It was not just restored.
            }
            $type->update_after_restore($restore, $outcome, $mission);
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

        $ofields = outcome_model::get_sql_fields('o', 'o_');
        $mfields = mission_model::get_sql_fields('m', 'm_');
        $sql = "SELECT $ofields, $mfields
                  FROM {block_gearup_outcome} o
                  JOIN {block_gearup_mission} m
                    ON o.missionid = m.id
                 WHERE $extrasql";
        $params = $extraparams;

        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $missionmodel = new mission_model(0, mission_model::extract_record($record, 'm_'));
            $outcomemodel = new outcome_model(0, outcome_model::extract_record($record, 'o_'));

            $objectivesgetter = function() use ($missionmodel) {
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
                debugging('Unknown mission type when processing outcomes.', DEBUG_DEVELOPER);
                continue;
            }

            $outcome = new persisted_outcome($outcomemodel, $this->outcometyperesolver);
            yield [$outcome, $mission];
        }

        $recordset->close();
    }
}
