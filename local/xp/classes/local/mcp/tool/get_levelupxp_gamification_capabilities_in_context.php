<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\local\mcp\tool;

use block_xp\local\utils\world_utils;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;
use local_xp\local\world\global_world;

/**
 * Get gamification capabilities in context MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_levelupxp_gamification_capabilities_in_context extends world_base {
    /**
     * Get the gamification capabilities tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'get_levelupxp_gamification_capabilities_in_context',
            'Get gamification capabilities in context',
            'Returns a description of the Level Up XP gamification capabilities available in the context.',
            [CONTEXT_COURSE, CONTEXT_SYSTEM],
            tool_definition::RISK_READ,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the gamification capabilities input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return static::world_scoped_schema();
    }

    /**
     * Get the gamification capabilities output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return schema::object([
            'capabilities' => schema::array_of(schema::object([
                'label' => schema::string(),
                'description' => schema::string(),
                'variants' => schema::array_of(schema::raw(['type' => 'object']))->optional(),
                'options' => schema::array_of(schema::raw(['type' => 'object']))->optional(),
                'details_tool' => schema::string()->optional(),
                'notes' => schema::array_of(schema::string())->optional(),
            ])),
            'unsupported_features' => schema::array_of(schema::string()),
            'limitations' => schema::array_of(schema::string()),
        ]);
    }

    /**
     * Describe the gamification features available in the selected world.
     *
     * @return array
     */
    protected function execute() {
        global $CFG;

        $world = $this->get_world();
        $isoverall = $world instanceof global_world;
        $supportsteamladder = world_utils::supports_team_leaderboard($world);
        $hascompletion = !empty($CFG->enablecompletion);
        $hasavailability = !empty($CFG->enableavailability);
        $hasbadges = !empty($CFG->enablebadges);

        // The rule type resolver service.
        $ruletyperesolver = \block_xp\di::get('rule_type_resolver');
        $typeserializer = \block_xp\di::get('serializer_factory')->get_ruletype_serializer();
        $filtermediator = \block_xp\di::get('rule_filter_mediator');
        $typeserializer->set_filter_mediator($filtermediator);

        $completiontypenames = ['cm_completion', 'section_completion', 'course_completion'];
        $actiontypenames = array_reduce(
            $ruletyperesolver->get_types(),
            function ($carry, $type) use ($ruletyperesolver) {
                if (self::levelupxp_item_is_available($type)) {
                    $carry[] = $ruletyperesolver->get_type_name($type);
                }
                return $carry;
            },
            []
        );

        $ruletypedescriptor = function (string $typename) use ($ruletyperesolver): ?array {
            // The resolved rule type.
            $type = $ruletyperesolver->get_type($typename);
            if (!$type) {
                return null;
            }
            $label = $type->get_display_name();
            $desc = $type->get_short_description();
            return [
                'label' => $label instanceof \lang_string ? $label->out('en') : $label,
                'description' => $desc instanceof \lang_string ? $desc->out('en') : $desc,
            ];
        };
        $ruletypesdescriptor = function (array $typenames) use ($ruletypedescriptor) {
            return array_values(array_filter(array_map($ruletypedescriptor, $typenames)));
        };

        $plugman = \core_plugin_manager::instance();
        $shortcodes = $plugman->get_plugin_info('filter_shortcodes');
        $shortcodesavailable = (bool) $shortcodes && ($shortcodes->is_enabled() ?? true);
        $availabilityxp = $plugman->get_plugin_info('availability_xp');
        $availabilityxpavailable = (bool) $availabilityxp && ($availabilityxp->is_enabled() ?? true);
        $dropsavailable = $shortcodesavailable;
        $enabledactivitymodules = self::levelupxp_get_enabled_activity_modules();

        $adjacentfeatures = array_values(array_filter([
            [
                'label' => 'Activity modules available',
                'description' => 'Moodle activity and resource modules that are installed and enabled on this site.',
                'options' => $enabledactivitymodules,
                'notes' => [
                    self::s(
                        'Avoid suggesting activities or plugins that are not listed here unless the user wants to',
                        'explore plugins that will need to be installed.'
                    ),
                ],
            ],
            $hasbadges ? [
                'label' => 'Badges',
                'description' => self::s(
                    'Use Moodle badges as visible achievements that can be awarded when learners',
                    'reach specific Level Up XP levels.'
                ),
                'notes' => [
                    self::s(
                        'Badges are Moodle-native awards connected to Level Up XP levels; they are not a separate Level',
                        'Up XP achievement system.'
                    ),
                    self::s(
                        'A level can be configured to award a selected compatible Moodle badge when the learner reaches',
                        'that level.'
                    ),
                ],
            ] : null,
            $hasavailability ? [
                'label' => 'Access restriction',
                'description' => self::s(
                    'Use Moodle access restrictions to gate course sections, activities, or resources as part of a',
                    'progression path alongside Level Up XP.'
                ),
                'notes' => array_values(array_filter([
                    self::s(
                        'Core Moodle restrictions can gate content based on conditions such as completion, grades, dates,',
                        'groups, and profile fields.',
                    ),
                    $availabilityxpavailable
                        ? 'The Level Up XP Availability plugin can be used to restrict access by a learner\'s Level Up XP level.'
                        : null,
                ])),
            ] : null,
            $hascompletion ? [
                'label' => 'Activity and course completion',
                'description' => self::s(
                    'Use Moodle completion tracking to mark progress through courses and activities; Level Up XP can award points',
                    'from those completion states when completion rules are configured.'
                ),
                'notes' => [
                    'Completion can be manual or automatic depending on the Moodle completion settings.',
                    self::s(
                        'Automatic completion may depend on conditions such as viewing, submitting, receiving a grade,',
                        'or activity-specific requirements.'
                    ),
                    self::s(
                        'Completion tracking must be enabled for the course and the relevant activities before completion-based',
                        'XP rules can be useful.'
                    ),
                ],
            ] : null,
        ]));

        $teamsourceoptions = [
            [
                'label' => 'Course groups',
                'description' => 'Use Moodle course groups as teams.',
            ],
            [
                'label' => 'Cohorts',
                'description' => 'Use Moodle cohorts as teams.',
            ],
        ];
        if (\block_xp\di::get('iomad_facade')->exists()) {
            $teamsourceoptions[] = [
                'label' => 'IOMAD companies',
                'description' => 'Use IOMAD companies as teams.',
            ];
            $teamsourceoptions[] = [
                'label' => 'IOMAD departments',
                'description' => 'Use IOMAD departments as teams.',
            ];
        }

        $pointvariants = [];
        if ($hascompletion) {
            $pointvariants[] = [
                'label' => 'Completion-based rules',
                'description' => 'Award points from Moodle completion states.',
                'details_tool' => 'get_levelupxp_completion_rules',
                'options' => $ruletypesdescriptor($completiontypenames),
            ];
        }
        if (!empty($actiontypenames)) {
            $pointvariants[] = [
                'label' => 'Action-based rules',
                'description' => 'Award points when users perform available learner actions.',
                'details_tool' => 'get_levelupxp_action_rules',
                'options' => $ruletypesdescriptor($actiontypenames),
                'notes' => [
                    'Rules can be narrowed to specific activities, sections, or courses.',
                    'Rules can also target activities by attributes such as name or tag, or apply broadly where appropriate.',
                    self::s(
                        'Action rules may also have limit and repeat settings to prevent awarding points too often for',
                        'the same behaviour.'
                    ),
                ],
            ];
        }
        $pointvariants[] = [
            'label' => 'Event-based rules',
            'description' => 'Award points from low-level Moodle events.',
            'notes' => ['Event rules are legacy and have been superseded by action rules.'],
        ];
        $pointvariants[] = [
            'label' => 'Grade-based',
            'description' => 'Award points when a learner receives a qualifying grade.',
            'notes' => [
                self::s(
                    'Do not promote grade-based rewards by default; rewarding performance can affect motivation and',
                    'fairness, so it should be used deliberately and with care.'
                ),
                self::s(
                    'Students earn as many points as the numeric grade they received; a later lower grade does not',
                    'remove points already awarded; a higher grade awards the difference in points from the previous grade.'
                ),
                self::s(
                    'Grade rewards are based on Moodle gradebook items; calculated grade items can be used when the',
                    'points should come from a more complex gradebook formula.'
                ),
            ],
        ];
        if ($dropsavailable) {
            $pointvariants[] = [
                'label' => 'Drop-based',
                'description' => 'Drops that can be placed into Moodle content to award points when encountered.',
                'details_tool' => 'get_levelupxp_drops',
                'notes' => [
                    'Drops are invisible to learners and passively award points the first time the learner encounters them.',
                    self::s(
                        'Drops can be placed in Moodle content that supports filters, such as pages, labels, quiz feedback,',
                        'forum content, lesson pages, and many more.'
                    ),
                ],
            ];
        }

        $capabilities = [
            [
                'label' => 'Levels',
                'description' => 'Progression levels are available.',
                'details_tool' => 'get_levelupxp_levels',
                'notes' => [
                    'Levels define the points required to progress through the experience system.',
                    'Each level can have a custom name and description.',
                    'Level-up notifications can be enabled, and level-specific popup messages can be configured.',
                    'Level visuals can be customised; these visuals are sometimes referred to as level badges.',
                    'A Moodle badge can optionally be awarded when a learner reaches a level.',
                ],
            ],
            [
                'label' => 'Points',
                'description' => $isoverall ? 'Points are combined from selected courses.'
                    : 'Points can be awarded through the rule types available.',
                'variants' => $isoverall ? [] : $pointvariants,
                'notes' => [
                    $isoverall ? 'User points cannot be changed directly in this context.'
                        : 'Point awards can come from variety of methods, or manually.',
                    'Point and currency visuals can be customised separately from level visuals.',
                    $isoverall ? 'Configure point-earning method strategies in the source courses directly.'
                        : 'Setup can define how many, how often and where points are awarded.',
                ],
            ],
            [
                'label' => 'Leaderboard',
                'description' => 'Ranking features available.',
                'variants' => array_values(array_filter([
                    [
                        'label' => 'Individual leaderboard',
                        'description' => 'Rank learners individually by points and level.',
                        'details_tool' => 'get_levelupxp_leaderboard',
                        'notes' => [
                            'Learner identity can be shown, anonymised, or partially displayed as first name with last initial.',
                            self::s(
                                'Ranking can show absolute positions, show relative ranking, or be hidden. Relative ranking shows',
                                'the point difference between a learner and nearby leaderboard entries.'
                            ),
                            self::s(
                                'The displayed list can include everyone or be limited to a configured number of neighbouring',
                                'learners above and below the current learner.'
                            ),
                            self::s(
                                'Participation can be forced, opt-out, or opt-in; opt-in means learners are hidden until they',
                                'choose to join, and opt-out means learners are shown unless they choose to leave.'
                            ),
                            self::s(
                                'Leaderboard isolation can restrict who learners are compared with, for example by cohort where',
                                'that mode is configured.'
                            ),
                        ],
                    ],
                    $supportsteamladder ? [
                        'label' => 'Team leaderboard',
                        'description' => 'Rank teams using aggregated learner progress.',
                        'options' => $teamsourceoptions,
                        'notes' => [
                            'Team identity can be shown or anonymised so learners cannot see other teams\' names.',
                            self::s(
                                'Team ranking can be based on total points, progress, or points with compensation for teams',
                                'with fewer members.'
                            ),
                        ],
                    ] : null,
                ])),
            ],
            [
                'label' => 'Adjacent Moodle features',
                'description' => 'Available Moodle features that can be combined with Level Up XP in a gamification setup.',
                'variants' => $adjacentfeatures,
            ],
        ];
        $capabilities = array_values(array_filter($capabilities));

        return [
            'capabilities' => $capabilities,
            'unsupported_features' => [
                'quests',
                'challenges',
                'achievements',
                'missions',
            ],
            'limitations' => [
                self::s(
                    'Badges, access restrictions, and completion tracking are Moodle-native features used alongside',
                    'Level Up XP, not standalone Level Up XP-native systems.'
                ),
                self::s(
                    'Capabilities, features, and adjacent Moodle features that are not listed here are unlikely to be',
                    'available in this context; do not suggest or mention them unless the user explicitly asks, or there',
                    'is a strong reason to believe they are available.'
                ),
            ],
        ];
    }

    /**
     * Get enabled Moodle activity/resource modules.
     *
     * @return list<string>
     */
    protected static function levelupxp_get_enabled_activity_modules(): array {
        $stringmanager = get_string_manager();
        $modules = [];
        foreach (\core_plugin_manager::instance()->get_plugins_of_type('mod') as $module) {
            if ($module->is_enabled() !== true) {
                continue;
            }

            $component = 'mod_' . $module->name;
            $modules[] = $stringmanager->get_string('modulename', $component, null, 'en') . ' (' . $component . ')';
        }

        \core_collator::asort($modules, \core_collator::SORT_NATURAL);
        return array_values($modules);
    }

    /**
     * Whether an item with availability info is available.
     *
     * @param mixed $item Rule type/filter.
     * @return bool
     */
    protected static function levelupxp_item_is_available($item): bool {
        return !$item instanceof \block_xp\local\availability\has_availability_info
            || $item->get_availability_info()->is_available();
    }

    /**
     * Combine strings.
     *
     * @param string[]|string ...$strings The strings to combine.
     * @return string
     */
    protected static function s(array|string ...$strings): string {
        $strings = array_merge(...array_map(fn($string) => (array) $string, $strings));
        return implode(' ', array_map('trim', $strings));
    }
}
