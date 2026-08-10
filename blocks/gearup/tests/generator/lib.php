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
 * Level Up Quest generator.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_gearup\di;
use block_gearup\local as ns;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\persisted_achievement;
use block_gearup\local\mission\persisted_challenge;
use block_gearup\local\mission\persisted_mission_instance;
use block_gearup\local\mission\persisted_quest;
use block_gearup\local\mission\persisted_streak;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\persisted_objective;
use block_gearup\local\objective\type\type;
use block_gearup\tests\mock\action\logged_in_mock;
use block_gearup\tests\mock\mission\achievement_mock;
use block_gearup\tests\mock\mission\challenge_mock;
use block_gearup\tests\mock\mission\mission_instance_mock;
use block_gearup\tests\mock\mission\quest_mock;
use block_gearup\tests\mock\mission\streak_mock;
use block_gearup\tests\mock\objective\objective_instance_mock;
use block_gearup\tests\mock\objective\objective_mock;

require_once(__DIR__ . '/../mocks/action/logged_in_mock.php');
require_once(__DIR__ . '/../mocks/objective/objective_mock.php');
require_once(__DIR__ . '/../mocks/objective/objective_instance_mock.php');
require_once(__DIR__ . '/../mocks/mission/mission_mock.php');
require_once(__DIR__ . '/../mocks/mission/achievement_mock.php');
require_once(__DIR__ . '/../mocks/mission/challenge_mock.php');
require_once(__DIR__ . '/../mocks/mission/quest_mock.php');
require_once(__DIR__ . '/../mocks/mission/streak_mock.php');
require_once(__DIR__ . '/../mocks/mission/mission_instance_mock.php');

/**
 * Level Up Quest generator.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_gearup_generator extends \testing_block_generator {

    /** @var int The assigner counter. */
    protected $assignercounter = 0;
    /** @var int The mission counter. */
    protected $missioncounter = 0;
    /** @var int The objective counter. */
    protected $objectivecounter = 0;
    /** @var int The outcome counter. */
    protected $outcomecounter = 0;

    /**
     * Reset process.
     *
     * Do not call directly.
     *
     * @return void
     */
    public function reset() {
        $this->missioncounter = 0;
        $this->objectivecounter = 0;
        $this->outcomecounter = 0;
        $this->assignercounter = 0;
    }

    /**
     * Create an achievement.
     *
     * @param object|array $data The data.
     * @return \block_gearup\local\mission\persisted_achievement
     */
    public function create_achievement($data = null) {
        $data = (array) ($data ?: []);
        $data['objectives'] = $data['objectives'] ?? [[
            'type' => 'manual',
        ]];
        $mission = $this->create_persisted_mission($data + [
            'type' => ns\mission\achievement::class,
        ]);
        return $mission;
    }

    /**
     * Create an assigner model.
     *
     * @param object|array $data The data.
     * @return ns\model\assigner
     */
    public function create_assigner_model($data) {
        $data = (object) $data;

        if (empty($data->missionid)) {
            throw new coding_exception('Expected mission ID.');
        }

        $this->assignercounter += 1;
        $obj = new ns\model\assigner(0, (object) [
            'missionid' => $data->missionid,
            'type' => $data->type,
        ]);
        if (isset($data->label)) {
            $obj->set('label', $data->label);
        }
        if (isset($data->enabled)) {
            $obj->set('enabled', $data->enabled);
        }
        if (!empty($data->configdata)) {
            $obj->set('configdata', (object) $data->configdata);
        }
        $obj->create();
        return $obj;
    }

    /**
     * Create a challenge.
     *
     * @param object|array $data The data.
     * @return \block_gearup\local\mission\persisted_challenge
     */
    public function create_challenge($data = null) {
        $data = (array) ($data ?: []);
        $data['objectives'] = $data['objectives'] ?? [[
            'type' => 'manual',
        ]];
        $mission = $this->create_persisted_mission($data + [
            'type' => ns\mission\challenge::class,
        ]);
        return $mission;
    }

    /**
     * Create a mission model.
     *
     * @param object|array $data The data.
     */
    public function create_mission_model($data = null) {
        global $DB;

        $data = (object) ($data ?: []);

        $this->missioncounter += 1;

        $contextid = $data->contextid ?? null;
        if (!$contextid && isset($data->courseid)) {
            $contextid = context_course::instance($data->courseid)->id;
        } else if (!$contextid) {
            $contextid = SYSCONTEXTID;
        }

        $type = $data->type ?? ns\mission\achievement::class;
        $record = (object) [
            'contextid' => $contextid,
            'type' => is_string($type) ? ns\model\mission::get_internal_type($type) : $type,
            'state' => $data->state ?? mission::STATE_ACTIVE,
            'title' => $data->title ?? $data->name ?? "Mission {$this->missioncounter}",
            'description' => $data->description ?? "Mission {$this->missioncounter} description",
            'instructions' => $data->instructions ?? "Mission {$this->missioncounter} instructions",
            'feedback' => $data->feedback ?? "Mission {$this->missioncounter} feedback",
        ];

        if ($record->type === ns\model\mission::TYPE_STREAK) {
            if (!isset($record->repeatcount)) {
                $record->repeatcount = mission::REPEAT_ALWAYS;
            }
            if (!isset($record->timelimit)) {
                $record->timelimit = DAYSECS;
            }
        }

        $optionalprops = ['repeatcount', 'startmode', 'timelimit', 'visual', 'visibility'];
        foreach ($optionalprops as $prop) {
            if (isset($data->$prop)) {
                $record->$prop = $data->$prop;
            }
        }

        $mission = new ns\model\mission(0, $record);
        if (!$mission->is_valid()) {
            $errors = array_map(function ($value) {
                return (string) $value;
            }, $mission->get_errors());
            throw new \coding_exception('Model data is not valid: ' . json_encode($mission->to_record())
                . ' / ' . json_encode($errors));
        }
        $mission->create();

        if (isset($data->timecreated)) {
            $DB->update_record(ns\model\mission::TABLE, (object) [
                'id' => $mission->get('id'),
                'timecreated' => $data->timecreated,
            ]);
            $mission->set('timecreated', $data->timecreated);
        }

        foreach ($data->objectives ?? [] as $obj) {
            $this->create_objective_model((array) $obj + ['missionid' => $mission->get('id')]);
        }
        foreach ($data->outcomes ?? [] as $obj) {
            $this->create_outcome_model((array) $obj + ['missionid' => $mission->get('id')]);
        }
        foreach ($data->assigners ?? [] as $obj) {
            $this->create_assigner_model((array) $obj + ['missionid' => $mission->get('id')]);
        }

        return $mission;
    }

    /**
     * Create a mission instance model.
     *
     * @param object|array $data The data.
     */
    public function create_mission_instance_model($data = null) {
        global $DB;

        $data = (object) ($data ?: []);
        if (!isset($data->missionid)) {
            throw new coding_exception('Expected mission ID.');
        } else if (!isset($data->subjectid)) {
            throw new coding_exception('Expected subject ID.');
        }

        $record = (object) [
            'missionid' => $data->missionid,
            'subjectid' => $data->subjectid,
        ];

        $keys = array_keys(ns\model\mission_inst::define_properties());
        $optionalprops = array_diff($keys, ['id'], array_keys((array) $record));
        foreach ($optionalprops as $prop) {
            if (isset($data->$prop)) {
                $record->$prop = $data->$prop;
            }
        }

        $missioninst = new ns\model\mission_inst(0, $record);
        $missioninst->create();

        if (isset($data->timecreated)) {
            $DB->update_record(ns\model\mission_inst::TABLE, (object) [
                'id' => $missioninst->get('id'),
                'timecreated' => $data->timecreated,
            ]);
            $missioninst->set('timecreated', $data->timecreated);
        }

        return $missioninst;
    }

    /**
     * Create an objective model.
     *
     * @param object|array $data The data.
     * * @return ns\model\objective
     */
    public function create_objective_model($data) {
        $data = (object) $data;

        if (empty($data->missionid)) {
            throw new coding_exception('Expected mission ID.');
        } else if (empty($data->type)) {
            throw new coding_exception('Expected objective type.');
        }

        $this->objectivecounter += 1;
        $obj = new ns\model\objective(0, (object) [
            'missionid' => $data->missionid,
            'type' => $data->type,
            'label' => $data->label ?? "Objective {$this->objectivecounter}",
            'countneeded' => $data->countneeded ?? 1,
        ]);
        if (!empty($data->configdata)) {
            $obj->set('configdata', (object) $data->configdata);
        }
        $obj->create();
        return $obj;
    }

    /**
     * Create an outcome model.
     *
     * @param object|array $data The data.
     * @return ns\model\outcome
     */
    public function create_outcome_model($data) {
        $data = (object) $data;

        if (empty($data->missionid)) {
            throw new coding_exception('Expected mission ID.');
        }

        $this->outcomecounter += 1;
        $obj = new ns\model\outcome(0, (object) [
            'missionid' => $data->missionid,
            'type' => $data->type,
            'label' => $data->label ?? "Outcome {$this->outcomecounter}",
        ]);
        if (isset($data->visibility)) {
            $obj->set('visibility', $data->visibility);
        }
        if (!empty($data->configdata)) {
            $obj->set('configdata', (object) $data->configdata);
        }
        $obj->create();
        return $obj;
    }

    /**
     * Create a persisted mission.
     *
     * @param object|array $data The data.
     * @return \block_gearup\local\mission\persisted_mission
     */
    public function create_persisted_mission($data = null) {
        $data = (object) ($data ?: []);
        $model = $this->create_mission_model($data);

        $objgetter = function () use ($model) {
            $objmodels = ns\model\objective::get_records(['missionid' => $model->get('id')], 'id', 'ASC');
            return array_values(array_map(function ($objmodel) {
                return new persisted_objective($objmodel, di::get('objective_type_resolver'));
            }, $objmodels));
        };

        if ($model->is_achievement()) {
            return new persisted_achievement($model, $objgetter);
        } else if ($model->is_challenge()) {
            return new persisted_challenge($model, $objgetter);
        } else if ($model->is_quest()) {
            return new persisted_quest($model, $objgetter);
        } else if ($model->is_streak()) {
            return new persisted_streak($model, $objgetter);
        }
        throw new \coding_exception('Unexpected mission type');
    }

    /**
     * Create a persisted mission instance.
     *
     * @param mission $mission The mission.
     * @param object|array $data The data.
     * @return \block_gearup\local\mission\persisted_mission_instance
     */
    public function create_persisted_mission_instance($mission, $data = null) {
        $data = (object) ($data ?: []);
        $data->missionid = $mission->get_id();
        $model = $this->create_mission_instance_model($data);
        return new persisted_mission_instance($model, $mission, []);
    }

    /**
     * Create a recruit.
     *
     * This is mostly an alias for the benefits of behat.
     *
     * @param object|array $data The data.
     * @return \block_gearup\local\mission\persisted_mission_instance
     */
    public function create_recruit($data = null) {
        $data = (object) ($data ?: []);

        if (empty($data->missionid)) {
            throw new \coding_exception('Expected mission ID.');
        } else if (empty($data->subjectid)) {
            throw new \coding_exception('Expected subject ID.');
        }

        $repo = di::get('repository');
        $mo = di::get('mission_operator');
        $mission = $repo->get_mission($data->missionid);
        return $mo->assign_mission($mission, $data->subjectid);
    }

    /**
     * Create a speech file.
     *
     * @param mission|int $mission The mission or mission ID.
     * @param string $storyline The storyline.
     * @param int $messageid The message ID.
     * @param string|null $fixturepath The fixture file path.
     * @return \stored_file
     */
    public function create_speech_file($mission, string $storyline = 'description', int $messageid = 0) {
        if (!$mission instanceof mission) {
            $mission = di::get('repository')->get_mission($mission);
        }

        $record = (object) [
            'contextid' => $mission->get_context()->id,
            'component' => 'block_gearup',
            'filearea' => 'speech',
            'itemid' => $mission->get_id(),
            'filepath' => '/' . trim($storyline, '/') . '/',
            'filename' => (string) $messageid,
            'mimetype' => 'audio/mp3',
        ];

        $fs = get_file_storage();
        return $fs->create_file_from_string($record, 'mock audio');
    }

    /**
     * Create a streak.
     *
     * @param object|array $data The data.
     * @return \block_gearup\local\mission\persisted_streak
     */
    public function create_streak($data = null) {
        $data = (array) ($data ?: []);
        $data['objectives'] = $data['objectives'] ?? [[
            'type' => 'manual',
        ]];
        $mission = $this->create_persisted_mission($data + [
            'type' => ns\mission\streak::class,
        ]);
        return $mission;
    }

    /**
     * Create a quest.
     *
     * This is mostly an alias for the benefits of behat.
     *
     * @param object|array $data The data.
     * @return \block_gearup\local\mission\persisted_quest
     */
    public function create_quest($data = null) {
        $data = (array) ($data ?: []);
        $data['visual'] = $data['visual'] ?? 'person-1';
        $data['objectives'] = $data['objectives'] ?? [[
            'type' => 'manual',
        ]];
        $mission = $this->create_persisted_mission($data + [
            'type' => ns\mission\quest::class,
        ]);
        return $mission;
    }

    /**
     * Mock an action logged in.
     *
     * @param integer $userid The user ID.
     * @param \DateTimeImmutable $time The time.
     */
    public function mock_action_loggedin(int $userid, ?\DateTimeImmutable $time = null) {
        $action = new logged_in_mock($userid);
        if ($time) {
            $action->set_time($time);
        }
        return $action;
    }

    /**
     * Mock an achievement.
     *
     * @param array|object|null $data The data.
     */
    public function mock_achievement($data = null) {
        return new achievement_mock((object) array_merge(
            (array) $data ?? [],
            []
        ));
    }

    /**
     * Mock an challenge.
     *
     * @param array|object|null $data The data.
     */
    public function mock_challenge($data = null) {
        return new challenge_mock((object) array_merge(
            (array) $data ?? [],
            []
        ));
    }

    /**
     * Mock an quest.
     *
     * @param array|object|null $data The data.
     */
    public function mock_mission($data = null) {
        $data = (object) ($data ?? []);
        $type = $data->type ?? ns\mission\achievement::class;
        switch ($type) {
            case ns\mission\achievement::class:
                return $this->mock_achievement($data);
            case ns\mission\quest::class:
                return $this->mock_quest($data);
            case ns\mission\streak::class:
                return $this->mock_streak($data);
            case ns\mission\challenge::class:
                return $this->mock_challenge($data);
        }
    }

    /**
     * Mock an quest.
     *
     * @param array|object|null $data The data.
     */
    public function mock_quest($data = null) {
        return new quest_mock((object) array_merge(
            (array) $data ?? [],
            []
        ));
    }

    /**
     * Mock an quest.
     *
     * @param array|object|null $data The data.
     */
    public function mock_streak($data = null) {
        return new streak_mock((object) array_merge(
            [
                    'timelimit' => 86400,
                ],
            (array) $data ?? [],
            [
                    'repeatcount' => mission::REPEAT_ALWAYS,
                ]
        ));
    }

    /**
     * Mock an objective.
     *
     * @param type $type The type.
     * @param object $data The data.
     */
    public function mock_mission_instance(mission $mission, $data = null) {
        return new mission_instance_mock($mission, (object) ($data ?? []));
    }

    /**
     * Mock an objective.
     *
     * @param type $type The type.
     * @param array|object $config The type config.
     * @param array|object|null $data The data.
     */
    public function mock_objective(type $type, $config, $data = null) {
        return new objective_mock($type,
            (object) array_merge(
                (array) $data ?? [],
                [
                    'typeconfig' => (object) $config,
                ]
            )
        );
    }

    /**
     * Mock an objective.
     *
     * @param type $type The type.
     * @param object $data The data.
     */
    public function mock_objective_instance(objective $obj, $data = null) {
        return new objective_instance_mock($obj, (object) ($data ?? []));
    }

}
