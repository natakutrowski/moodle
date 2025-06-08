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
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\exporter;

use block_gearup\di;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\outcome\type\user_facing_type;
use block_gearup\local\utils\time_utils;
use renderer_base;

/**
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_exporter extends \core\external\exporter {

    /** @var mission The mission. */
    protected $mission;

    /**
     * Constructor.
     *
     * @param mission $mission The mission.
     * @param array $related The related objects.
     */
    public function __construct(mission $mission, $related = []) {
        $this->mission = $mission;
        parent::__construct([], $related + [
            'context' => $mission->get_context(),
        ]);
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'access_permissions_factory' => '\block_gearup\local\factory\access_permissions_factory',
            'context' => 'context',
            'mission_helper' => '\block_gearup\local\mission\helper',
            'url_resolver' => '\block_gearup\local\routing\url_resolver?',

            // When the instance is passed, we export in relation to the instance. When it is not provided,
            // we retrict information based on the capability to manage the mission.
            'mission_instance' => '\block_gearup\local\mission\mission_instance?',
        ];
    }

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'id' => [
                'type' => PARAM_INT,
            ],
            'contextid' => [
                'type' => PARAM_INT,
            ],
            'type' => [
                'type' => [
                    'isachievement' => [
                        'type' => PARAM_BOOL,
                    ],
                    'ischallenge' => [
                        'type' => PARAM_BOOL,
                    ],
                    'isquest' => [
                        'type' => PARAM_BOOL,
                    ],
                ],
            ],

            'title' => [
                'type' => PARAM_TEXT,
            ],
            'secret' => [
                'type' => PARAM_ALPHANUM,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],

            // We cannot set the property to null when it's a sub structure.
            'visual' => [
                'type' => visual_exporter::read_properties_definition(),
                'optional' => true,
            ],

            'description' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],

            'instructions' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],

            'feedback' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],

            'assigners' => [
                'type' => assigner_exporter::read_properties_definition(),
                'multiple' => true,
            ],
            'hasassigners' => [
                'type' => PARAM_BOOL,
            ],

            'objectives' => [
                'type' => objective_exporter::read_properties_definition(),
                'multiple' => true,
            ],
            'hasobjectives' => [
                'type' => PARAM_BOOL,
            ],

            'outcomes' => [
                'type' => outcome_exporter::read_properties_definition(),
                'multiple' => true,
            ],
            'hasoutcomes' => [
                'type' => PARAM_BOOL,
            ],

            'rewards' => [
                'type' => outcome_exporter::read_properties_definition(),
                'multiple' => true,
            ],
            'hasrewards' => [
                'type' => PARAM_BOOL,
            ],

            'isarchived' => [
                'type' => PARAM_BOOL,
            ],
            'iseditable' => [
                'type' => PARAM_BOOL,
                'default' => false,
            ],

            'assignmentbehaviour' => [
                'optional' => true,
                'type' => [
                    'name' => [
                        'type' => PARAM_RAW,
                    ],
                    'description' => [
                        'type' => PARAM_RAW,
                    ],
                    'iscompulsory' => [
                        'type' => PARAM_BOOL,
                    ],
                    'isoptional' => [
                        'type' => PARAM_BOOL,
                    ],
                    'isdiscoverable' => [
                        'type' => PARAM_BOOL,
                    ],
                ],
            ],

            'timing' => [
                'optional' => true,
                'type' => [
                    'timelimitformatted' => [
                        'type' => PARAM_RAW,
                    ],
                    'hastimelimit' => [
                        'type' => PARAM_BOOL,
                    ],
                    'isrepeating' => [
                        'type' => PARAM_BOOL,
                    ],
                    'repeats' => [
                        'optional' => true,
                        'type' => [
                            'daily' => [
                                'type' => PARAM_BOOL,
                                'default' => false,
                            ],
                            'dailyweekday' => [
                                'type' => PARAM_BOOL,
                                'default' => false,
                            ],
                            'weekly' => [
                                'type' => PARAM_BOOL,
                                'default' => false,
                            ],
                            'fortnightly' => [
                                'type' => PARAM_BOOL,
                                'default' => false,
                            ],
                            'monthly' => [
                                'type' => PARAM_BOOL,
                                'default' => false,
                            ],
                        ],
                    ],
                ],
            ],

            'shortcode' => [
                'optional' => true,
                'type' => [
                    'snippet' => [
                        'type' => PARAM_RAW,
                    ],
                    'helpformatted' => [
                        'type' => PARAM_RAW,
                    ],
                ],
            ],

            'manageurl' => [
                'type' => PARAM_URL,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],

            'recruitcount' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        global $USER;

        $mission = $this->mission;
        $context = $mission->get_context();

        $accessperms = $this->related['access_permissions_factory']->get_permissions_for_context($context);
        $missionhelper = $this->related['mission_helper'];
        $missioninst = $this->related['mission_instance'] ?? null;

        // TODO Use user from related object?
        $canmanage = $accessperms->can_manage($USER->id);

        // TODO Get as a related object instead?
        $repository = di::get('repository');

        $manageurl = null;
        if ($canmanage) {
            $urlresolver = $this->related['url_resolver'] ?? di::get('url_resolver');
            $manageurl = $urlresolver->reverse('mission', ['missionid' => $mission->get_id()])->out(false);
        }

        // Assigners cannot be seen by non-managers.
        $assigners = [];
        if ($canmanage) {
            $assigners = $repository->get_assigners($mission->get_id());
        }

        $objectives = [];
        if ($canmanage || $this->should_export_objectives($missioninst)) {
            $objectives = array_values($mission->get_objectives($mission->get_id()));
        }

        // Only quests have outcomes.
        $typehasoutcomes = $missionhelper->is_a_quest($mission) || $missionhelper->is_a_challenge($mission);
        $outcomes = [];
        $rewards = [];
        $canseerewards = $canmanage || $this->should_export_rewards($missioninst);
        if ($typehasoutcomes && ($canmanage || $canseerewards)) {
            $alloutcomes = array_values($repository->get_outcomes($mission->get_id(), false));
            if ($canmanage) {
                $outcomes = $alloutcomes;
            }
            if ($canseerewards) {
                $rewards = array_values(array_filter($alloutcomes, function($outcome) {
                    return $outcome->get_type() instanceof user_facing_type;
                }));
            }
            unset($alloutcomes);
        }

        $iseditable = $canmanage && !$missionhelper->is_archived($mission);
        $assignbehaviour = null;
        $shortcode = null;
        if ($canmanage) {
            $assignbehaviour = $this->get_assignment_behaviour_properties();
            if ($missionhelper->is_discoverable($mission)) {
                $shortcode = [
                    'snippet' => "[questdiscovery id={$mission->get_id()} secret="
                        . substr($mission->get_secret() ?? 'invalid', 0, 7) . "]",
                    'helpformatted' => $output->help_icon('shortcodes', 'block_gearup'),
                ];
            }
        }

        $timing = null;
        if ($canmanage || $missioninst) {
            $timing = $this->get_timing_properties();
        }

        $recruitcount = null;
        if ($canmanage) {
            $recruitcount = $repository->count_users($mission->get_id());
        }

        $data = [
            'id' => $mission->get_id(),
            'contextid' => $mission->get_context()->id,

            'type' => [
                'isachievement' => $missionhelper->is_an_achievement($mission),
                'ischallenge' => $missionhelper->is_a_challenge($mission),
                'isquest' => $missionhelper->is_a_quest($mission),
            ],

            'title' => $mission->get_title(),
            'secret' => $canmanage ? $mission->get_secret() : null,

            'hasassigners' => !empty($assigners),
            'assigners' => array_map(function($assigner) use ($context, $output) {
                $exporter = new assigner_exporter($assigner, [
                    'context' => $context,
                    'mission' => $this->mission,
                ]);
                return $exporter->export($output);
            }, $assigners),

            'hasobjectives' => !empty($objectives),
            'objectives' => array_map(function($objective) use ($context, $output) {
                $exporter = new objective_exporter($objective, [
                    'context' => $context,
                    'mission' => $this->mission,
                ]);
                return $exporter->export($output);
            }, $objectives),

            'hasrewards' => !empty($rewards),
            'rewards' => array_map(function($outcome) use ($context, $output) {
                $exporter = new outcome_exporter($outcome, [
                    'context' => $context,
                    'mission' => $this->mission,
                ]);
                return $exporter->export($output);
            }, $rewards),

            'hasoutcomes' => !empty($outcomes),
            'outcomes' => array_map(function($outcome) use ($context, $output) {
                $exporter = new outcome_exporter($outcome, [
                    'context' => $context,
                    'mission' => $this->mission,
                ]);
                return $exporter->export($output);
            }, $outcomes),

            'isarchived' => $missionhelper->is_archived($mission),
            'iseditable' => $iseditable,
            'manageurl' => $manageurl,
            'recruitcount' => $recruitcount,
        ];

        // Exporters don't support null for sub structures.
        if ($visual = $mission->get_visual()) {
            $data['visual'] = (new visual_exporter($visual))->export($output);
        }

        // Story line.
        $data += [
            'description' => $canmanage ? $mission->get_description() : null,
            'instructions' => $canmanage ? $mission->get_instructions() : null,
            'feedback' => $canmanage ? $mission->get_feedback() : null,
        ];

        if ($assignbehaviour !== null) {
            $data['assignmentbehaviour'] = $assignbehaviour;
        }
        if ($timing !== null) {
            $data['timing'] = $timing;
        }
        if ($shortcode !== null) {
            $data['shortcode'] = $shortcode;
        }

        return $data;
    }

    protected function get_assignment_behaviour_properties() {
        $mh = $this->related['mission_helper'];
        $mission = $this->mission;

        $assignbehaviour = null;
        $name = null;
        $desc = null;
        if ($mh->is_compulsory($mission)) {
            $assignbehaviour = 0;
            $name = get_string('compulsoryquest', 'block_gearup');
            $desc = get_string('compulsoryquestdesc', 'block_gearup');
        } else if ($mh->is_optional($mission)) {
            $assignbehaviour = 1;
            $name = get_string('optionalquest', 'block_gearup');
            $desc = get_string('optionalquestdesc', 'block_gearup');
        } else if ($mh->is_discoverable($mission)) {
            $assignbehaviour = 2;
            $name = get_string('discoverablequest', 'block_gearup');
            $desc = get_string('discoverablequestdesc', 'block_gearup');
        } else {
            $assignbehaviour = -1;
            $name = get_string('invaliddata', 'core_error');
            $desc = '';
        }

        return [
            'name' => $name,
            'description' => $desc,
            'iscompulsory' => $assignbehaviour === 0,
            'isoptional' => $assignbehaviour === 1,
            'isdiscoverable' => $assignbehaviour === 2,
        ];
    }

    protected function get_timing_properties() {
        $mission = $this->mission;
        $missionhelper = $this->related['mission_helper'];

        $timelimit = $mission->get_time_limit();
        $hastimelimit = $timelimit > 0;
        $isrepeating = $missionhelper->is_repeating($mission);
        $repeats = null;

        $timelimitformatted = get_string('nolimit', 'block_gearup');
        if ($hastimelimit) {
            if ($timelimit == DAYSECS) {
                $timelimitformatted = get_string('numday', 'core', 1);
            } else if ($timelimit == time_utils::DAILY_WEEKDAY) {
                $timelimitformatted = get_string('numweekday', 'block_gearup', 1);
            } else if ($timelimit == WEEKSECS) {
                $timelimitformatted = get_string('numweek', 'core', 1);
            } else if ($timelimit == WEEKSECS * 2) {
                $timelimitformatted = get_string('numweeks', 'core', 2);
            } else if ($timelimit == DAYSECS * 30) {
                $timelimitformatted = get_string('nummonth', 'core', 1);
            }
        }

        if ($isrepeating && $hastimelimit) {
            $repeats = [
                'daily' => false,
                'dailyweekday' => false,
                'weekly' => false,
                'fortnightly' => false,
                'monthly' => false,
            ];
            if ($timelimit == DAYSECS) {
                $repeats['daily'] = true;
            } else if ($timelimit == time_utils::DAILY_WEEKDAY) {
                $repeats['dailyweekday'] = true;
            } else if ($timelimit == WEEKSECS) {
                $repeats['weekly'] = true;
            } else if ($timelimit == WEEKSECS * 2) {
                $repeats['fortnightly'] = true;
            } else if ($timelimit == DAYSECS * 30) {
                $repeats['monthly'] = true;
            }
        }

        $data = [
            'timelimitformatted' => $timelimitformatted,
            'hastimelimit' => $hastimelimit,
            'isrepeating' => $isrepeating,
        ];
        if ($repeats) {
            $data['repeats'] = $repeats;
        }
        return $data;
    }

    protected function should_export_objectives(?mission_instance $missioninst) {
        if (!$missioninst) {
            return false;
        }

        $missionhelper = $this->related['mission_helper'];

        if ($missionhelper->has_started($missioninst)) {
            return true;
        }

        return (bool) preg_match('/^\s*\[objectives\]\s*$/m', $this->mission->get_description() ?? '');
    }

    protected function should_export_rewards(?mission_instance $missioninst) {
        if (!$missioninst) {
            return false;
        }

        $missionhelper = $this->related['mission_helper'];

        if ($missionhelper->is_a_challenge($missioninst)) {
            return true;
        }

        if ($missionhelper->has_completed($missioninst)) {
            return true;
        }

        return (bool) preg_match('/^\s*\[rewards\]\s*$/m', $this->mission->get_description() ?? '')
            || (bool) preg_match('/^\s*\[rewards\]\s*$/m', $this->mission->get_instructions() ?? '');
    }

}
