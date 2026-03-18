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
class quests_tracker_exporter extends missions_tracker_exporter_base {

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
            'showongoing' => [
                'type' => PARAM_BOOL,
            ],
            'hasongoingquests' => [
                'type' => PARAM_BOOL,
            ],
            'ongoingquests' => [
                'type' => mission_instance_exporter::read_properties_definition(),
                'multiple' => true,
            ],
            'hasavailablequests' => [
                'type' => PARAM_BOOL,
            ],
            'availablequests' => [
                'type' => mission_instance_exporter::read_properties_definition(),
                'multiple' => true,
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

        $nquests = $mr->count_quest_instances_by_subject_id($userid, null, $context);
        $nstartedquests = 0;
        $startedquests = [];
        $navailablequests = 0;
        $availablequests = [];
        $nendedquests = 0;
        if ($nquests) {

            // Fetch some of the ongoing quests.
            $nstartedquests = $mr->count_quest_instances_by_subject_id($userid,
                [mission_instance::STATE_STARTED, mission_instance::STATE_COMPLETED],
                $context
            );
            $startedquests = $mr->get_quest_instances_by_subject_id($userid,
                [mission_instance::STATE_STARTED, mission_instance::STATE_COMPLETED],
                $context,
                ['mi.timecompleted DESC', 'mi.timestarted DESC'],
                0,
                3
            );

            // Fetch the available quests.
            $availablequests = $mr->get_available_quests($userid, $context);
            $navailablequests = count($availablequests);

            // Fetch the number of ended quests.
            $nendedquests = $mr->count_quest_instances_by_subject_id($userid, [mission_instance::STATE_ENDED], $context);
        }

        $hasmore = $nstartedquests > count($startedquests) || $nendedquests;
        $hasongoing = !empty($nstartedquests);
        $showongoing = $nendedquests > 0 || $nstartedquests > 0;

        $moreurl = $urlresolver->reverse('my_quests');
        $pageurl = $this->related['pageurl'] ?? null;
        if ($pageurl) {
            $moreurl->param('returnurl', $pageurl->out_as_local_url(false));
        }
        if (!$nstartedquests) {
            $moreurl->param('mode', 1);
        }

        return [
            'context' => (new context_exporter($context))->export($output),
            'hasany' => $nquests > 0,

            'showongoing' => $showongoing,

            'hasongoingquests' => $hasongoing,
            'ongoingquests' => array_map(function ($missioninst) use ($ef, $output) {
                return $ef->get_mission_instance_exporter($missioninst)->export($output);
            }, array_values($startedquests)),

            'hasavailablequests' => $navailablequests > 0,
            'availablequests' => array_map(function ($missioninst) use ($ef, $output) {
                return $ef->get_mission_instance_exporter($missioninst)->export($output);
            }, array_values($availablequests)),

            'hasmore' => $hasmore,
            'moreurl' => $moreurl->out(false),
        ];
    }
}
