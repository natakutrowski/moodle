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

use block_xp\local\config\course_world_config;
use block_xp\local\utils\world_utils;
use local_xp\local\config\default_course_world_config;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;
use local_xp\local\utils\agent_utils;

/**
 * Get Level Up XP context config MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_levelupxp_context_config extends world_base {
    /**
     * Get the context configuration tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'get_levelupxp_context_config',
            'Get the context config settings',
            'Returns the Level Up XP configuration settings for a context.',
            [CONTEXT_COURSE, CONTEXT_SYSTEM],
            tool_definition::RISK_READ,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the context configuration input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return static::world_scoped_schema();
    }

    /**
     * Get the context configuration output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return schema::object([
            'general' => schema::object([
                'points_can_be_earned' => schema::boolean('Whether points earning rules are enabled.')->optional(),
                'info_page_available_to_learners' => schema::boolean(),
                'level_up_popup_notifications_enabled' => schema::boolean(),
                'instructions_shown_on_info_page' => schema::string()->optional(),
            ], 'General settings.'),
            'leaderboard' => schema::object([
                'individual_leaderboard_enabled' => schema::boolean(),
                'participation_policy' => schema::string()->optional(),
                'learner_isolation' => schema::string()->optional(),
                'participant_anonymity' => schema::string()->optional(),
                'rank_display' => schema::string()->optional(),
                'visible_participants' => schema::string()->optional(),
                'optional_columns_shown' => schema::array_of(schema::string())->optional(),
            ], 'Individual leaderboard settings.'),
            'team_leaderboard' => schema::object([
                'team_source' => schema::string(),
                'team_anonymity' => schema::string()->optional(),
                'team_ranking_method' => schema::string()->optional(),
                'optional_columns_shown' => schema::array_of(schema::string())->optional(),
            ], 'Team leaderboard settings, if available.')->optional(),
            'block_display' => schema::object([
                'block_title' => schema::string(),
                'block_description' => schema::string(),
                'recent_activity_shown_in_block' => schema::boolean()->optional(),
                'ranking_snapshot_shown_in_block' => schema::boolean()->optional(),
            ], 'Level Up XP block display settings.'),
        ], 'Level Up XP configuration settings for the context.');
    }

    /**
     * Get the configuration for the selected world.
     *
     * @return array
     */
    protected function execute() {
        $world = $this->get_world();
        $context = $world->get_context();
        $config = $world->get_config();
        $configlocked = \block_xp\di::get('config_locked');
        $leaderboardenabled = (bool) $config->get('enableladder');
        $supportsblockrecentactivity = world_utils::supports_recent_activity($world);
        $supportsteamleaderboard = world_utils::supports_team_leaderboard($world);
        $supportspointschange = world_utils::supports_local_points_management($world);

        $settingsdef = [];
        $generalsettings = array_filter([
            'enabled' => $supportspointschange ? [
                'outputkey' => 'points_can_be_earned',
                'transform' => 'boolval',
            ] : null,
            'enableinfos' => [
                'outputkey' => 'info_page_available_to_learners',
                'transform' => 'boolval',
            ],
            'enablelevelupnotif' => [
                'outputkey' => 'level_up_popup_notifications_enabled',
                'transform' => 'boolval',
            ],
        ]);
        if ((bool) $config->get('enableinfos')) {
            $generalsettings['instructions'] = [
                'outputkey' => 'instructions_shown_on_info_page',
                'transform' => function () use ($config, $context) {
                    $text = (string) $config->get('instructions');
                    $format = (int) $config->get('instructions_format');
                    $html = format_text($text, $format, [
                        'context' => $context,
                        'filter' => true,
                        'para' => false,
                    ]);
                    return agent_utils::shorten_text(agent_utils::html_to_text($html));
                },
            ];
        }
        $settingsdef['general'] = $generalsettings;

        $leaderboardsettings = [
            'enableladder' => [
                'outputkey' => 'individual_leaderboard_enabled',
                'transform' => 'boolval',
            ],
        ];
        if ($leaderboardenabled) {
            $leaderboardsettings += [
                'ladderparticipation' => [
                    'outputkey' => 'participation_policy',
                    'labels' => [
                        default_course_world_config::LEADERBOARD_PARTICIPATION_FORCED =>
                            'all learners are included and cannot opt out',
                        default_course_world_config::LEADERBOARD_PARTICIPATION_OPTOUT =>
                            'learners are included by default and may choose not to participate',
                        default_course_world_config::LEADERBOARD_PARTICIPATION_OPTIN =>
                            'learners are excluded by default and must choose to participate',
                    ],
                ],
                'ladderiso' => [
                    'outputkey' => 'learner_isolation',
                    'labels' => [
                        default_course_world_config::LEADERBOARD_ISO_DEFAULT =>
                            'learners are ranked with everyone, or only members of their groups',
                        default_course_world_config::LEADERBOARD_ISO_COHORTS =>
                            'learners are ranked only with members of their cohorts',
                    ],
                ],
                'identitymode' => [
                    'outputkey' => 'participant_anonymity',
                    'labels' => [
                        course_world_config::IDENTITY_OFF => 'hide participants identity',
                        course_world_config::IDENTITY_ON => 'display participants identity',
                        default_course_world_config::IDENTITY_FIRSTNAME_INITIAL_LASTNAME =>
                            'display first name and last initial',
                    ],
                ],
                'rankmode' => [
                    'outputkey' => 'rank_display',
                    'labels' => [
                        course_world_config::RANK_OFF => 'hide rank',
                        course_world_config::RANK_ON => 'display rank',
                        course_world_config::RANK_REL =>
                            'display relative rank',
                    ],
                ],
                'neighbours' => [
                    'outputkey' => 'visible_participants',
                    'transform' => function ($value) {
                        return (int) $value > 0
                            ? (int) $value . ' neighbours'
                            : 'display everyone';
                    },
                ],
                'laddercols' => [
                    'outputkey' => 'optional_columns_shown',
                    'transform' => function ($value) {
                        return self::levelupxp_config_csv_labels((string) $value, [
                            'xp' => 'total points',
                            'progress' => 'progress bar',
                        ]);
                    },
                ],
            ];
        }
        $settingsdef['leaderboard'] = $leaderboardsettings;

        $teamleaderboardsettings = [
            'enablegroupladder' => [
                'outputkey' => 'team_source',
                'labels' => [
                    default_course_world_config::GROUP_LADDER_NONE => 'none, team leaderboard disabled',
                    default_course_world_config::GROUP_LADDER_COURSE_GROUPS => 'course groups',
                    default_course_world_config::GROUP_LADDER_COHORTS => 'cohorts',
                    default_course_world_config::GROUP_LADDER_IOMAD_COMPANIES => 'IOMAD companies',
                    default_course_world_config::GROUP_LADDER_IOMAD_DEPARTMENTS => 'IOMAD departments',
                ],
            ],
        ];
        if ((int) $config->get('enablegroupladder') !== default_course_world_config::GROUP_LADDER_NONE) {
            $teamleaderboardsettings += [
                'groupidentitymode' => [
                    'outputkey' => 'team_anonymity',
                    'labels' => [
                        course_world_config::IDENTITY_OFF => 'hide group identity',
                        course_world_config::IDENTITY_ON => 'display group identity',
                    ],
                ],
                'grouporderby' => [
                    'outputkey' => 'team_ranking_method',
                    'labels' => [
                        default_course_world_config::GROUP_ORDER_BY_POINTS => 'points',
                        default_course_world_config::GROUP_ORDER_BY_POINTS_COMPENSATED_BY_AVG => 'points with compensation',
                        default_course_world_config::GROUP_ORDER_BY_PROGRESS => 'progress',
                    ],
                ],
                'groupladdercols' => [
                    'outputkey' => 'optional_columns_shown',
                    'transform' => function ($value) {
                        return self::levelupxp_config_csv_labels((string) $value, [
                            'xp' => 'total points',
                            'progress' => 'progress bar',
                        ]);
                    },
                ],
            ];
        }
        if ($supportsteamleaderboard) {
            $settingsdef['team_leaderboard'] = $teamleaderboardsettings;
        }

        $blockdisplaysettings = array_filter([
            'blocktitle' => ['outputkey' => 'block_title'],
            'blockdescription' => ['outputkey' => 'block_description'],
            'blockrecentactivity' => $supportsblockrecentactivity ? [
                'outputkey' => 'recent_activity_shown_in_block',
                'transform' => 'boolval',
            ] : null,
        ]);
        if ($leaderboardenabled) {
            $blockdisplaysettings += [
                'blockrankingsnapshot' => [
                    'outputkey' => 'ranking_snapshot_shown_in_block',
                    'transform' => 'boolval',
                ],
            ];
        }
        $settingsdef['block_display'] = $blockdisplaysettings;

        // Format the settings.
        $settings = [];
        foreach ($settingsdef as $sectionkey => $sectionsettings) {
            $settings[$sectionkey] = [];
            foreach ($sectionsettings as $key => $setting) {
                $outputkey = $setting['outputkey'] ?? $key;
                $value = $config->get($key);

                if (isset($setting['transform'])) {
                    $transform = $setting['transform'];
                    $value = $transform($value);
                } else if (isset($setting['labels'])) {
                    $value = static::levelupxp_config_label($value, $setting['labels']);
                }

                if ($configlocked->has($key) && $configlocked->get($key)) {
                    $value .= ' (setting locked by admin)';
                }

                $settings[$sectionkey][$outputkey] = $value;
            }
        }

        return $settings;
    }

    /**
     * Return a label by key, with a hardcoded fallback for unknown or stale values.
     *
     * @param mixed $value Config value.
     * @param array<int|string, string> $labels Labels indexed by config value.
     * @return string
     */
    protected static function levelupxp_config_label($value, array $labels): string {
        $key = is_numeric($value) ? (int) $value : (string) $value;
        if (array_key_exists($key, $labels)) {
            return $labels[$key];
        }
        return 'unrecognised value: ' . (string) $value;
    }

    /**
     * Return labels for comma-separated config values.
     *
     * @param string $csv Comma-separated config values.
     * @param array<int|string, string> $labels Labels indexed by config value.
     * @return list<string>
     */
    protected static function levelupxp_config_csv_labels(string $csv, array $labels): array {
        $out = [];
        foreach (explode(',', $csv) as $value) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }
            $out[] = self::levelupxp_config_label($value, $labels);
        }
        return array_values($out);
    }
}
