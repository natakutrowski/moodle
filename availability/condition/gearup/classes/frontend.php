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

namespace availability_gearup;

use block_gearup\di;
use block_gearup\local\mission\achievement;
use block_gearup\local\mission\challenge;
use block_gearup\local\mission\quest;
use context_course;

/**
 * Frontend class.
 *
 * @package    availability_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {

    /**
     * Decides whether this plugin should be available in a given course.
     *
     * @param stdClass $course Course object.
     * @param \cm_info $cm Course-module currently being edited (null if none).
     * @param \section_info $section Section currently being edited (null if none).
     * @return bool False when adding is disabled.
     */
    protected function allow_add($course, ?\cm_info $cm = null, ?\section_info $section = null) {
        $context = context_course::instance($course->id);
        return di::get('repository')->count_missions($context) > 0;
    }

    /**
     * Gets a list of string identifiers.
     *
     * @return array Array of required string identifiers.
     */
    protected function get_javascript_strings() {
        return ['achievements', 'quests', 'challenges', 'isassigned', 'isstarted', 'iscompleted', 'isended',
            'isrecruit', 'hasstarted', 'hascompleted', 'mission'];
    }

    /**
     * Gets additional parameters for the plugin's initInner function.
     *
     * @param stdClass $course Course object.
     * @param \cm_info $cm Course-module currently being edited (null if none).
     * @param \section_info $section Section currently being edited (null if none).
     * @return array Array of parameters for the JavaScript function.
     */
    protected function get_javascript_init_params($course, ?\cm_info $cm = null, ?\section_info $section = null) {
        $context = context_course::instance($course->id);
        $missions = di::get('repository')->get_missions($context);
        $renderer = di::get('renderer');
        return [(object) [
            'achievements' => array_values(array_map(function ($m) {
                return ['id' => $m->get_id(), 'title' => $m->get_title()];
            }, array_filter($missions, function ($mission) {
                return $mission instanceof achievement;
            }))),
            'quests' => array_values(array_map(function ($m) {
                return ['id' => $m->get_id(), 'title' => $m->get_title()];
            }, array_filter($missions, function ($mission) {
                return $mission instanceof quest;
            }))),
            'challenges' => array_values(array_map(function ($m) {
                return ['id' => $m->get_id(), 'title' => $m->get_title()];
            }, array_filter($missions, function ($mission) {
                return $mission instanceof challenge;
            }))),
            'helphtml' => markdown_to_html(get_string('setuphelp', 'availability_gearup')),
            'helpiconhtml' => $renderer->pix_icon('help', get_string('help', 'core'), 'core'),
        ]];
    }

}
