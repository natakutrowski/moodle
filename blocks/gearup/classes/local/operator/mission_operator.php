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
 * Operator.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\operator;

use block_gearup\di;
use block_gearup\local\action\achievement_unlocked;
use block_gearup\local\action\action;
use block_gearup\local\action\challenge_completed;
use block_gearup\local\action\challenge_failed;
use block_gearup\local\action\quest_completed;
use block_gearup\local\action\quest_finished;
use block_gearup\local\action\quest_started;
use block_gearup\local\action\streak_reached;
use block_gearup\local\assigner\assigner;
use block_gearup\local\assigner\persisted_assigner;
use block_gearup\local\assigner\type\type_with_action_consumption;
use block_gearup\local\assigner\type\type_with_event_consumption;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\mission\persisted_mission;
use block_gearup\local\mission\persisted_mission_instance;
use block_gearup\local\model\mission as mission_model;
use block_gearup\local\model\mission_inst;
use block_gearup\local\model\objective_inst;
use block_gearup\local\model\objective as objective_model;
use block_gearup\local\model\outcome as outcome_model;
use block_gearup\local\model\assigner as assigner_model;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\persisted_objective;
use block_gearup\local\objective\persisted_objective_instance;
use block_gearup\local\outcome\persisted_outcome;
use block_gearup\local\utils\json_utils;
use block_gearup\local\utils\time_utils;
use core_date;
use DateInterval;
use DateTimeImmutable;

/**
 * Operator.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_operator {

    /** @var \block_gearup\local\repository\repository */
    protected $repository;
    /** @var \block_gearup\local\mission\helper */
    protected $missionhelper;
    /** @var \core\clock */
    protected $clock;
    protected $completionratiocalculator;
    /** @var objective_operator */
    protected $objectiveoperator;

    public function __construct(
        $repository,
        $missionhelper,
        $completionratiocalculator,
        objective_operator $objectiveoperator
    ) {
        $this->clock = di::get('clock');
        $this->repository = $repository;
        $this->missionhelper = $missionhelper;
        $this->completionratiocalculator = $completionratiocalculator;
        $this->objectiveoperator = $objectiveoperator;
    }

    /**
     * Archive a mission.
     */
    public function archive_mission(mission $mission) {
        if (!$mission instanceof persisted_mission) {
            throw new \coding_exception('Support for non-persisted mission has not been implemented.');
        } else if (!$this->missionhelper->is_archivable($mission)) {
            throw new \coding_exception('The mission cannot be archived.');
        }
        $persistent = $mission->get_persistent();
        $persistent->set('state', mission::STATE_ARCHIVED);
        $persistent->save();
    }

    public function assign_mission(mission $mission, $userid): mission_instance {
        if (!$this->missionhelper->is_active($mission)) {
            throw new \coding_exception('The mission is not active.');
        }
        if ($this->repository->is_assigned_mission($userid, $mission->get_id())) {
            // We cannot assign the mission more than once using this method. If a mission needs to
            // be assigned multiple times, see calls to self::create_iterated_instance for info.
            // TODO Perhaps if there all instances are ended we can automatically created an iteration.
            // Although this might be worse for performance, and thus maybe another "assign_or_iterate"
            // should be created for cases we want to handle iterations automatically.
            return $this->repository->get_instance_by_subject_id($mission->get_id(), $userid);
        }

        $missioninst = $this->create_instance($mission, $userid);
        $this->post_create($missioninst);
        return $missioninst;
    }

    protected function apply_instance_outcomes(mission_instance $missioninst) {
        $outcomes = $this->repository->get_outcomes($missioninst->get_mission()->get_id());
        foreach ($outcomes as $outcome) {
            $type = $outcome->get_type();
            $type->apply($outcome, $missioninst);
        }
    }

    /**
     * Broadcast an action.
     *
     * This is internal logic to centralise the ability for the mission operator to
     * broadcast actions that would be related to the missions.
     *
     * @param action $action The action,
     */
    protected function broadcast_action(action $action) {
        // TODO Perhaps we should not process this right here. We should also not use the
        // di object right here but the action processor require the mission operator.
        // In the future we can inject the action processor after instantiating the
        // mission operator and only broadcast the actions when we validate that we
        // have the action processor.
        di::get('action_processor')->process_action($action);

        // The assigner processor is handled separately, for now.
        $assignerproc = di::get('assigner_processor');
        if ($assignerproc instanceof \block_gearup\local\assigner\processor\action_processor) {
            $assignerproc->process_action($action);
        }
    }

    public function complete_instance(mission_instance $missioninst) {
        if (!$this->missionhelper->is_active($missioninst)) {
            throw new \coding_exception('The mission is not active.');
        } else if ($this->missionhelper->has_completed($missioninst)) {
            throw new \coding_exception('The instance is already completed');
        }

        if (!$this->missionhelper->has_started($missioninst)) {
            $this->start_instance($missioninst);
        }

        $missioninst->set_state($missioninst::STATE_COMPLETED);
        $missioninst->set_time_completed($this->clock->now());

        // Quests require attention when they're completed.
        if ($this->missionhelper->is_a_quest($missioninst)) {
            $missioninst->set_needs_attention(true);
            $this->broadcast_action(new quest_completed($missioninst));
        }

        // Achievements are automatically ended.
        if ($this->missionhelper->is_an_achievement($missioninst)) {
            $missioninst->set_needs_attention(true);

            // Flag that we have achievements in that context.
            try {
                $prefname = 'block_gearup_achievements_ctxids';
                $ctxids = json_utils::decode_to_list(get_user_preferences($prefname, '[]', $missioninst->get_subject_id()));
                $ctxids[] = $missioninst->get_mission()->get_context()->id;
                $ctxids = array_unique(array_map('intval', $ctxids));
                set_user_preference($prefname, json_utils::encode_as_list($ctxids), $missioninst->get_subject_id());
            } catch (\Exception $e) {
                debugging($e->getMessage(), DEBUG_DEVELOPER);
            }

            $this->end_instance($missioninst);
        }

        // Challenges outcomes are applied right away.
        if ($this->missionhelper->is_a_challenge($missioninst)) {
            if ($missioninst->get_completion_ratio() >= 1) {
                $this->apply_instance_outcomes($missioninst);
                $this->broadcast_action(new challenge_completed($missioninst));
            } else {
                $this->broadcast_action(new challenge_failed($missioninst));
            }
        }

        // Streaks increment their internal counter if they were successful.
        if ($this->missionhelper->is_a_streak($missioninst)) {
            if ($missioninst->get_completion_ratio() >= 1) {
                $this->increment_counter($missioninst);
            }
        }
    }

    protected function create_instance(mission $mission, $userid): mission_instance {
        // TODO Prevent multiple instances of the same mission.
        // TODO Wrap in a transaction.
        // TODO Should the operator know about the database? It seems that the resolver would instead.
        $persistent = new mission_inst(0, (object) [
            'subjectid' => $userid,
            'missionid' => $mission->get_id(),
        ]);
        $persistent->create();
        return new persisted_mission_instance($persistent, $mission, []);
    }

    protected function create_iterated_instance(mission_instance $missioninst): mission_instance {
        $mission = $this->missionhelper->get_mission($missioninst);
        $hasnotended = mission_inst::record_exists_select('subjectid = ? AND missionid = ? AND state != ?', [
            $missioninst->get_subject_id(), $mission->get_id(), mission_instance::STATE_ENDED]);

        if ($hasnotended) {
            throw new \coding_exception('Cannot create an iterated instance when there is an ongoing one.');
        }

        $persistent = new mission_inst(0, (object) [
            'subjectid' => $missioninst->get_subject_id(),
            'missionid' => $mission->get_id(),
            'iteration' => $missioninst->get_iteration_number() + 1,
        ]);
        $persistent->create();
        // Challenges next start time should be relative to their last deadline, if there is a time limit.
        return new persisted_mission_instance($persistent, $mission, []);
    }

    protected function create_objective_instance(mission_instance $missioninst, objective $objective) {
        // TODO Should the operator know about the database? I think not.
        $objinstmodel = new objective_inst(0, (object) [
            'missioninstid' => $missioninst->get_id(),
            'subjectid' => $missioninst->get_subject_id(),
            'objectiveid' => $objective->get_id(),
        ]);
        $objinstmodel->create();
        $oi = new persisted_objective_instance($objinstmodel, $objective);

        // Challenges and streaks do not resume from previous states.
        if (!$this->missionhelper->is_a_challenge($missioninst)
                && !$this->missionhelper->is_a_streak($missioninst)
        ) {
            $this->objectiveoperator->initialise_state_on_instance($missioninst, $oi);
        }

        return $oi;
    }

    public function delete_instance(mission_instance $missioninst) {
        // No archive/active check here. For now we will trust the calling code
        // because there are instances where we want to be able to delete stuff even
        // when something has been archived.

        // TODO Wrap in transaction.
        // TODO Should the operator know about the database, I think not!
        $id = $missioninst->get_id();
        if ($missioninst instanceof persisted_mission_instance) {
            $mi = $missioninst->get_persistent();
        } else {
            $mi = new mission_inst($id);
        }
        $ois = objective_inst::get_records(['missioninstid' => $id]);
        foreach ($ois as $oi) {
            $oi->delete();
        }
        $mi->delete();
    }

    public function delete_mission(mission $mission) {
        global $DB;
        // TODO Wrap in transaction.
        // TODO Should the operator know about the database, I think not!
        $missionid = $mission->get_id();
        $objids = $DB->get_fieldset_select(objective_model::TABLE, 'id', 'missionid = ?', [$missionid]);
        if (!empty($objids)) {
            [$insql, $inparams] = $DB->get_in_or_equal($objids);
            $DB->delete_records_select(objective_inst::TABLE, "id $insql", $inparams);
        }
        $DB->delete_records(mission_inst::TABLE, ['missionid' => $missionid]);
        $DB->delete_records(objective_model::TABLE, ['missionid' => $missionid]);
        $DB->delete_records(outcome_model::TABLE, ['missionid' => $missionid]);
        $DB->delete_records(assigner_model::TABLE, ['missionid' => $missionid]);
        $DB->delete_records(mission_model::TABLE, ['id' => $missionid]);
    }

    /**
     * Duplicate a mission.
     *
     * This requires the mission to have been saved previously.
     *
     * @param mission $origmission The mission.
     * @param array|null $options An array of options.
     */
    public function duplicate_mission(mission $origmission, ?array $options = null) {
        global $DB;

        if (!$origmission instanceof persisted_mission || !$origmission->get_id()) {
            throw new \coding_exception('Support for non-persisted mission has not been implemented.');
        } else if ($origmission->get_state() === mission::STATE_WIZARD) {
            throw new \coding_exception('The mission is not ready to be duplicated.');
        }

        $options = array_merge([
            'includeobjectives' => true,
            'includeoutcomes' => true,
            'includeassigners' => true,
        ], $options ?? []);

        $tx = $DB->start_delegated_transaction();

        // Clone the mission.
        $origrecord = $origmission->get_persistent()->to_record();
        $origrecord->state = mission::STATE_ACTIVE;
        unset($origrecord->id);
        unset($origrecord->secret);
        $mission = new mission_model(0, $origrecord);
        $mission->save();

        // Clone the objectives.
        if ($options['includeobjectives']) {
            foreach ($this->repository->get_objectives($origmission->get_id()) as $origobj) {
                if (!$origobj instanceof persisted_objective) {
                    continue;
                }
                $origobjrecord = $origobj->get_persistent()->to_record();
                unset($origobjrecord->id);
                $obj = new objective_model(0, $origobjrecord);
                $obj->set('missionid', $mission->get('id'));
                $obj->save();
            }
        }

        // Clone the outcomes.
        if ($options['includeoutcomes']) {
            foreach ($this->repository->get_outcomes($origmission->get_id()) as $origoc) {
                if (!$origoc instanceof persisted_outcome) {
                    continue;
                }
                $origocrecord = $origoc->get_persistent()->to_record();
                unset($origocrecord->id);
                $outcome = new outcome_model(0, $origocrecord);
                $outcome->set('missionid', $mission->get('id'));
                $outcome->save();
            }
        }

        // Clone the assigners.
        if ($options['includeassigners']) {
            foreach ($this->repository->get_assigners($origmission->get_id()) as $origassigner) {
                if (!$origassigner instanceof persisted_assigner) {
                    continue;
                }
                $origassignerrecord = $origassigner->get_persistent()->to_record();
                unset($origassignerrecord->id);
                $assigner = new assigner_model(0, $origassignerrecord);
                $assigner->set('missionid', $mission->get('id'));
                $assigner->save();
            }
        }

        $tx->allow_commit();

        return $this->repository->get_mission($mission->get('id'));
    }

    /**
     * Evaluate whether the mission should be completed.
     *
     * @param mission_instance $missioninst
     */
    public function evaluate_instance(mission_instance $missioninst) {

        // Evaluation is forbidden when the mission is not active.
        if (!$this->missionhelper->is_active($missioninst)) {
            return;
        }

        $time = $this->clock->time();
        $ischallenge = $this->missionhelper->is_a_challenge($missioninst);
        $deadline = $missioninst->get_deadline();
        $deadlinepassed = $deadline ? $deadline->getTimestamp() <= $time : false;

        // If it's not yet completed, and there is no deadline or its not passed yet, then compute
        // the state which may cause the mission to be completed, and outcomes to be awarded.
        if (!$this->missionhelper->has_completed($missioninst) && !$deadlinepassed) {
            $this->update_instance_state($missioninst);
        }

        // At times, we must evaluation the mission to be ended.
        if (!$this->missionhelper->is_ended($missioninst)) {
            if ($deadlinepassed) {
                // Missions with deadline that has expired as ended.
                $this->end_instance($missioninst);

            } else if ($ischallenge && !$deadline && $this->missionhelper->is_completed($missioninst)
                    && $missioninst->get_time_completed()->getTimestamp() < $time - DAYSECS
            ) {
                // Missions without deadline that have been completed more than a day ago are to be finished.
                $this->end_instance($missioninst);
            }
        }
    }

    public function end_instance(mission_instance $missioninst) {
        global $DB;

        if (!$this->missionhelper->is_active($missioninst)) {
            throw new \coding_exception('The mission is not active.');
        } else if ($this->missionhelper->is_ended($missioninst)) {
            throw new \coding_exception('The instance has already been ended.');
        }

        $ischallenge = $this->missionhelper->is_a_challenge($missioninst);
        $isstreak = $this->missionhelper->is_a_streak($missioninst);

        // Special edge-case for ending streaks.
        if ($isstreak) {

            // We restart the streaks that were completed on time, so long as they're not being re-evaluated too late.
            // We also restart the streaks that never were completed a single time.
            $nextdeadline = $this->calculate_deadline($missioninst, $missioninst->get_deadline()->add(new DateInterval('PT1S')));
            $shouldreset = ($missioninst->get_completion_ratio() >= 1 && $nextdeadline > $this->clock->now())
                || $missioninst->get_counter() <= 0;

            if ($shouldreset) {
                $tx = $DB->start_delegated_transaction();

                // The final objective IDs.
                $objids = array_map(function ($obj) {
                    return $obj->get_id();
                }, $missioninst->get_mission()->get_objectives());

                // Reset the objectives instances, and delete the extraneous ones.
                $objinsts = [];
                foreach ($missioninst->get_objective_instances() as $objinst) {
                    if (!in_array($objinst->get_objective()->get_id(), $objids)) {
                        // TODO This is not how we should be deleting an objective instance.
                        if ($objinst instanceof persisted_objective_instance) {
                            $objinst->get_persistent()->delete();
                        } else {
                            throw new \coding_exception('Unable to handle non-persisted objective instance.');
                        }
                    }
                    $objinst->reset();
                    $objinsts[] = $objinst;
                }

                // Adding the objectives that we're missing.
                $objidsininst = array_map(function ($objinst) {
                    return $objinst->get_objective()->get_id();
                }, $objinsts);
                $missingobjids = array_diff($objids, $objidsininst);
                foreach ($missioninst->get_mission()->get_objectives() as $objective) {
                    if (!in_array($objective->get_id(), $missingobjids)) {
                        continue;
                    }
                    $objinst = $this->create_objective_instance($missioninst, $objective);
                    $objinsts[] = $objinst;
                }
                $missioninst->set_objective_instances($objinsts);

                // Reset the mission.
                $missioninst->set_completion_ratio(0);
                $missioninst->set_time_ended(new DateTimeImmutable('@0'));
                $missioninst->set_time_completed(new DateTimeImmutable('@0'));
                $missioninst->set_state($missioninst::STATE_STARTED);
                $deadline = $nextdeadline > $this->clock->now() ? $nextdeadline : $this->calculate_deadline($missioninst);
                $missioninst->set_deadline($deadline);

                // Commit the changes.
                $DB->commit_delegated_transaction($tx);

                // Finally, re-evaluate.
                $this->evaluate_instance($missioninst);
                return;
            }
        }

        // TODO Wrap all in a transaction?
        if (!$this->missionhelper->is_completed($missioninst)) {
            $this->complete_instance($missioninst);
        }
        $missioninst->set_state($missioninst::STATE_ENDED);
        $missioninst->set_time_ended($this->clock->now());

        if (!$ischallenge && !$isstreak) {
            // Challenges outcomes apply upon completion, not ending.
            // Streaks do not support outcomes upon ending.
            $this->apply_instance_outcomes($missioninst);
        }

        $action = null;
        if ($this->missionhelper->is_a_quest($missioninst)) {
            $action = new quest_finished($missioninst);
        } else if ($this->missionhelper->is_an_achievement($missioninst)) {
            $action = new achievement_unlocked($missioninst);
        }

        if ($action) {
            $this->broadcast_action($action);
        }

        if ($this->missionhelper->is_repeating($missioninst)) {
            $newinst = $this->create_iterated_instance($missioninst);
            $this->post_create($newinst);
        }
    }

    public function finish_instance(mission_instance $missioninst) {
        // Less permissive mission for public use.
        if (!$this->missionhelper->is_active($missioninst)) {
            throw new \coding_exception('The mission is not active.');
        } else if (!$this->missionhelper->is_completed($missioninst)) {
            throw new \coding_exception('The instance is not completed.');
        }
        $this->end_instance($missioninst);
    }

    public function increment_counter(mission_instance $missioninst, $by = 1) {
        if (!$this->missionhelper->is_active($missioninst)) {
            throw new \coding_exception('The mission is not active.');
        } else if (!$this->missionhelper->is_a_streak($missioninst)) {
            throw new \coding_exception('Counter not compatible with this type.');
        }

        // Record that the streak just started when it was completed,
        // or now if someone is forcing the increment before its completion.
        if ($missioninst->get_counter() <= 0) {
            $timecompleted = $this->clock->now();
            if ($this->missionhelper->is_completed($missioninst)) {
                $timecompleted = $missioninst->get_time_completed();
            }
            $missioninst->set_time_started($timecompleted);
        }
        $missioninst->increment_counter($by);

        // When this is a streak, and the counter is greater than 1, we broadcast an action. Our only
        // objective tracking this is expecting a minimum counter of 2, so we can skip those before.
        if ($this->missionhelper->is_a_streak($missioninst) && $missioninst->get_counter() > 1) {
            $this->broadcast_action(new streak_reached($missioninst));
        }
    }

    protected function post_create(mission_instance $missioninst) {
        $mission = $missioninst->get_mission();

        // Optional, and discoverable quests should catch the recruit's attention.
        if ($this->missionhelper->is_a_quest($mission) &&
                ($this->missionhelper->is_optional($mission) || $this->missionhelper->is_discoverable($mission))
        ) {
            $missioninst->set_needs_attention(true);
        }

        // Automatically starts instances.
        if ($this->missionhelper->is_an_achievement($mission)
                || $this->missionhelper->is_a_challenge($mission)
                || $this->missionhelper->is_a_streak($mission)
                || $mission->get_start_mode() === $mission::START_ALWAYS
        ) {

            $this->start_instance($missioninst);
        }
    }

    public function reset_instance(mission_instance $missioninst) {
        global $DB;

        if (!$missioninst instanceof persisted_mission_instance) {
            return;
        }

        if (!$this->missionhelper->is_active($missioninst)) {
            throw new \coding_exception('The mission is not active.');
        } else if ($this->missionhelper->is_ended($missioninst) && $this->missionhelper->is_repeating($missioninst)) {
            // Repeating missions rotate automatically when ended, in which case we cannot reset them.
            throw new \coding_exception('Cannot reset the instance of the mission as it has ended.');
        }

        $mi = $missioninst->get_persistent();
        $id = $mi->get('id');
        $tx = $DB->start_delegated_transaction();

        // Delete existing objectives.
        $ois = objective_inst::get_records(['missioninstid' => $id]);
        foreach ($ois as $oi) {
            $oi->delete();
        }

        // Override the previous mission instance with a fresh new one, only keeping the ID and iteration number.
        $newmi = new mission_inst(0, (object) [
            'subjectid' => $missioninst->get_subject_id(),
            'missionid' => $missioninst->get_mission()->get_id(),
            'iteration' => $missioninst->get_iteration_number(),
        ]);
        $newmi->set('id', $id);
        $newmi->update();

        // Carry-on as if we had just assign the user.
        $newinst = new persisted_mission_instance($newmi, $missioninst->get_mission(), []);
        $this->post_create($newinst);

        $tx->allow_commit();
    }

    public function start_instance(mission_instance $missioninst) {
        if (!$this->missionhelper->is_active($missioninst)) {
            throw new \coding_exception('The mission is not active.');
        } else if ($this->missionhelper->has_started($missioninst)) {
            throw new \coding_exception('The instance has already been started.');
        }

        $missioninst->set_state($missioninst::STATE_STARTED);
        $missioninst->set_time_started($this->clock->now());

        // Quests require attention when they're started, because a new dialogue is shown.
        $mission = $this->missionhelper->get_mission($missioninst);
        $isquest = $this->missionhelper->is_a_quest($mission);
        if ($isquest) {
            $missioninst->set_needs_attention(true);
        }

        // Missions with a time limit.
        $timelimit = $mission->get_time_limit();
        if ($timelimit > 0) {
            $missioninst->set_deadline($this->calculate_deadline($mission));
        }

        $objinsts = [];
        $mission = $missioninst->get_mission();
        $evaluateinstance = false;
        foreach ($mission->get_objectives() as $objective) {
            $oi = $this->create_objective_instance($missioninst, $objective);
            $objinsts[] = $oi;
            if ($oi->get_counter() != 0) {
                $evaluateinstance = true;
            }
        }

        $missioninst->set_objective_instances($objinsts);

        if ($isquest) {
            $this->broadcast_action(new quest_started($missioninst));
        }

        if ($evaluateinstance) {
            $this->evaluate_instance($missioninst);
        }
    }

    public function sync_assigner(mission $mission, assigner $assigner) {
        if (!$this->missionhelper->is_active($mission)) {
            throw new \coding_exception('The mission is not active.');
        }

        $type = $assigner->get_type();
        [$userssql, $usersparams] = $type->get_elligible_users_sql($assigner, $mission);
        $this->sync_assigner_with_users_sql($mission, $assigner, $userssql, $usersparams);
    }

    public function sync_assigner_from_action(mission $mission, assigner $assigner, action $action) {
        if (!$this->missionhelper->is_active($mission)) {
            throw new \coding_exception('The mission is not active.');
        }
        $sql = "SELECT {$action->get_user_id()} AS id";
        $this->sync_assigner_with_users_sql($mission, $assigner, $sql, []);
    }

    public function sync_assigner_from_event(mission $mission, assigner $assigner, \core\event\base $event) {
        if (!$this->missionhelper->is_active($mission)) {
            throw new \coding_exception('The mission is not active.');
        }

        $type = $assigner->get_type();
        if (!$type instanceof type_with_event_consumption) {
            return;
        }
        [$userssql, $usersparams] = $type->get_elligile_users_sql_from_event($event, $assigner, $mission);
        $this->sync_assigner_with_users_sql($mission, $assigner, $userssql, $usersparams);
    }

    protected function sync_assigner_with_users_sql(
        mission $mission,
        assigner $assigner,
        $userssql,
        $usersparams
    ) {
        global $CFG, $DB;

        $stateparams = [];
        $stateendedsql = '1=1';
        // Assigners currently cannot re-recruit users for repeated missions when their ongoing instance
        // is deleted and they have ended instances. That is because the SQL query does not find them,
        // but more importantly because the assign_mission method prevents creating instances if any
        // already exist. We would need to fix the assign_mission before there is any point in changing
        // this query.
        // if ($this->missionhelper->is_repeating($mission)) {
        // $stateendedsql = 'mi.state != :stateended';
        // $stateparams['stateended'] = mission_instance::STATE_ENDED;
        // }

        // Now, find the users that do not have an instance.
        $sql = "SELECT DISTINCT u.id AS userid
                  FROM {user} u
                 WHERE u.id IN ($userssql)
                   AND u.confirmed = 1
                   AND u.deleted = 0
                   AND u.suspended = 0
                   AND u.id != :guestid
                   AND NOT EXISTS (
                        SELECT 1
                          FROM {block_gearup_mission_inst} mi
                         WHERE mi.subjectid = u.id
                           AND mi.missionid = :missionid
                           AND $stateendedsql)";
        $params = array_merge($usersparams, $stateparams, [
            'missionid' => $mission->get_id(),
            'guestid' => $CFG->siteguest,
        ]);

        // TODO The performance of this is terrible, we should bulk assign.
        // TODO Should the operator know about the database?
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $this->assign_mission($mission, $record->userid);
        }
        $recordset->close();
    }

    public function update_instance_objectives(mission_instance $missioninst) {
        if (!$this->missionhelper->is_active($missioninst)) {
            throw new \coding_exception('The mission is not active.');
        } else if (!$this->missionhelper->has_started($missioninst)) {
            throw new \coding_exception('The instance has not started.');
        } else if ($this->missionhelper->has_completed($missioninst)) {
            throw new \coding_exception('The instance is already completed.');
        }

        $objids = array_map(function ($obj) {
            return $obj->get_id();
        }, $missioninst->get_mission()->get_objectives());

        /** @var int[] */
        $hasids = array_map(function ($objinst) {
            return $objinst->get_objective()->get_id();
        }, $missioninst->get_objective_instances());

        $addedois = [];
        $missingids = array_diff($objids, $hasids);
        foreach ($missingids as $missingid) {
            $objective = $missioninst->get_mission()->get_objective($missingid);
            $addedois[] = $this->create_objective_instance($missioninst, $objective);
        }

        // Objectives should not be deleted when the mission has been completed. This code here is just
        // to accomodate for inconsistencies but should not be relied upon because it means that there is
        // a time during which the objective instance lives without its objective.
        $toremoveids = array_diff($hasids, $objids);
        foreach ($toremoveids as $removeid) {
            $objinst = $missioninst->get_instance_of_objective($removeid);
            if ($objinst->is_completed()) {
                continue;
            }
            // TODO This is not how we should be deleting an objective instance.
            if ($objinst instanceof persisted_objective_instance) {
                $objinst->get_persistent()->delete();
            }
        }

        // Update the list of objective instances.
        $objinsts = array_filter(array_merge($missioninst->get_objective_instances(), $addedois), function ($oi) use ($objids) {
            return in_array($oi->get_objective()->get_id(), $objids);
        });
        $missioninst->set_objective_instances($objinsts);

        // Re-evaluate the objectives because the config could have changed. This can have the drawback of
        // completing objectives, and instantly completing missions, but for now that's going to be expected.
        foreach ($missioninst->get_objective_instances() as $objinst) {
            $this->objectiveoperator->reevaluate_state($objinst);
        }
        $this->evaluate_instance($missioninst);
    }

    protected function update_instance_state(mission_instance $missioninst) {
        if ($this->missionhelper->has_completed($missioninst)) {
            return;
        }

        $iscompleted = true;
        $objinsts = $missioninst->get_objective_instances();
        $nobjs = count($objinsts);
        foreach ($objinsts as $objinst) {
            $iscompleted = $iscompleted && $objinst->is_completed();
            if (!$iscompleted) {
                break;
            }
        }

        $missioninst->set_completion_ratio($this->completionratiocalculator->calculate_completion_ratio($missioninst));

        // We need at least one objective to consider this completed!
        if ($nobjs >= 1 && $iscompleted) {
            $this->complete_instance($missioninst);
        }
    }

    protected function calculate_deadline($missionorinst, ?DateTimeImmutable $reftime = null) {
        $mission = $this->missionhelper->get_mission($missionorinst);
        $timelimit = $mission->get_time_limit();
        if (!$timelimit) {
            return null;
        }

        // When missions repeat, their deadline is relative.
        $reftime = ($reftime ?? $this->clock->now())->setTimezone(core_date::get_server_timezone_object());
        if ($this->missionhelper->is_repeating($mission)) {
            $ismonth = $timelimit % (DAYSECS * 30) === 0;
            $isweek = $timelimit % WEEKSECS === 0;
            $reftime = $reftime->setTime(23, 59, 59, 0);
            if ($ismonth) {
                $reftime = $reftime->setDate($reftime->format('Y'), $reftime->format('m'), 1);
            } else if ($isweek) {
                $reftime = $reftime->sub(new DateInterval('P' . (1 + ($reftime->format('N') - 1)) . 'D'));
            } else {
                // Yesterday 1 second before midnight.
                $reftime = $reftime->setTime(0, 0, 0, 0)->sub(new DateInterval('PT1S'));
            }
        }

        if ($timelimit === time_utils::DAILY_WEEKDAY) {
            // We get the next weekday, but we cannot use it directly as the time is being reset.
            $nextweekday = $reftime->modify('next weekday');
            $deadline = $reftime->setDate($nextweekday->format('Y'), $nextweekday->format('m'), $nextweekday->format('d'));
        } else {
            $days = (int) max(1, floor($timelimit / DAYSECS));
            if ($days === 30) {
                if ($this->missionhelper->is_repeating($mission)) {
                    $deadline = $reftime->modify("last day of this month");
                } else {
                    $deadline = $reftime->add(new DateInterval('P1M'));
                }
            } else {
                $deadline = $reftime->add(new DateInterval("P{$days}D"));
            }
        }

        return $deadline;
    }

}
