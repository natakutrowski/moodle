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
 * Mission controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller;

use block_gearup\di;
use block_gearup\local\controller\utils\mission_route_base;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\model\mission_inst;
use block_gearup\local\repository\user_query;
use block_gearup\local\utils\human_utils;
use block_gearup\output\group_switcher;
use core\chart_axis;
use core\chart_bar;
use core\chart_series;

/**
 * Mission controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_insights extends mission_route_base {

    protected $form;
    protected $missionnavname = 'mission_insights';
    protected $supportsgroups = true;

    protected function post_login() {
        parent::post_login();
        if (!$this->lm->use_insights()) {
            return redirect($this->get_mission_url());
        }
    }

    protected function get_group_sql($useridfield = 'subjectid') {
        static $i = 0;

        $sql = '1=1';
        $params = [];

        if ($groupid = $this->get_group_id()) {
            $paramname = 'groupid' . $i++;
            $sql = "$useridfield IN (SELECT gm.userid FROM {groups_members} gm WHERE gm.groupid = :$paramname)";
            $params = [$paramname => $groupid];
        }

        return [$sql, $params];
    }

    protected function get_subpage_html_head_title() {
        return get_string('insights', 'block_gearup');
    }

    protected function get_complete_vs_incomplete_insight(mission $mission) {
        $incompletecolor = '#64748B';
        $completedcolor = '#22C55E';
        $missionid = $mission->get_id();

        $defaultdata = (object) [
            'total' => 0,
            'ncompletedratio' => 0,
            'title' => get_string('completedvsincomplete', 'block_gearup'),
            'chart' => null,
        ];

        // The number of users that have started the mission.
        [$groupsql, $groupparams] = $this->get_group_sql();
        $total = mission_inst::count_records_select("missionid = :missionid AND state IN (:state1, :state2, :state3) AND $groupsql",
            [
                'missionid' => $missionid,
                'state1' => mission_instance::STATE_STARTED,
                'state2' => mission_instance::STATE_COMPLETED,
                'state3' => mission_instance::STATE_ENDED,
            ] + $groupparams
        );

        // Nothing to show here.
        if (!$total) {
            return $defaultdata;
        }

        // The number of users who have completed, or ended, the mission.
        [$groupsql, $groupparams] = $this->get_group_sql();
        $ncompleted = mission_inst::count_records_select("missionid = :missionid AND state IN (:state1, :state2) AND $groupsql", [
            'missionid' => $missionid,
            'state1' => mission_instance::STATE_COMPLETED,
            'state2' => mission_instance::STATE_ENDED,
        ] + $groupparams);

        // Putting it together.
        $nincomplete = $total - $ncompleted;
        $ncompletedratio = $total ? $ncompleted / $total : 0;
        $nincompleteratio = $total ? $nincomplete / $total : 0;

        $datapoints = array_filter([
            [$nincomplete, $nincompleteratio, get_string('incomplete', 'block_gearup'), $incompletecolor],
            [$ncompleted, $ncompletedratio, get_string('completed', 'block_gearup'), $completedcolor],
        ]);

        // Building the chart.
        $series1 = new \core\chart_series(get_string('count', 'core_tag'), array_map(function ($dp) {
            return $dp[0];
        }, $datapoints));
        $series1->set_labels(array_map(function ($dp) {
            return $dp[0] . ' (' . format_float($dp[1] * 100, 1, true, true) . '%)';
        }, $datapoints));
        $series1->set_colors(array_map(function ($dp) {
            return $dp[3];
        }, $datapoints));

        $chart = new \core\chart_pie();
        $chart->add_series($series1);
        $chart->set_labels(array_map(function ($dp) {
            return $dp[2];
        }, $datapoints));

        return (object) array_merge((array) $defaultdata, [
            'total' => $total,
            'ncompletedratio' => $ncompletedratio,
            'chart' => $chart,
            'note' => get_string('insightcompletevsincomplete', 'block_gearup', [
                'total' => $total,
                'ncompleted' => $ncompleted,
                'ncompletedpc' => human_utils::percentage($ncompletedratio),
                'nincomplete' => $nincomplete,
                'nincompletepc' => human_utils::percentage($nincompleteratio),
            ])]);
    }

    protected function get_challenge_success_rate(mission $mission) {
        $incompletecolor = '#64748B';
        $completedcolor = '#22C55E';
        $missionid = $mission->get_id();

        $defaultdata = (object) [
            'total' => 0,
            'ncompletedratio' => 0,
            'title' => get_string('successrate', 'block_gearup'),
            'chart' => null,
        ];

        // The number of completed challenges.
        [$groupsql, $groupparams] = $this->get_group_sql();
        $total = mission_inst::count_records_select("missionid = :missionid AND state IN (:state1, :state2) AND $groupsql", [
            'missionid' => $missionid,
            'state1' => mission_instance::STATE_COMPLETED,
            'state2' => mission_instance::STATE_ENDED,
        ] + $groupparams);

        // Nothing to show here.
        if (!$total) {
            return $defaultdata;
        }

        // The number of users who have successfully completed the mission.
        [$groupsql, $groupparams] = $this->get_group_sql();
        $ncompleted = mission_inst::count_records_select("missionid = :missionid
                AND state IN (:state1, :state2) AND completionratio >= 1 AND $groupsql", [
            'missionid' => $missionid,
            'state1' => mission_instance::STATE_COMPLETED,
            'state2' => mission_instance::STATE_ENDED,
        ] + $groupparams);

        // Putting it together.
        $nincomplete = $total - $ncompleted;
        $ncompletedratio = $total ? $ncompleted / $total : 0;
        $nincompleteratio = $total ? $nincomplete / $total : 0;

        $datapoints = array_filter([
            [$nincomplete, $nincompleteratio, get_string('incomplete', 'block_gearup'), $incompletecolor],
            [$ncompleted, $ncompletedratio, get_string('success', 'block_gearup'), $completedcolor],
        ]);

        // Building the chart.
        $series1 = new \core\chart_series(get_string('count', 'core_tag'), array_map(function ($dp) {
            return $dp[0];
        }, $datapoints));
        $series1->set_labels(array_map(function ($dp) {
            return $dp[0] . ' (' . format_float($dp[1] * 100, 1, true, true) . '%)';
        }, $datapoints));
        $series1->set_colors(array_map(function ($dp) {
            return $dp[3];
        }, $datapoints));

        $chart = new \core\chart_pie();
        $chart->add_series($series1);
        $chart->set_labels(array_map(function ($dp) {
            return $dp[2];
        }, $datapoints));

        return (object) array_merge((array) $defaultdata, [
            'total' => $total,
            'ncompletedratio' => $ncompletedratio,
            'chart' => $chart,
            'note' => get_string('insightchallengecompletionrate', 'block_gearup', [
                'total' => $total,
                'avgrate' => human_utils::percentage($ncompletedratio),
            ])]);
    }

    protected function get_completion_rate_insight(mission $mission) {
        global $DB;

        $colours = ['#64748B', '#5E7B87', '#588383', '#528A7F', '#4C917B', '#469977', '#40A072', '#3AA86E', '#34AF6A',
            '#2EB666', '#28BE62'];
        $missionid = $mission->get_id();

        $defaultdata = (object) [
            'avgprogress' => 0,
            'title' => get_string('ongoingprogress', 'block_gearup'),
            'chart' => null,
        ];

        // Completion rate.
        [$groupsql, $groupparams] = $this->get_group_sql('mi.subjectid');
        $subsql = "SELECT CASE
                            WHEN mi.completionratio <= 0 THEN -10
                            ELSE FLOOR(mi.completionratio * 10) * 10
                        END AS bucket
                     FROM {" . mission_inst::TABLE . "} mi
                    WHERE mi.missionid = :missionid
                      AND mi.state = :state
                      AND $groupsql
                ";
        $sql = "SELECT s.bucket, COUNT(s.bucket) FROM ($subsql) s GROUP BY s.bucket";
        $params = [
            'missionid' => $missionid,
            'state' => mission_instance::STATE_STARTED,
        ] + $groupparams;

        $seriesdata = $DB->get_records_sql_menu($sql, $params);
        for ($i = -10; $i < 100; $i += 10) {
            if (!isset($seriesdata[$i])) {
                $seriesdata[$i] = 0;
            }
        }
        ksort($seriesdata);
        unset($seriesdata[100]); // Fail safe.
        $total = array_sum($seriesdata);

        // TODO We should return a meaningful placeholder here, if there are users who have completed it, but none in progress.
        if ($total <= 0) {
            return $defaultdata;
        }

        [$groupsql, $groupparams] = $this->get_group_sql();
        $avgcompletionrate = $DB->get_field_select(mission_inst::TABLE,
            'AVG(completionratio)',
            "missionid = :missionid AND state = :state AND $groupsql",
            [
            'missionid' => $missionid,
            'state' => mission_instance::STATE_STARTED,
            ] + $groupparams
        );

        $series = new chart_series(get_string('progressrate', 'block_gearup'), array_values(array_map(function ($n) use ($total) {
            if (!$n || !$total) {
                return 0;
            }
            return round($n / $total * 100, 1);
        }, $seriesdata)));
        $series->set_labels(array_values(array_map(function ($n) use ($total) {
            if (!$n || !$total) {
                return null;
            }
            return format_float($n / $total * 100, 1, true, true) . "% ($n)";
        }, $seriesdata)));
        $series->set_colors($colours);

        $chart = new chart_bar();
        $chart->add_series($series);
        $chart->set_labels(array_map(function ($pc) {
            if ($pc < 0) {
                return '0%';
            } else if (!$pc) {
                return '<10%';
            }
            return $pc . '%';
        }, array_keys($seriesdata)));

        [$ymax, $ystep] = $this->get_scale_step($seriesdata, $total);
        $yaxis = new chart_axis();
        $yaxis->set_label(get_string('participantspc', 'block_gearup'));
        $yaxis->set_min(0);
        $yaxis->set_max($ymax);
        $yaxis->set_stepsize($ystep);

        $xaxis = new chart_axis();

        $chart->set_xaxis($xaxis);
        $chart->set_yaxis($yaxis);

        return (object) array_merge((array) $defaultdata, [
            'avgprogress' => $avgcompletionrate,
            'chart' => $chart,
            'note' => get_string('insightcompleterate', 'block_gearup', [
                'avgrate' => human_utils::percentage($avgcompletionrate),
                'total' => $total,
                'nzero' => $seriesdata[-10],
                'nzeropc' => human_utils::percentage($seriesdata[-10] / $total),
            ])]);
    }

    protected function get_completion_time_insight(mission $mission) {
        global $DB;

        $completedcolor = '#22C55E';
        $defaultdata = (object) [
            'avgtime' => 0,
            'title' => get_string('timetocomplete', 'block_gearup'),
            'chart' => null,
        ];

        $completedchallengesql = '1=1';
        if ($this->is_challenge()) {
            $completedchallengesql = "mi.completionratio >= 1";
        }

        // Time to completion.
        [$groupsql, $groupparams] = $this->get_group_sql('mi.subjectid');
        $sql = "SELECT MIN(mi.timecompleted - mi.timestarted) AS timemin,
                       MAX(mi.timecompleted - mi.timestarted) AS timemax,
                       ROUND(AVG(mi.timecompleted - mi.timestarted)) AS timeavg
                  FROM {" . mission_inst::TABLE . "} mi
                 WHERE mi.state IN (:completed, :ended)
                   AND mi.missionid = :missionid
                   AND mi.timestarted > 0
                   AND mi.timecompleted > 0
                   AND $completedchallengesql
                   AND $groupsql";
        $params = [
            'completed' => mission_instance::STATE_COMPLETED,
            'ended' => mission_instance::STATE_ENDED,
            'missionid' => $mission->get_id(),
        ] + $groupparams;
        $values = $DB->get_record_sql($sql, $params);
        $timemin = $values->timemin ?? 0;
        $timemax = $values->timemax ?? 0;
        $timeavg = $values->timeavg ?? 0;
        ;
        if ($timemin <= 0 && $timemax <= 0) {
            return $defaultdata;
        }

        $diff = $timemax == $timemin ? $timemax : ($timemax - $timemin);
        $scale = human_utils::time_scale($diff);

        [$groupsql, $groupparams] = $this->get_group_sql('mi.subjectid');
        $tsql = "FLOOR((mi.timecompleted - mi.timestarted) / $scale)";
        $sql = "SELECT $tsql AS t, COUNT(1) AS n
                  FROM {" . mission_inst::TABLE . "} mi
                 WHERE mi.state IN (:completed, :ended)
                   AND mi.missionid = :missionid
                   AND mi.timestarted > 0
                   AND mi.timecompleted > 0
                   AND $completedchallengesql
                   AND $groupsql
              GROUP BY $tsql";
        $params = [
            'completed' => mission_instance::STATE_COMPLETED,
            'ended' => mission_instance::STATE_ENDED,
            'missionid' => $mission->get_id(),
        ] + $groupparams;

        $minv = floor($timemin / $scale);
        $maxv = floor($timemax / $scale);
        $nitems = ($maxv - $minv) + 1;

        $seriesdata = $DB->get_records_sql_menu($sql, $params);
        for ($i = 0; $i < $nitems; $i++) {
            $key = $i + $minv;
            if (!isset($seriesdata[$key])) {
                $seriesdata[$key] = 0;
            }
        }

        ksort($seriesdata);
        $total = array_sum($seriesdata);
        $series = new chart_series(get_string('completiontime', 'block_gearup'), array_values(array_map(function ($n) use ($total) {
            if (!$n || !$total) {
                return 0;
            }
            return round($n / $total * 100, 1);
        }, $seriesdata)));
        $series->set_labels(array_values(array_map(function ($n) use ($total) {
            if (!$n || !$total) {
                return null;
            }
            return format_float($n / $total * 100, 1, true, true) . "% ($n)";
        }, $seriesdata)));
        $series->set_color($completedcolor);

        $chart = new chart_bar();
        $chart->add_series($series);
        $chart->set_labels(array_map(function ($t) use ($scale) {
            return $this->format_scale_value($scale, $t ? $t : '<1');
        }, array_keys($seriesdata)));

        [$ymax, $ystep] = $this->get_scale_step($seriesdata, $total);

        $yaxis = new chart_axis();
        $yaxis->set_label(get_string('participantspc', 'block_gearup'));
        $yaxis->set_min(0);
        $yaxis->set_max($ymax);
        $yaxis->set_stepsize($ystep);
        $xaxis = new chart_axis();
        $xaxis->set_label(get_string('completiontimein', 'block_gearup', $this->get_scale_string($scale)));

        $chart->set_xaxis($xaxis);
        $chart->set_yaxis($yaxis);

        return (object) array_merge((array) $defaultdata, [
            'avgtime' => $timeavg,
            'chart' => $chart,
            'note' => get_string('insightcompletetime', 'block_gearup', [
                'avgtime' => $this->format_time($timeavg),
                'fastest' => $this->format_time($timemin),
                'slowest' => $this->format_time($timemax),
            ]),
        ]);
    }

    protected function get_streak_active_insight(mission $mission, $activeonly = true) {
        global $DB;

        $incompletecolor = '#64748B';
        $completedcolor = '#22C55E';

        $missionid = $mission->get_id();
        $defaultdata = (object) [
            'title' => get_string('activevsinactive', 'block_gearup'),
            'chart' => null,
        ];

        // Count active recruits.
        [$groupsql, $groupparams] = $this->get_group_sql('mi.subjectid');
        $sql = "SELECT COUNT(DISTINCT subjectid)
                  FROM {block_gearup_mission_inst} mi
                  WHERE mi.missionid = :missionid
                    AND mi.state != :state
                    AND mi.counter > 0
                    AND $groupsql";
        $params = ['missionid' => $missionid, 'state' => mission_instance::STATE_ENDED] + $groupparams;
        $nactive = $DB->count_records_sql($sql, $params);

        // Count inactive recruits.
        [$groupsql, $groupparams] = $this->get_group_sql('mi.subjectid');
        $sql = "SELECT COUNT(DISTINCT subjectid)
                  FROM {block_gearup_mission_inst} mi
                  WHERE mi.missionid = :missionid
                    AND mi.state != :state
                    AND mi.counter = 0
                    AND $groupsql";
        $params = ['missionid' => $missionid, 'state' => mission_instance::STATE_ENDED] + $groupparams;
        $ninactive = $DB->count_records_sql($sql, $params);
        $total = $nactive + $ninactive;

        if ($total <= 0) {
            return $defaultdata;
        }

        // Putting it together.
        $activeratio = $total ? $nactive / $total : 0;
        $inactiveratio = $total ? $ninactive / $total : 0;
        $datapoints = array_filter([
            [$ninactive, $inactiveratio, get_string('inactive', 'block_gearup'), $incompletecolor],
            [$nactive, $activeratio, get_string('active', 'block_gearup'), $completedcolor],
        ]);

        // Building the chart.
        $series1 = new \core\chart_series(get_string('count', 'core_tag'), array_map(function ($dp) {
            return $dp[0];
        }, $datapoints));
        $series1->set_labels(array_map(function ($dp) {
            return $dp[0] . ' (' . format_float($dp[1] * 100, 1, true, true) . '%)';
        }, $datapoints));
        $series1->set_colors(array_map(function ($dp) {
            return $dp[3];
        }, $datapoints));

        $chart = new \core\chart_pie();
        $chart->add_series($series1);
        $chart->set_labels(array_map(function ($dp) {
            return $dp[2];
        }, $datapoints));

        return (object) array_merge((array) $defaultdata, [
            'chart' => $chart,
            'note' => get_string('insightstreakactivevsinactive', 'block_gearup', [
                'total' => $total,
                'inactive' => $ninactive,
                'inactivepc' => human_utils::percentage($inactiveratio),
                'active' => $nactive,
                'activepc' => human_utils::percentage($activeratio),
            ])]);
    }

    protected function get_streak_spread_insight(mission $mission, $activeonly = true) {
        global $DB;

        $missionid = $mission->get_id();
        $defaultdata = (object) [
            'title' => get_string('currentstreaks', 'block_gearup'),
            'chart' => null,
        ];

        // Fetch streaks.
        [$groupsql, $groupparams] = $this->get_group_sql('mi.subjectid');
        $sql = "SELECT s.c, COUNT(s.c) AS n
                  FROM (SELECT MAX(mi.counter) AS c
                          FROM {block_gearup_mission_inst} mi
                         WHERE mi.missionid = :missionid
                           AND mi.state != :state
                           AND $groupsql
                      GROUP BY mi.subjectid) s
               GROUP BY s.c
               ORDER BY s.c";
        $params = ['missionid' => $missionid, 'state' => mission_instance::STATE_ENDED] + $groupparams;

        $seriesdata = $DB->get_records_sql_menu($sql, $params);
        $minstreak = 0;
        $maxstreak = max(5, max(array_keys($seriesdata ?: [0])));
        $totalstreaks = 0;
        for ($i = $minstreak; $i <= $maxstreak; $i++) {
            $totalstreaks += $i * ($seriesdata[$i] ?? 0);
            if (!isset($seriesdata[$i])) {
                $seriesdata[$i] = 0;
            }
        }
        ksort($seriesdata);
        $total = array_sum($seriesdata);

        // Nobody has made any progress.
        if ($total <= 0) {
            return $defaultdata;
        }

        $series = new chart_series(get_string('recruits', 'block_gearup'), array_values(array_map('intval', $seriesdata)));
        $chart = new chart_bar();
        $chart->set_labels(array_keys($seriesdata));
        $chart->add_series($series);
        $chart->get_yaxis(0, true)->set_label(get_string('streaks', 'block_gearup'));

        $highest = max(array_keys(array_filter($seriesdata)));
        $nactive = array_sum(array_slice($seriesdata, 1));
        $average = $nactive > 0 ? $totalstreaks / $nactive : 0;

        return (object) array_merge((array) $defaultdata, [
            'chart' => $chart,
            'note' => get_string('insightcurrentstreak', 'block_gearup', [
                'avg' => round($average, 1),
                'zero' => $seriesdata[0] ?? 0,
                'total' => $totalstreaks,
                'highest' => $highest,
            ])]);
    }

    protected function get_recruits_count() {
        $repository = di::get('repository');
        $query = (new user_query($this->context))
            ->set_context_id($this->context->id)
            ->set_group_id($this->get_group_id())
            ->set_mission_id($this->mission->get_id());
        return $repository->count_users_from_query($query);
    }

    protected function content() {
        parent::pre_content();

        $this->page_mission_header();
        $this->page_mission_navigation();
        $this->page_advanced_heading();

        $output = $this->get_renderer();
        if ($this->is_page_using_groups()) {
            echo $output->render(new group_switcher($this->pageurl, $this->context, $this->get_group_id()));
        }

        if ($this->is_streak()) {
            $this->page_insights_content_streak();
            return;
        }

        $this->page_insights_content();
    }

    protected function page_advanced_heading() {
        global $PAGE;
        $output = $this->get_renderer();

        $missionusersurl = $this->urlresolver->reverse('mission_users', ['missionid' => $this->mission->get_id()]);
        if ($this->get_group_id()) {
            $missionusersurl->param('group', $this->get_group_id());
        }

        echo $output->advanced_heading('', [
            'intro' => get_string('insightsexplaination', 'block_gearup'),
            'menu' => array_filter([
                $this->lm->use_export_recruits() ? [
                    'label' => get_string('downloadrawdata', 'block_gearup'),
                    'data-gu-action' => 'open-form',
                    'data-form-class' => 'block_gearup\form\table_download_dynamic_form',
                    'data-form-args__pageurl' => $missionusersurl->out_as_local_url(false),
                    'data-form-args__filename' => 'mission-' . $this->mission->get_id() . '-report-' . date('Y-m-d'),
                    'data-form-args__guctxid' => $this->context->id,
                    'data-modal-buttons__save__label' => get_string('download', 'core'),
                    'data-modal-title' => get_string('downloadrawdata', 'block_gearup'),
                    'disabled' => $this->get_recruits_count() <= 0,
                    'href' => '#',
                ] : null,
            ]),
        ]);
    }

    protected function page_insights_content() {
        if (!$this->is_quest() && !$this->is_challenge() && !$this->is_achievement()) {
            throw new \coding_exception('Unsupported mission type');
        }

        $mission = $this->mission;
        $output = $this->get_renderer();

        $repository = di::get('repository');
        $missionhelper = di::get('mission_helper');

        $ischallenge = $this->is_challenge();

        // Gather insights.
        $nrecruits = $this->get_recruits_count();
        $nchallenges = null;
        $completevsincomp = null;
        $completionrate = null;
        $successrate = null;
        if (!$ischallenge) {
            $completevsincomp = $this->get_complete_vs_incomplete_insight($mission);
            $completionrate = $this->get_completion_rate_insight($mission);
        } else {
            if ($missionhelper->is_repeating($mission)) {
                $nchallenges = $repository->count_instances_completed($mission->get_id());
            }
            $successrate = $this->get_challenge_success_rate($mission);
        }
        $completiontime = $this->get_completion_time_insight($mission);

        $data = [
            'stats' => array_values(array_filter([
                [
                    'value' => $nrecruits ?: '-',
                    'label' => get_string('recruits', 'block_gearup'),
                ],
                $nchallenges !== null ? [
                    'value' => $nchallenges ?: '-',
                    'label' => get_string('completed', 'block_gearup'),
                ] : null,
                $completevsincomp ? [
                    'value' => $completevsincomp->chart ? human_utils::percentage($completevsincomp->ncompletedratio) . '%' : '-',
                    'label' => get_string('completed', 'block_gearup'),
                ] : null,
                $completionrate ? [
                    'value' => $completionrate->chart ? human_utils::percentage($completionrate->avgprogress) . '%' : '-',
                    'label' => get_string('averageprogress', 'block_gearup'),
                ] : null,
                $successrate ? [
                    'value' => $successrate->chart ? human_utils::percentage($successrate->ncompletedratio) . '%' : '-',
                    'label' => get_string('successrate', 'block_gearup'),
                ] : null,
                [
                    'value' => $completiontime->chart ? $this->format_tiny_time($completiontime->avgtime) : '-',
                    'label' => get_string('averagetime', 'block_gearup'),
                ],
            ])),
            'charts' => array_values(array_map(function ($data) use ($output) {
                return array_merge((array) $data, ['chart' => $data->chart ? $output->render_chart($data->chart, false) : null]);
            }, array_filter([
                $successrate,
                $completevsincomp,
                $completionrate,
                $completiontime,
            ]))),
        ];

        echo $output->render_from_template('block_gearup/mission_insights', $data);
    }

    protected function page_insights_content_streak() {
        global $DB;

        $mission = $this->mission;
        $output = $this->get_renderer();
        $nrecruits = $this->get_recruits_count();

        [$groupsql, $groupparams] = $this->get_group_sql('mi.subjectid');
        $nongoing = $DB->get_field_sql("SELECT COUNT(DISTINCT mi.subjectid)
                                          FROM {block_gearup_mission_inst} mi
                                         WHERE mi.missionid = :missionid
                                           AND mi.state != :ended
                                           AND mi.counter > 0
                                           AND $groupsql", [
                                            'missionid' => $mission->get_id(),
                                            'ended' => mission_instance::STATE_ENDED] + $groupparams);
        [$groupsql, $groupparams] = $this->get_group_sql('mi.subjectid');
        $beststreakever = $DB->get_field_sql("SELECT MAX(mi.counter)
                                                FROM {block_gearup_mission_inst} mi
                                               WHERE mi.missionid = :missionid
                                                 AND $groupsql", ['missionid' => $mission->get_id()] + $groupparams);
        [$groupsql, $groupparams] = $this->get_group_sql('mi.subjectid');
        $avgstreak = $DB->get_field_sql("SELECT AVG(mi.counter)
                                           FROM {block_gearup_mission_inst} mi
                                          WHERE mi.missionid = :missionid
                                            AND mi.counter > 0
                                            AND $groupsql", ['missionid' => $mission->get_id()] + $groupparams);

        $data = [
            'stats' => array_values(array_filter([
                [
                    'value' => $nrecruits ?: '-',
                    'label' => get_string('recruits', 'block_gearup'),
                ],
                [
                    'value' => $nongoing ?: '-',
                    'label' => get_string('activestreaks', 'block_gearup'),
                ],
                [
                    'value' => $avgstreak > 0 ? round($avgstreak, 1) : '-',
                    'label' => get_string('overallaverage', 'block_gearup'),
                ],
                [
                    'value' => $beststreakever ?: '-',
                    'label' => get_string('beststreakever', 'block_gearup'),
                ],
            ])),
            'charts' => array_values(array_map(function ($data) use ($output) {
                return array_merge((array) $data, ['chart' => $data->chart ? $output->render_chart($data->chart, false) : null]);
            }, array_filter([
                $this->get_streak_spread_insight($mission),
                $this->get_streak_active_insight($mission),
            ]))),
        ];

        echo $output->render_from_template('block_gearup/mission_insights', $data);
    }

    protected function get_scale_step($seriesdata, $total) {
        $highestratio = array_reduce(array_keys($seriesdata), function ($carry, $key) use ($seriesdata) {
            $val = $seriesdata[$key];
            if ($val > $carry[0]) {
                return [$val, $key];
            }
            return $carry;
        }, [0, -10])[0] / $total * 100;
        $ymax = $highestratio > 0 ? min(100, ceil($highestratio / 10) * 10) : 100;
        // Max value, and step size.
        return [$ymax, $ymax >= 40 ? 10 : 5];
    }

    protected function format_time($value) {
        $value = human_utils::duration_approx($value);
        if ($value < 60) {
            return '<1 ' . get_string('minutes', 'core');
        }
        return format_time($value);
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

    protected function get_scale_string($scale) {
        $str = 'minutes';
        if ($scale === YEARSECS) {
            $str = 'years';
        } else if ($scale === WEEKSECS) {
            $str = 'weeks';
        } else if ($scale === DAYSECS) {
            $str = 'days';
        } else if ($scale === HOURSECS) {
            $str = 'hours';
        }
        return get_string($str, 'core');
    }
}
