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
 * Assigner processor.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\assigner\processor;

use block_gearup\local\assigner\resolver\resolver;
use block_gearup\local\assigner\persisted_assigner;
use block_gearup\local\assigner\type\type_with_backup_handling;
use block_gearup\local\assigner\type\type_with_event_consumption;
use block_gearup\local\assigner\type\type_with_update_after_restore;
use block_gearup\local\backup\backup_facade;
use block_gearup\local\backup\backup_processor;
use block_gearup\local\backup\restore_context;
use block_gearup\local\backup\restore_processor;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\persisted_achievement;
use block_gearup\local\mission\persisted_challenge;
use block_gearup\local\mission\persisted_quest;
use block_gearup\local\mission\persisted_streak;
use block_gearup\local\model\assigner as assigner_model;
use block_gearup\local\model\mission as mission_model;

/**
 * Assigner processor.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class default_processor implements all_processor, backup_processor, event_processor, restore_processor {

    /** @var resolver The type resolver. */
    protected $assignertyperesolver;
    /** @var object The repository. */
    protected $repository;
    /** @var oeprator The mission operator. */
    protected $missionoperator;

    /**
     * Constructor.
     *
     * @param resolver $assignertyperesolver The type resolver.
     * @param object $repository The repository.
     * @param oeprator $missionoperator The operator.
     */
    public function __construct($assignertyperesolver, $repository, $missionoperator) {
        $this->assignertyperesolver = $assignertyperesolver;
        $this->repository = $repository;
        $this->missionoperator = $missionoperator;
    }

    public function process_all() {
        // TODO Should we skip assigners that are event-compatible? Assigners
        // are always full synced once in the next cron run, so we probably don't
        // need to keep syncing them if they have other ways to keep in sync.
        foreach ($this->get_missions(['a.enabled = 1'], []) as [$assigner, $mission]) {
            $this->missionoperator->sync_assigner($mission, $assigner);
        }
    }

    public function process_backup(backup_facade $backup) {
        global $DB;

        // Filter the assigners that we can work with.
        $types = array_filter($this->assignertyperesolver->get_types(), function($type) {
            return $type instanceof type_with_backup_handling;
        });
        if (empty($types)) {
            return;
        }
        $typenames = array_values(array_map(function($type) {
            return $this->assignertyperesolver->get_type_name($type);
        }, $types));

        // Request the missions within the context, and compatible with event.
        list($typeinsql, $typeinparams) = $DB->get_in_or_equal($typenames, SQL_PARAMS_NAMED);
        $extraconditions = [
            "m.contextid = :contextid",
            "a.type $typeinsql",
        ];
        $extraparams = array_merge([
            'contextid' => $backup->get_course_context()->id,
        ], $typeinparams);

        // Do the things!
        foreach ($this->get_missions($extraconditions, $extraparams) as [$assigner, $mission]) {
            $type = $assigner->get_type();
            if (!$type instanceof type_with_backup_handling) {
                continue;
            }
            $type->extend_backup($backup, $assigner, $mission);
        }
    }

    public function process_event(\core\event\base $event) {
        global $DB;
        if ($event->is_restored()) {
            return;
        } else if (!$event->get_context()) {
            return;
        }

        // Filter the assigners that can work with events.
        $types = $this->assignertyperesolver->get_types_compatible_with_event($event);
        if (empty($types)) {
            return;
        }
        $typenames = array_values(array_map(function($type) {
            return $this->assignertyperesolver->get_type_name($type);
        }, $types));

        // TODO We're fetching more assigners than we need here, we could filter out
        // out the assigners that are in another context, the type should implement
        // an early filter mechanism.
        list($typeinsql, $typeinparams) = $DB->get_in_or_equal($typenames, SQL_PARAMS_NAMED);
        $extraconditions = ['a.enabled = 1', "a.type $typeinsql"];
        $extraparams = $typeinparams;

        foreach ($this->get_missions($extraconditions, $extraparams) as [$assigner, $mission]) {
            $type = $assigner->get_type();
            if ($type instanceof type_with_event_consumption) {
                if (!$type->is_event_passing_constraints($event, $assigner)) {
                    continue;
                }
                $this->missionoperator->sync_assigner_from_event($mission, $assigner, $event);
            }
        }
    }

    public function process_restore(restore_context $restore) {
        global $DB;

        // Filter the assigners that we can work with.
        $types = array_filter($this->assignertyperesolver->get_types(), function($type) {
            return $type instanceof type_with_update_after_restore;
        });
        if (empty($types)) {
            return;
        }
        $typenames = array_values(array_map(function($type) {
            return $this->assignertyperesolver->get_type_name($type);
        }, $types));

        // Request the missions within the context, and compatible with event.
        list($typeinsql, $typeinparams) = $DB->get_in_or_equal($typenames, SQL_PARAMS_NAMED);
        $extraconditions = [
            "m.contextid = :contextid",
            "a.type $typeinsql",
        ];
        $extraparams = array_merge([
            'contextid' => $restore->get_course_context()->id,
        ], $typeinparams);

        // Do the things!
        foreach ($this->get_missions($extraconditions, $extraparams) as [$assigner, $mission]) {
            $type = $assigner->get_type();
            if (!$type instanceof type_with_update_after_restore) {
                continue;
            }
            if (!$restore->get_mapping_old_id('block_gearup_mission', $mission->get_id())) {
                continue; // It was not just restored.
            }
            $type->update_after_restore($restore, $assigner, $mission);
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

        $afields = assigner_model::get_sql_fields('a', 'a_');
        $mfields = mission_model::get_sql_fields('m', 'm_');
        $sql = "SELECT $afields, $mfields
                  FROM {block_gearup_assigner} a
                  JOIN {block_gearup_mission} m
                    ON a.missionid = m.id
                 WHERE m.state = :mstateactive
                   AND $extrasql";
        $params = ['mstateactive' => mission::STATE_ACTIVE] + $extraparams;

        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $missionmodel = new mission_model(0, mission_model::extract_record($record, 'm_'));
            $assignermodel = new assigner_model(0, assigner_model::extract_record($record, 'a_'));

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
                debugging('Unknown mission type when processing assigners.', DEBUG_DEVELOPER);
                continue;
            }

            $assigner = new persisted_assigner($assignermodel, $this->assignertyperesolver);
            yield [$assigner, $mission];
        }

        $recordset->close();
    }
}
