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
use renderer_base;

/**
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class streaks_tracker_exporter extends missions_tracker_exporter_base {

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
            'streaks' => [
                'type' => mission_instance_exporter::read_properties_definition(),
                'multiple' => true,
            ],
            'hasany' => ['type' => PARAM_BOOL],
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
        $context = $this->related['context'];
        $missions = array_slice($mr->get_current_streaks($this->userid, $this->related['context']), 0, 1);
        return [
            'context' => (new context_exporter($context))->export($output),
            'streaks' => array_map(function ($missioninst) use ($ef, $output) {
                return $ef->get_mission_instance_exporter($missioninst, [
                    'url_exporter' => $this->related['url_resolver'] ?? null,
                ])->export($output);
            }, array_values($missions)),
            'hasany' => count($missions) > 0,
        ];
    }
}
