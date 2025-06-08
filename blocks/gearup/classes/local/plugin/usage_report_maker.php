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
 * Usage report maker.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\plugin;

use block_gearup\di;
use block_gearup\local\model\mission;
use core_component;
use core_plugin_manager;

/**
 * Usage report maker.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usage_report_maker {

    /** @var string Hash. */
    protected $lkh = '467989c430017cbcba2fd7ee956225f95f203d89';

    /**
     * Constructor.
     */
    public function __construct() {
    }

    /**
     * Make usage report.
     *
     * @return object Where keys represent usage.
     */
    public function make() {
        global $CFG, $DB;
        $pluginman = core_plugin_manager::instance();
        $lm = di::get('lm');

        $data = (object) [
            'url' => $CFG->wwwroot,
            'siteidentifier' => get_site_identifier(),
            'moodle_version' => $CFG->version,
            'moodle_release' => $CFG->release,
            'lkh' => $this->lkh,
            'php_version' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
        ];

        $data->moodle_flavour = $this->get_flavour();
        $data->moodle_flavour_version = $this->get_flavour_version($data->moodle_flavour);

        $questinfo = $pluginman->get_plugin_info('block_gearup');
        $data->quest_version = $questinfo ? $questinfo->versiondisk : '?';
        $data->quest_release = $questinfo ? $questinfo->release : '?';

        $sql = 'SELECT COUNT(DISTINCT ctx.id)
                  FROM {block_gearup_mission} m
                  JOIN {context} ctx ON ctx.id = m.contextid
                 WHERE ctx.contextlevel = ?';
        $data->quest_courses = $DB->count_records_sql($sql, ['contextlevel' => CONTEXT_COURSE]);

        $sql = 'SELECT COUNT(DISTINCT mi.subjectid) FROM {block_gearup_mission_inst} mi';
        $data->quest_unique_users = $DB->count_records_sql($sql);

        $sql = 'SELECT COUNT(m.id)
                  FROM {block_gearup_mission} m
                  JOIN {context} ctx ON ctx.id = m.contextid
                 WHERE ctx.contextlevel = ?';
        $data->quest_system_missions = $DB->count_records_sql($sql, ['contextlevel' => CONTEXT_SYSTEM]);
        $data->quest_course_missions = $DB->count_records_sql($sql, ['contextlevel' => CONTEXT_COURSE]);

        $data->quest_quests = $DB->count_records('block_gearup_mission', ['type' => mission::TYPE_QUEST]);
        $data->quest_achievements = $DB->count_records('block_gearup_mission', ['type' => mission::TYPE_ACHIEVEMENT]);
        $data->quest_challenges = $DB->count_records('block_gearup_mission', ['type' => mission::TYPE_CHALLENGE]);
        $data->quest_streaks = $DB->count_records('block_gearup_mission', ['type' => mission::TYPE_STREAK]);

        $sql = 'component = ? AND filearea = ? AND filename != ?';
        $data->quest_achievement_badges = $DB->count_records_select('files', $sql, ['block_gearup', 'achievementbadges', '.']);
        $data->quest_quest_narrators = $DB->count_records_select('files', $sql, ['block_gearup', 'questnarrators', '.']);

        $sql = 'SELECT ROUND(AVG(t.n)) FROM
                    (SELECT COUNT(1) AS n
                       FROM {files}
                      WHERE component = ? AND filearea = ? AND filename != ?
                   GROUP BY contextid) AS t';
        $data->quest_achievement_badges_avg = (int) $DB->get_field_sql($sql, ['block_gearup', 'achievementbadges', '.']);
        $data->quest_quest_narrators_avg = (int) $DB->get_field_sql($sql, ['block_gearup', 'questnarrators', '.']);

        $sql = 'SELECT type, COUNT(1) AS n FROM {block_gearup_assigner} GROUP BY type ORDER BY type ASC';
        $data->quest_assigners_usage = $this->type_records_to_dict($DB->get_records_sql($sql, []));
        $sql = 'SELECT type, COUNT(1) AS n FROM {block_gearup_objective} GROUP BY type ORDER BY type ASC';
        $data->quest_objectives_usage = $this->type_records_to_dict($DB->get_records_sql($sql, []));
        $sql = 'SELECT type, COUNT(1) AS n FROM {block_gearup_outcome} GROUP BY type ORDER BY type ASC';
        $data->quest_outcomes_usage = $this->type_records_to_dict($DB->get_records_sql($sql, []));

        $components = ['availability_gearup', 'block_xp', 'block_stash', 'filter_shortcodes'];
        $data->plugins = array_reduce($components, function($carry, $component) use ($pluginman) {
            $plugininfo = $pluginman->get_plugin_info($component);
            if (!$plugininfo) {
                return $carry;
            }
            return array_merge($carry, [$component => ['r' => $plugininfo->release, 'v' => (string) $plugininfo->versiondisk]]);
        }, []);

        $data->lm_is_active = $lm->is_active();
        $data->lm_is_activated = $lm->is_activated();
        $data->lm_assigner_types = $lm->get_assigner_types();
        $data->lm_objective_types = $lm->get_objective_types();
        $data->lm_outcome_types = $lm->get_outcome_types();
        $data->lm_max_achievement_badges = $lm->max_achievement_badges();
        $data->lm_max_quest_narrators = $lm->max_quest_narrators();
        $data->lm_use_challenges = $lm->use_challenges();
        $data->lm_use_insights = $lm->use_insights();
        $data->lm_use_sitewide = $lm->use_sitewide();

        return $data;
    }

    /**
     * Get the flavour.
     *
     * @return string|null
     */
    protected function get_flavour() {
        if (array_key_exists('totara', core_component::get_plugin_types())) {
            return 'totara';
        } else if (array_key_exists('iomad', core_component::get_plugin_list('local'))) {
            return 'iomad';
        } else if (array_key_exists('workplace', core_component::get_plugin_list('theme'))) {
            return 'workplace';
        }
        return null;
    }

    /**
     * Get the flavour's version.
     *
     * @param string|null $flavour The flavour.
     * @return string|int|float|null The version.
     */
    protected function get_flavour_version($flavour) {
        global $CFG;
        if ($flavour !== 'totara') {
            return null;
        }

        $TOTARA = new \stdClass(); // @codingStandardsIgnoreLine
        include($CFG->dirroot . '/version.php');
        return isset($TOTARA->version) ? $TOTARA->version : null; // @codingStandardsIgnoreLine
    }

    /**
     * Records to dict.
     *
     * @param array $records The records.
     */
    protected function type_records_to_dict($records) {
        return array_reduce($records, function($carry, $item) {
            $carry[$item->type] = $item->n;
            return $carry;
        }, []);
    }
}
