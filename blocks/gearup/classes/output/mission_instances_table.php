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
 * Mission instances table.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\output;

use block_gearup\local\mission\mission;
use block_gearup\local\routing\url_resolver;
use renderer_base;

/**
 * Mission instances table.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated Since Quest 1.4, use \block_gearup\table\mission_instances instead.
 */
class mission_instances_table extends \block_gearup\table\mission_instances {

    /**
     * Constructor.
     *
     * @param mission $mission The mission.
     * @param object $repository The resolver.
     * @param renderer_base $renderer The renderer.
     * @param url_resolver $urlresolver The URL resolver.
     * @param object $missionhelper The mission helper.
     * @param int|null $subjectid The subjectid ID.
     */
    public function __construct(
        mission $mission,
        $repository,
        renderer_base $renderer,
        url_resolver $urlresolver,
        $missionhelper,
        ?int $subjectid = null
    ) {

        debugging('The class \block_gearup\output\mission_instances_table is deprecated, '
            . 'use \block_gearup\table\mission_instances instead.', DEBUG_DEVELOPER);

        parent::__construct('block_gearup_missions');
        $this->define_mission($mission);
        $this->define_repository($repository);
        $this->define_renderer($renderer);
        $this->define_url_resolver($urlresolver);
        $this->define_mission_helper($missionhelper);
        if ($subjectid) {
            $this->define_subject_id($subjectid);
        }
        $this->define_query(new \block_gearup\local\repository\mission_instance_query($mission->get_context()));
        $this->init();
    }

}
