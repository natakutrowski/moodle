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
// https://levelup.plus

namespace local_xp\local\controller;

use block_xp\di;
use block_xp\local\routing\url;
use block_xp\local\rule\instance;
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

    protected function define_optional_params() {
        $params = parent::define_optional_params();
        $params[] = ['ruleid', null, PARAM_INT];
        $params[] = ['download', '', PARAM_ALPHA, false];
        $params[] = ['downloadfilename', '', PARAM_NOTAGS, false];
        return $params;
    }

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

            $manager = di::get('world_rule_manager_factory')->get_rule_manager($this->world);
            $rule = $manager->get_rule($ruleid);
            if (!$rule) {
                $ruleid = 0;
            }

            $this->rule = $rule;
            $this->ruleid = $ruleid;
        }
        return $this->ruleid;
    }

    protected function get_table() {
        if (!$this->table) {
            $teamresolver = null;
            $withteams = $this->world->get_config()->get('enablegroupladder') != default_course_world_config::GROUP_LADDER_NONE;
            if ($withteams) {
                $teamresolverfactory = \block_xp\di::get('team_membership_resolver_factory');
                $teamresolver = $teamresolverfactory->get_course_team_membership_resolver($this->world);
            }
            $table = new logs_table(
                $this->world,
                di::get('reason_from_log_entry_factory'),
                $this->get_groupid(),
                [$this->get_param('download'), $this->get_download_filename()],
                $teamresolver,
                $this->get_user_id()
            );
            $table->define_baseurl($this->pageurl->get_compatible_url());
            $table->set_filter_by_rule_id($this->get_rule_id());
            $table->set_filterset($this->get_filterset());
            $this->table = $table;
        }
        return $this->table;
    }

    protected function pre_content() {

        // We must send the table before the output starts.
        $table = $this->get_table();
        if ($table->is_downloading()) {
            $table->send_file();
        }

        parent::pre_content();
    }

    protected function get_dismissable_filters() {
        $filters = parent::get_dismissable_filters();
        $ruleid = $this->get_rule_id();
        if (!$ruleid) {
            return $filters;
        }

        $rulelessurl = new url($this->pageurl);
        $rulelessurl->remove_params('ruleid');
        $rule = $this->rule;
        $filters[] = [
            'label' => get_string('resultsfilteredforrulen',
                'block_xp',
                $rule ? di::get('rule_descriptor')->get_full_description($rule) : get_string('unknown', 'block_xp')
            ),
            'removeurl' => $rulelessurl,
        ];
        return $filters;
    }

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
