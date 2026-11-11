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
 * Topics course format. Display the whole course as "topics" made of modules.
 *
 * @package format_topics
 * @copyright 2006 The Open University
 * @author N.D.Freear@open.ac.uk, and others.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

// Horrible backwards compatible parameter aliasing.
if ($topic = optional_param('topic', 0, PARAM_INT)) {
    $url = $PAGE->url;
    $url->param('section', $topic);
    debugging('Outdated topic param passed to course/view.php', DEBUG_DEVELOPER);
    redirect($url);
}
// End backwards-compatible aliasing.

// Retrieve course format option fields and add them to the $course object.
$format = course_get_format($course);
$course = $format->get_course();
$context = context_course::instance($course->id);

if (($marker >= 0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    course_set_marker($course->id, $marker);
}

// Make sure section 0 is created.
course_create_sections_if_missing($course, 0);

$renderer = $PAGE->get_renderer('format_topics');

if (!is_null($displaysection)) {
    $format->set_sectionnum($displaysection);
}
$outputclass = $format->get_output_classname('content');
$widget = new $outputclass($format);

// TOPICS_EDITOR_OVERVIEW_V6_BEGIN
// Offer the native full editing view without changing the student experience.
if ($format->show_editor() &&
        $format->get_course_display() == COURSE_DISPLAY_MULTIPAGE &&
        empty($format->get_sectionid()) &&
        $PAGE->pagetype === 'course-view-topics' &&
        $PAGE->url->get_path() === '/course/view.php') {
    $editorfull = optional_param('editor_full', 0, PARAM_BOOL) ||
        optional_param('campusfr_full', 0, PARAM_BOOL);
    $editorurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
    if (!$editorfull) {
        $editorurl->param('editor_full', 1);
    }
    $editorlabel = $editorfull ? 'Retour au sommaire' : 'Vue complète';
    echo \html_writer::div(
        \html_writer::link($editorurl, $editorlabel, ['class' => 'btn btn-outline-secondary btn-sm']),
        'text-end mb-3',
        ['data-editor-overview' => $editorfull ? 'full' : 'compact']
    );
}
// TOPICS_EDITOR_OVERVIEW_V6_END

echo $renderer->render($widget);
