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

namespace local_xp\local\controller;

use block_xp\di;
use block_xp\local\routing\url;
use block_xp\local\rule\instance;
use block_xp\local\rule\instance_with_source;
use block_xp\local\utils\world_utils;
use local_xp\local\config\default_course_world_config;
use local_xp\output\logs_table;

/**
 * Log controller class.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class log_controller extends \block_xp\local\controller\log_controller {
    /** @var logs_table The log table. */
    protected $table;
    /** @var int|null The rule ID. */
    protected $ruleid = null;
    /** @var instance|null The rule. */
    protected $rule = null;

    /**
     * Define optional parameters.
     *
     * @return array
     */
    protected function define_optional_params() {
        $params = parent::define_optional_params();
        $params[] = ['ruleid', null, PARAM_INT];
        $params[] = ['download', '', PARAM_ALPHA, false];
        $params[] = ['downloadfilename', '', PARAM_NOTAGS, false];
        return $params;
    }

    /**
     * Get download filename.
     */
    protected function get_download_filename(): string {
        $userid = $this->get_user_id();
        $ruleid = $this->get_rule_id();
        $groupid = $this->is_supporting_groups() ? $this->get_groupid() : null;
        $defaultfilename = 'xp-log-' . $this->world->get_context()->id;
        if ($ruleid) {
            $defaultfilename .= '-r' . (string) (int) $ruleid;
        }
        if ($userid) {
            $defaultfilename .= '-u' . (string) (int) $userid;
        }
        if ($groupid) {
            $defaultfilename .= '-' . (string) (int) $groupid;
        }
        $defaultfilename .= '-' . userdate(time(), '%Y-%m-%d');
        return $this->get_param('downloadfilename') ?: $defaultfilename;
    }

    /**
     * Get the rule IDs to filter by.
     *
     * @return int[]
     */
    protected function get_filter_by_rule_ids(): array {
        $ruleid = $this->get_rule_id();
        if (!$ruleid) {
            return [];
        }

        $ruleids = [$ruleid];
        $rule = $this->get_rule();
        if ($rule instanceof instance_with_source && $rule->get_source_rule_id()) {
            $ruleids[] = $rule->get_source_rule_id();
        } else if (!$rule) {
            // Find out if a local rule uses this ID as source ID, then filter for both.
            $manager = di::get('world_rule_manager_factory')->get_rule_manager($this->world);
            foreach ($manager->get_rules() as $candidate) {
                if ($candidate instanceof instance_with_source && $candidate->get_source_rule_id() === $ruleid) {
                    $ruleids[] = $candidate->get_id();
                    break;
                }
            }
        }

        return $ruleids;
    }

    /**
     * Get the rule.
     *
     * @return instance|null
     */
    protected function get_rule(): ?instance {
        $this->get_rule_id();
        return $this->rule;
    }

    /**
     * Get the rule ID.
     *
     * @return int When falsy, no rule.
     */
    protected function get_rule_id(): int {
        if ($this->ruleid === null) {
            $ruleid = $this->get_param('ruleid');
            if (!$ruleid || $ruleid <= 0) {
                $ruleid = 0;
            }

            // We don't validate whether the rule was found or not because it is not possible to
            // abuse the rule ID to extrafiltrate information that wasn't already visible.
            $manager = di::get('world_rule_manager_factory')->get_rule_manager($this->world);
            $this->rule = $ruleid ? $manager->get_rule($ruleid) : null;
            $this->ruleid = $ruleid;
        }
        return $this->ruleid;
    }

    /**
     * Get table.
     *
     * @return \block_xp\output\logs_table
     */
    protected function get_table() {
        if (!$this->table) {
            $teamresolver = null;
            $withteams = world_utils::supports_team_leaderboard($this->world)
                && $this->world->get_config()->get('enablegroupladder') != default_course_world_config::GROUP_LADDER_NONE;
            if ($withteams) {
                $teamresolverfactory = \block_xp\di::get('team_membership_resolver_factory');
                $teamresolver = $teamresolverfactory->get_course_team_membership_resolver($this->world);
            }
            $table = new logs_table(
                $this->world,
                null,
                $this->get_groupid(),
                [$this->get_param('download'), $this->get_download_filename()],
                $teamresolver,
                $this->get_user_id()
            );
            $table->define_baseurl($this->pageurl->get_compatible_url());
            $table->set_filter_by_rule_ids($this->get_filter_by_rule_ids());
            $table->set_filterset($this->get_filterset());
            $logger = di::get('world_logger_factory')->get_logger_for_world($this->world);
            $table->set_collection_logger($logger);
            $this->table = $table;
        }
        return $this->table;
    }

    /**
     * Prepare content.
     *
     * @return void
     */
    protected function pre_content() {

        // We must send the table before the output starts.
        $table = $this->get_table();
        if ($table->is_downloading()) {
            $table->send_file();
        }

        parent::pre_content();
    }

    /**
     * Get dismissable filters.
     *
     * @return array|null
     */
    protected function get_dismissable_filters() {
        $filters = parent::get_dismissable_filters();
        $ruleid = $this->get_rule_id();
        if (!$ruleid) {
            return $filters;
        }

        $rulelessurl = new url($this->pageurl);
        $rulelessurl->remove_params('ruleid');
        $label = get_string('resultsfilteredforarule', 'block_xp');
        $rule = $this->get_rule();
        if ($rule) {
            $label = get_string(
                'resultsfilteredforrulen',
                'block_xp',
                di::get('rule_descriptor')->get_full_description($rule)
            );
        }
        $filters[] = [
            'label' => $label,
            'removeurl' => $rulelessurl,
        ];
        return $filters;
    }

    /**
     * Get page menu items.
     *
     * @return array
     */
    protected function get_page_menu_items() {
        return array_merge(parent::get_page_menu_items(), [
            [
                'label' => get_string('exportdata', 'block_xp'),
                'data-xp-action' => 'open-form',
                'data-form-class' => 'local_xp\\form\\table_download',
                'data-form-args__contextid' => $this->world->get_context()->id,
                'data-form-args__filename' => $this->get_download_filename(),
                'data-form-args__pageurl' => $this->pageurl->out_as_local_url(false),
                'data-modal-buttons__save__label' => get_string('export', 'block_xp'),
                'href' => '#',
            ],
        ]);
    }
}
