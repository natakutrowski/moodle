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

namespace block_gearup\local\exporter\tracker;

use block_gearup\di;
use block_gearup\local\exporter\context_exporter;
use block_gearup\local\exporter\mission_instance_exporter;
use block_gearup\local\mission\mission_instance;
use renderer_base;

/**
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class achievements_tracker_exporter extends missions_tracker_exporter_base {

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'context' => [
                'type' => context_exporter::read_properties_definition(),
            ],
            'hasany' => [
                'type' => PARAM_BOOL,
            ],
            'instances' => [
                'type' => mission_instance_exporter::read_properties_definition(),
                'multiple' => true,
            ],
            'hasinstances' => [
                'type' => PARAM_BOOL,
            ],
            'recentlycompleted' => [
                'type' => mission_instance_exporter::read_properties_definition(),
                'multiple' => true,
            ],
            'hasrecentlycompleted' => [
                'type' => PARAM_BOOL,
            ],
            'total' => [
                'type' => PARAM_INT,
            ],
            'totalcompleted' => [
                'type' => PARAM_INT,
            ],
            'hasmore' => [
                'type' => PARAM_BOOL,
            ],
            'moreurl' => [
                'type' => PARAM_URL,
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
        $mr = di::get('repository');
        $ef = di::get('exporter_factory');
        $userid = $this->userid;
        $context = $this->related['context'];
        $urlresolver = $this->related['url_resolver'] ?? di::get('url_resolver');

        $ntohighlight = 3;
        $nachievements = $mr->count_achievement_instances_by_subject_id($userid, null, $context);
        $ncompletedachievements = 0;
        $instances = [];
        $recentlycompleted = [];
        $hascompletedall = empty($nachievements);
        $showinstancesonly = $nachievements <= $ntohighlight;

        // If we have any achievements.
        if ($nachievements) {

            // Check how many achievements we completed.
            $ncompletedachievements = $mr->count_achievement_instances_by_subject_id($userid,
                mission_instance::STATE_ENDED,
                $context
            );
            $hascompletedall = $nachievements == $ncompletedachievements;

            // If there are less than 3 achievements, we show all them all as instance. Else we only show ongoing ones.
            $filters = [mission_instance::STATE_STARTED, mission_instance::STATE_COMPLETED, mission_instance::STATE_ENDED];
            $orderby = ['mi.state ASC', 'mi.completionratio DESC', 'mi.timestarted DESC'];
            if (!$showinstancesonly) {
                $filters = [mission_instance::STATE_STARTED];
            }

            // No need to fetch the ongoing ones if we've completed everything.
            if ($showinstancesonly || !$hascompletedall) {
                $instances = $mr->get_achievement_instances_by_subject_id($userid,
                    $filters,
                    $context,
                    $orderby,
                    0,
                    $ntohighlight
                );
            }
        }

        // If there are mot than 3 achievements, and
        if (!$showinstancesonly && $ncompletedachievements > 0) {
            $recentlycompleted = $mr->get_achievement_instances_by_subject_id(
                $userid,
                [mission_instance::STATE_ENDED, mission_instance::STATE_COMPLETED],
                $context,
                ['mi.timecompleted DESC', 'mi.timestarted DESC'],
                0,
                5
            );
        }

        $moreurl = $urlresolver->reverse('my_achievements');
        $pageurl = $this->related['pageurl'] ?? null;
        if ($pageurl) {
            $moreurl->param('returnurl', $pageurl->out_as_local_url(false));
        }
        $hasmore = $nachievements > $ntohighlight;

        return [
            'context' => (new context_exporter($context))->export($output),
            'hasany' => $nachievements > 0,

            'instances' => array_map(function ($achievement) use ($ef, $output) {
                return $ef->get_mission_instance_exporter($achievement)->export($output);
            }, $instances),
            'hasinstances' => !empty($instances),

            'recentlycompleted' => array_map(function ($achievement) use ($ef, $output) {
                return $ef->get_mission_instance_exporter($achievement)->export($output);
            }, $recentlycompleted),
            'hasrecentlycompleted' => !empty($recentlycompleted),

            'hasmore' => $hasmore,
            'moreurl' => $moreurl->out(false),

            'total' => $nachievements,
            'totalcompleted' => $ncompletedachievements,
        ];
    }
}
