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
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller;

use block_gearup\di;
use block_gearup\local\controller\utils\route_base;
use block_gearup\local\mission\achievement;
use block_gearup\local\mission\challenge;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\quest;
use block_gearup\local\repository\mission_instance_query;
use block_gearup\local\repository\mission_query;
use block_gearup\local\repository\user_query;
use block_gearup\local\utils\collection_utils;
use block_gearup\local\utils\human_utils;
use block_gearup\output\group_switcher;
use block_gearup\table\missions;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class insights extends route_base {

    /** @var string[] */
    protected $allowedtypes = [];
    protected $supportsgroups = true;

    protected function define_optional_params() {
        return [
            ['download', null, PARAM_ALPHANUMEXT, false],
            ['downloadfilename', null, PARAM_NOTAGS, false],
        ];
    }

    protected function post_login() {
        parent::post_login();
        if (!$this->lm->use_insights()) {
            return redirect($this->urlresolver->reverse('missions'));
        }

        $this->allowedtypes = [
            achievement::class,
            quest::class,
            challenge::class,
        ];
    }

    protected function get_download_filename() {
        return $this->get_param('downloadfilename') ?: 'missions-' . $this->context->id . '-report-' . date('Y-m-d');
    }

    protected function get_page_html_head_title() {
        return get_string('insights', 'block_gearup');
    }

    protected function pre_content() {
        parent::pre_content();
        if ($this->get_param('download')) {
            $table = $this->get_table();
            if ($table->is_downloading()) {
                $table->send_file();
            }
        }
    }

    protected function get_table() {
        $query = new mission_query($this->context);
        $query->set_context_id($this->context->id);
        $query->set_group_id($this->get_group_id());
        $query->filter_types($this->allowedtypes);

        $table = new missions();
        $table->define_baseurl($this->pageurl);
        $table->define_query($query);
        $table->define_download_format($this->get_param('download'));
        $table->define_download_filename($this->get_download_filename());
        $table->init();

        return $table;
    }

    protected function get_stats() {
        $repo = di::get('repository');
        $groupid = $this->get_group_id();

        $hasachievements = $repo->has_achievements_in($this->context);
        $haschallenges = $repo->has_challenges_in($this->context);
        $hasquests = $repo->has_quests_in($this->context);

        $recruitquery = (new user_query($this->context))
            ->set_context_id($this->context->id)
            ->set_group_id($groupid)
            ->filter_by_mission_types($this->allowedtypes);
        $ongoingquery = (new mission_instance_query($this->context))
            ->set_context_id($this->context->id)
            ->set_group_id($groupid)
            ->set_mission_state(mission::STATE_ACTIVE)  // Exclude archived from ongoing.
            ->filter_by_mission_types($this->allowedtypes)
            ->filter_by_status('is_started');
        $completedquery = (new mission_instance_query($this->context))
            ->set_context_id($this->context->id)
            ->set_group_id($groupid)
            ->set_mission_state(mission::STATE_ACTIVE)  // Exclude archived from completed.
            ->filter_by_mission_types([achievement::class, quest::class])
            ->filter_by_status('has_completed');

        $missioncount = $repo->count_missions($this->context);
        $recruitcount = $repo->count_users_from_query($recruitquery);
        $ongoingcount = $repo->count_instances_from_query($ongoingquery);

        $completedcount = null;
        if ($hasachievements || $hasquests) {
            $completedcount = $repo->count_instances_from_query($completedquery);
        }

        $ef = di::get('exporter_factory');
        $stuff = function ($query, $valuegetter) use ($repo, $ef) {
            $count = $repo->count_missions_from_query($query);
            $items = [];
            if ($count > 0) {
                $items = collection_utils::iterable_to_array($repo->get_missions_from_query($query, 0, 5));
            }
            return [
                'items' => array_values(array_map(function ($item) use ($valuegetter, $ef) {
                    $me = $ef->get_mission_exporter($item->mission, [
                        'url_resolver' => $this->urlresolver,
                    ]);
                    return [
                        'mission' => $me->export($this->get_renderer()),
                        'value' => $valuegetter($item),
                    ];
                }, $items)),
                'hasitems' => !empty($items),
            ];
        };

        $query = (new mission_query($this->context))
            ->set_context_id($this->context->id)
            ->set_group_id($groupid)
            ->set_type(quest::class)
            ->filter_has_inprogress()
            ->annotate_inprogress_average_rate()
            ->add_order_by_inprogress_average_rate(SORT_ASC)
            ->add_order_by('title', SORT_ASC);
        $leastinprogressratequests = false ? $stuff($query, function ($item) {
            return human_utils::percentage($item->annotations->inprogress_average_rate) . '%';
        }) : null;

        $query = (new mission_query($this->context))
            ->set_context_id($this->context->id)
            ->set_group_id($groupid)
            ->set_type(quest::class)
            ->filter_has_completed()
            ->annotate_average_completion_time()
            ->add_order_by_average_completion_time(SORT_ASC)
            ->add_order_by('title', SORT_ASC);
        $fastestquests = $hasquests ? $stuff($query, function ($item) {
            return $this->format_tiny_time(floor($item->annotations->average_completion_time));
        }) : null;

        $query = (new mission_query($this->context))
            ->set_context_id($this->context->id)
            ->set_group_id($groupid)
            ->set_type(quest::class)
            ->filter_has_completed()
            ->annotate_average_completion_time()
            ->add_order_by_average_completion_time(SORT_DESC)
            ->add_order_by('title', SORT_ASC);
        $slowestquests = $hasquests ? $stuff($query, function ($item) {
            return $this->format_tiny_time(floor($item->annotations->average_completion_time));
        }) : null;

        $query = (new mission_query($this->context))
            ->set_context_id($this->context->id)
            ->set_group_id($groupid)
            ->set_type(achievement::class)
            ->annotate_completion_rate()
            ->filter_has_completed()
            ->add_order_by_completion_rate(SORT_DESC)
            ->add_order_by('title', SORT_ASC);
        $mostunlocked = $hasachievements ? $stuff($query, function ($item) {
            return human_utils::percentage($item->annotations->completion_rate) . '%';
        }) : null;

        $query = (new mission_query($this->context))
            ->set_context_id($this->context->id)
            ->set_group_id($groupid)
            ->set_type(achievement::class)
            ->annotate_completion_rate()
            ->filter_has_completed()
            ->add_order_by_completion_rate(SORT_ASC)
            ->add_order_by('title', SORT_ASC);
        $leastunlocked = $hasachievements ? $stuff($query, function ($item) {
            return human_utils::percentage($item->annotations->completion_rate) . '%';
        }) : null;

        $query = (new mission_query($this->context))
            ->set_context_id($this->context->id)
            ->set_group_id($groupid)
            ->set_type(challenge::class)
            ->annotate_success_rate()
            ->filter_has_completed()
            ->add_order_by_success_rate(SORT_ASC)
            ->add_order_by('title', SORT_ASC);
        $leastsuccess = $haschallenges ? $stuff($query, function ($item) {
            return human_utils::percentage($item->annotations->success_rate) . '%';
        }) : null;

        $query = (new mission_query($this->context))
            ->set_context_id($this->context->id)
            ->set_group_id($groupid)
            ->set_type(challenge::class)
            ->annotate_success_rate()
            ->filter_has_completed()
            ->add_order_by_success_rate(SORT_DESC)
            ->add_order_by('title', SORT_ASC);
        $mostsuccess = $haschallenges ? $stuff($query, function ($item) {
            return human_utils::percentage($item->annotations->success_rate) . '%';
        }) : null;

        return [
            'missioncount' => $missioncount,
            'recruitcount' => $recruitcount,
            'ongoingcount' => $ongoingcount,
            'completedcount' => $completedcount,

            'leastinprogressratequests' => $leastinprogressratequests,
            'hasleastinprogressratequests' => $leastinprogressratequests !== null,
            'fastestquests' => $fastestquests,
            'hasfastestquests' => $fastestquests !== null,
            'slowestquests' => $slowestquests,
            'hasslowestquests' => $slowestquests !== null,
            'mostunlocked' => $mostunlocked,
            'hasmostunlocked' => $mostunlocked !== null,
            'leastunlocked' => $leastunlocked,
            'hasleastunlocked' => $leastunlocked !== null,
            'leastsuccess' => $leastsuccess,
            'hasleastsuccess' => $leastsuccess !== null,
            'mostsuccess' => $mostsuccess,
            'hasmostsuccess' => $mostsuccess !== null,

            'hasachievements' => $hasachievements,
            'haschallenges' => $haschallenges,
            'hasquests' => $hasquests,
            'hasany' => $hasquests || $haschallenges || $hasachievements,
        ];
    }

    protected function content() {
        global $PAGE;
        $output = $this->get_renderer();
        $stats = $this->get_stats();

        echo $output->navigation_for_management($this->urlresolver, 'insights');
        echo $output->advanced_heading('', [
            'intro' => get_string('insightsintro', 'block_gearup'),
            'menu' => array_filter([
                [
                    'label' => get_string('downloadrawdata', 'block_gearup'),
                    'data-gu-action' => 'open-form',
                    'data-form-class' => 'block_gearup\form\table_download_dynamic_form',
                    'data-form-args__pageurl' => $this->get_page_url_for_actions()->get_compatible_url(false),
                    'data-form-args__filename' => $this->get_download_filename(),
                    'data-form-args__guctxid' => $this->context->id,
                    'data-modal-buttons__save__label' => get_string('download', 'core'),
                    'data-modal-title' => get_string('downloadrawdata', 'block_gearup'),
                    'href' => '#',
                    'disabled' => !$stats['hasany'],
                ],
            ]),
        ]);

        if ($this->is_page_using_groups()) {
            echo $output->render(new group_switcher($this->pageurl, $this->context, $this->get_group_id()));
        }

        echo $output->render_from_template('block_gearup/insights', [
            'highlights' => array_filter([
                ['label' => get_string('recruits', 'block_gearup'), 'value' => $stats['recruitcount']],
                ['label' => get_string('missions', 'block_gearup'), 'value' => $stats['missioncount']],
                ['label' => get_string('inprogress', 'block_gearup'), 'value' => $stats['ongoingcount']],
                $stats['completedcount'] !== null ? ['label' => get_string('completed', 'block_gearup'),
                    'value' => $stats['completedcount']] : null,
            ]),
        ] + $stats);
    }

    protected function format_tiny_time($value) {
        if ($value < 60) {
            return $this->format_scale_value(60, '<1');
        }
        $value = human_utils::duration_approx($value);
        $scale = human_utils::time_scale($value);
        return $this->format_scale_value($scale, floor($value / $scale));
    }

    protected function format_scale_value($scale, $value) {
        $str = 'tinytimeminutes';
        if ($scale === YEARSECS) {
            $str = 'tinytimeyears';
        } else if ($scale === WEEKSECS) {
            $str = 'tinytimeweeks';
        } else if ($scale === DAYSECS) {
            $str = 'tinytimedays';
        } else if ($scale === HOURSECS) {
            $str = 'tinytimehours';
        }
        return get_string($str, 'block_gearup', $value);
    }

}
