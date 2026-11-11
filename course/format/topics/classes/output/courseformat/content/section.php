<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contains the default section controls output class.
 *
 * @package   format_topics
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_topics\output\courseformat\content;

use core_courseformat\base as course_format;
use core_courseformat\output\local\content\section as section_base;
use stdClass;

/**
 * Base class to render a course section.
 *
 * @package   format_topics
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {

    /** @var course_format the course format */
    protected $format;


    // TOPICS_EDITOR_OVERVIEW_V6_BEGIN
    /** Keep a multipage course overview lightweight, including when editing. */
    protected function add_cm_data(\stdClass &$data, \renderer_base $output): bool {
        global $PAGE;
        $compact = $this->format->show_editor() &&
            $this->format->get_course_display() == COURSE_DISPLAY_MULTIPAGE &&
            empty($this->format->get_sectionid()) &&
            $PAGE->pagetype === 'course-view-topics' &&
            $PAGE->url->get_path() === '/course/view.php' &&
            !optional_param('editor_full', 0, PARAM_BOOL) &&
            !optional_param('campusfr_full', 0, PARAM_BOOL);
        if (!$compact || (int)$this->section->section === 0 ||
                $this->section->is_delegated() || $this->isstealth) {
            return parent::add_cm_data($data, $output);
        }
        // Reuse the native summary; do not build an editing card for every activity.
        $cmsummary = new $this->cmsummaryclass($this->format, $this->section);
        $data->cmsummary = $cmsummary->export_for_template($output);
        $data->onlysummary = true;
        return true;
    }
    // TOPICS_EDITOR_OVERVIEW_V6_END

    public function export_for_template(\renderer_base $output): stdClass {
        $format = $this->format;

        $data = parent::export_for_template($output);

        if (!$this->format->get_sectionnum() && !$this->section->get_component_instance()) {
            $addsectionclass = $format->get_output_classname('content\\addsection');
            $addsection = new $addsectionclass($format);
            $data->numsections = $addsection->export_for_template($output);
            $data->insertafter = true;
        }

        return $data;
    }
}
