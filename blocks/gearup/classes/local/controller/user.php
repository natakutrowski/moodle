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
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller;

use block_gearup\di;
use block_gearup\local\controller\utils\route_base;
use block_gearup\local\exporter\user_exporter;
use core_user;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user extends route_base {

    /** @var object The repository. */
    protected $repository;
    /** @var object The user. */
    protected $user;

    protected $supportsgroups = true;

    protected function post_login() {
        parent::post_login();
        $this->user = core_user::get_user($this->get_param('userid'), '*', MUST_EXIST);
        if (!\core_user::is_real_user($this->user->id) || isguestuser($this->user)) {
            throw new \moodle_exception('invaliduser', 'core_error');
        }
        $this->repository = di::get('repository');
    }

    protected function pre_content() {
        parent::pre_content();

        // If the user does not current have any instance in this context, we must check
        // a capability otherwise user information could be leaked to undesired users.
        if (!$this->repository->has_any_instances_in($this->user->id, $this->context)) {
            require_capability('moodle/user:viewdetails', $this->context);
        }
    }

    protected function get_page_html_head_title() {
        return fullname($this->user);
    }

    protected function content() {
        $achievements = $this->repository->get_achievement_instances_by_subject_id($this->user->id,
            null,
            $this->context,
            ['m.title ASC']
        );
        $quests = $this->repository->get_quest_instances_by_subject_id($this->user->id,
            null,
            $this->context,
            ['mi.timestarted ASC', 'mi.timecreated ASC']
        );

        $challenges = [];
        if ($this->lm->use_challenges()) {
            $challenges = $this->repository->get_challenges_by_subject_id($this->user->id, $this->context);
        }

        $streaks = [];
        if ($this->lm->use_streaks()) {
            $streaks = $this->repository->get_latest_streak_instances($this->user->id, $this->context);
        }

        $ef = di::get('exporter_factory');
        $output = $this->get_renderer();
        echo $output->navigation_for_management($this->urlresolver, 'users');
        echo $output->render_from_template('block_gearup/user', [
            'listurl' => $this->urlresolver->reverse('users'),
            'subject' => (new user_exporter($this->user, ['context' => $this->context]))->export($output),

            'hasquests' => !empty($quests),
            'quests' => array_values(array_map(function ($mi) use ($ef, $output) {
                $exporter = $ef->get_mission_instance_exporter($mi, ['url_resolver' => $this->urlresolver]);
                return $exporter->export($output);
            }, $quests)),

            'hasachievements' => !empty($achievements),
            'achievements' => array_values(array_map(function ($mi) use ($ef, $output) {
                $exporter = $ef->get_mission_instance_exporter($mi, ['url_resolver' => $this->urlresolver]);
                return $exporter->export($output);
            }, $achievements)),

            'haschallenges' => !empty($challenges),
            'challenges' => array_values(array_map(function ($m) use ($ef, $output) {
                $exporter = $ef->get_mission_exporter($m);
                $data = $exporter->export($output, ['url_resolver' => $this->urlresolver]);
                return [
                    'manageurl' => $this->urlresolver->reverse('mission_user',
                        ['missionid' => $m->get_id(), 'userid' => $this->user->id]
                    ),
                ] + (array) $data;
            }, $challenges)),

            'hasstreaks' => !empty($streaks),
            'streaks' => array_values(array_map(function ($mi) use ($ef, $output) {
                $exporter = $ef->get_mission_instance_exporter($mi, ['url_resolver' => $this->urlresolver]);
                return $exporter->export($output);
            }, $streaks)),
        ]);
    }

}
