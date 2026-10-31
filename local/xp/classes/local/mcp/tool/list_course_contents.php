<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\local\mcp\tool;

use block_xp\di;
use core\context;
use local_xp\local\mcp\invalid_params_exception;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;
use local_xp\local\utils\agent_utils;

/**
 * List course contents MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_course_contents extends world_base {
    /**
     * Get the course contents tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'list_course_contents',
            'List course contents',
            'Return an overview of the course contents for the purpose of using within the context of Level Up XP.',
            [CONTEXT_SYSTEM, CONTEXT_COURSE],
            tool_definition::RISK_READ,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the course contents input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        $schema = static::world_scoped_schema();

        if (static::is_sitewide()) {
            $schema->property('course_id', schema::integer([
                'The Moodle course ID, obtainable by calling resolve_course.',
            ])->minimum(1));
        }

        return $schema;
    }

    /**
     * Get the course contents output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return schema::object([
            'course_shortname' => schema::string('The course short name.'),
            'course_fullname' => schema::string('The course full name.'),
            'course_description' => schema::string('The course summary text.'),
            'sections' => schema::array_of(
                static::get_section_schema(),
                'The list of course sections and their contents.'
            ),
        ], 'Course contents overview.');
    }

    /**
     * Get the activity output schema.
     *
     * @return schema
     */
    protected static function get_activity_schema(): schema {
        global $CFG;
        $hascompletion = !empty($CFG->enablecompletion);
        $hastags = !empty($CFG->usetags);
        $hasavailability = !empty($CFG->enableavailability);

        $schema = schema::object([
            'activity_type' => schema::string('Human-readable module type with its frankenstyle component.'),
            'activity_name' => schema::string('The activity name, when supported.')->optional(),
            'activity_description' => schema::string('The activity summary/intro, or null.')->nullable(),
            'activity_content' => schema::string(
                'Content of the activity when it has readily available text content.'
            )->optional(),
            'completion_mode' => schema::string('When enabled, how completion is tracked for the activity.')
                ->enum(['manual_by_student', 'criteria_based'])
                ->optional(),
            'completion_criteria' => schema::array_of(schema::string())->optional(),
            'conditional_access_criteria' => schema::string('Plain-text tree of conditional access criteria.')->optional(),
            'tags' => schema::array_of(schema::string(), 'Tags attached to the activity, if any.')->optional(),
        ], 'An activity (course module) in the course.');

        if (!$hascompletion) {
            $schema->remove_property('completion_mode');
            $schema->remove_property('completion_criteria');
        }
        if (!$hasavailability) {
            $schema->remove_property('conditional_access_criteria');
        }
        if (!$hastags) {
            $schema->remove_property('tags');
        }

        return $schema;
    }

    /**
     * Get the section output schema.
     *
     * @param bool $issubsection Whether this is a subsection schema.
     * @return schema
     */
    protected static function get_section_schema(bool $issubsection = false): schema {
        global $CFG;
        $hasavailability = !empty($CFG->enableavailability);

        if ($issubsection) {
            $sectioncontents = schema::array_of(schema::object([
                'type' => schema::string('Discriminator for the entry.')->enum(['activity']),
                'activity' => static::get_activity_schema(),
            ]), 'Ordered list of activities within the subsection.');
        } else {
            $sectioncontents = schema::array_of(schema::object([
                'type' => schema::string('Discriminator for the entry: either an activity or a subsection.')
                    ->enum(['activity', 'subsection']),
                'activity' => static::get_activity_schema()->optional(),
                'section' => static::get_section_schema(true)->optional(),
            ]), 'Ordered list of activities and subsections within the section.');
        }

        $schema = schema::object([
            'section_name' => schema::string('The section name as displayed in the course.'),
            'section_description' => schema::string('The section summary, if any.')->nullable(),
            'conditional_access_criteria' => schema::string('Plain-text tree of conditional access criteria.')->optional(),
            'section_contents' => $sectioncontents,
        ], 'A course section.');

        if (!$hasavailability) {
            $schema->remove_property('conditional_access_criteria');
        }

        return $schema;
    }

    /**
     * Check access to the selected course.
     *
     * @return void
     */
    protected function permission_checks(): void {
        parent::permission_checks();

        if (static::is_sitewide()) {
            $modinfo = get_fast_modinfo($this->get_param('course_id'));
            if (!can_access_course($modinfo->get_course(), null, '', true)) {
                throw new invalid_params_exception('Invalid course_id');
            }
        }
    }

    /**
     * List the contents of the selected course.
     *
     * @return array
     */
    protected function execute() {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $world = $this->get_world();
        if (static::is_sitewide()) {
            $courseid = $this->get_param('course_id');
        } else {
            $coursecontext = $world->get_context()->get_course_context(false);
            if (!$coursecontext) {
                throw new invalid_params_exception('Course contents require a course context. Select a course context.');
            }
            $courseid = $coursecontext->instanceid;
        }

        $modinfo = get_fast_modinfo($courseid);
        $course = $modinfo->get_course();
        $context = \context_course::instance($courseid);
        $courseformat = course_get_format($course);

        $availabilityenabled = !empty($CFG->enableavailability);
        $tagsenabled = !empty($CFG->usetags);
        $completioninfo = new \completion_info($course);
        $completionenabled = $completioninfo->is_enabled() == COMPLETION_ENABLED;

        // Filter out delegated subsections.
        $sections = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            if (method_exists($section, 'is_delegated') && $section->is_delegated()) {
                continue;
            }
            $sections[] = $section;
        }

        $sectionsout = [];
        foreach ($sections as $section) {
            if (!$section->uservisible) {
                continue;
            }
            $sectionsout[] = self::course_contents_section_to_array(
                $section,
                $courseformat,
                $context,
                $availabilityenabled,
                $completionenabled,
                $tagsenabled,
                false,
            );
        }

        return [
            'course_shortname' => format_string($course->shortname, true, ['context' => $context]),
            'course_fullname' => format_string($course->fullname, true, ['context' => $context]),
            'course_description' => self::format_text_to_plaintext(
                (string) ($course->summary ?? ''),
                (int) ($course->summaryformat ?? FORMAT_HTML),
                $context
            ),
            'sections' => $sectionsout,
        ];
    }

    /**
     * Build the plain-text course-contents array for a single course section.
     *
     * When $issubsection is true, course modules that delegate further sections are still rendered
     * as activities to enforce the documented one-level nesting depth.
     *
     * @param \section_info $section Section to render.
     * @param \core_courseformat\base $courseformat Course format helper.
     * @param \context $context Course context.
     * @param bool $availabilityenabled Whether site availability is enabled.
     * @param bool $completionenabled Whether completion is enabled for the course.
     * @param bool $tagsenabled Whether site tags are enabled.
     * @param bool $issubsection Whether the section is a delegated subsection (no further nesting).
     * @return array
     */
    protected static function course_contents_section_to_array(
        \section_info $section,
        \core_courseformat\base $courseformat,
        context $context,
        bool $availabilityenabled,
        bool $completionenabled,
        bool $tagsenabled,
        bool $issubsection,
    ): array {
        $contents = [];
        foreach ($section->get_sequence_cm_infos() as $cm) {
            if ($cm->deletioninprogress) {
                continue;
            } else if (!$cm->uservisible) {
                continue;
            }
            $delegated = !$issubsection && method_exists($cm, 'get_delegated_section_info')
                ? $cm->get_delegated_section_info()
                : null;
            if ($delegated) {
                if (!$delegated->uservisible) {
                    continue;
                }
                $contents[] = [
                    'type' => 'subsection',
                    'section' => self::course_contents_section_to_array(
                        $delegated,
                        $courseformat,
                        $context,
                        $availabilityenabled,
                        $completionenabled,
                        $tagsenabled,
                        true,
                    ),
                ];
            } else {
                $contents[] = [
                    'type' => 'activity',
                    'activity' => self::course_contents_activity_to_array(
                        $cm,
                        $availabilityenabled,
                        $completionenabled,
                        $tagsenabled,
                    ),
                ];
            }
        }

        $description = self::format_text_to_plaintext(
            (string) ($section->summary ?? ''),
            (int) ($section->summaryformat ?? FORMAT_HTML),
            $context
        );

        $out = [
            'section_name' => (string) $courseformat->get_section_name($section->section),
            'section_description' => $description !== '' ? $description : null,
        ];

        if ($availabilityenabled && self::can_view_section_availability_criteria($context)) {
            $ci = new \core_availability\info_section($section);
            $criteria = self::availability_to_plaintext($ci->get_full_information(), $section->course);
            if (!empty($criteria)) {
                $out['conditional_access_criteria'] = "# Students requirements:\n\n" . $criteria;
            }
        }

        $out['section_contents'] = $contents;
        return $out;
    }

    /**
     * Format Moodle text as plain text.
     *
     * @param string|null $text Raw text content (any FORMAT_xx).
     * @param int $format The original FORMAT_xx of the content.
     * @param \context $context Context for formatting/filtering (also used as the pluginfile context).
     * @return string Plain text, possibly with a truncation marker appended.
     */
    protected static function format_text_to_plaintext(?string $text, int $format, context $context): string {
        if ($text === null || trim($text) === '') {
            return '';
        }
        $html = format_text($text, $format, [
            'context' => $context,
            'filter' => true,
            'para' => false,
        ]);
        return agent_utils::shorten_text(agent_utils::html_to_text($html));
    }

    /**
     * Render an availability info structure to plain text.
     *
     * @param string|\core_availability_multiple_messages|false|null $info Output of get_full_information().
     * @param int|\stdClass $courseorid Course or course id (used to resolve cm name tags).
     * @return string Plain-text rendering, or empty string when there are no restrictions.
     */
    protected static function availability_to_plaintext($info, $courseorid): string {
        if (!$info) {
            return '';
        }
        $html = \core_availability\info::format_info($info, $courseorid);
        return agent_utils::html_to_text((string) $html);
    }

    /**
     * Build the plain-text course-contents array for a single course module (activity) entry.
     *
     * @param \cm_info $cm Course module.
     * @param bool $availabilityenabled Whether site availability is enabled.
     * @param bool $completionenabled Whether completion is enabled for the course.
     * @param bool $tagsenabled Whether site tags are enabled.
     * @return array
     */
    protected static function course_contents_activity_to_array(
        \cm_info $cm,
        bool $availabilityenabled,
        bool $completionenabled,
        bool $tagsenabled,
    ): array {
        global $USER;

        $modname = $cm->modname;
        $cmcontext = $cm->context;

        $activity = [
            'activity_type' => get_string('modulename', 'mod_' . $modname) . ' (mod_' . $modname . ')',
        ];

        $cmname = trim((string) $cm->name);
        if ($cmname !== '') {
            $activity['activity_name'] = format_string($cmname, true, ['context' => $cmcontext]);
        }

        $instance = di::get('db')->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);
        $intro = (string) ($instance->intro ?? '');
        $introformat = (int) ($instance->introformat ?? FORMAT_HTML);
        $intromd = self::format_text_to_plaintext($intro, $introformat, $cmcontext);

        if ($modname === 'label') {
            // Labels have no separate description; their intro is the content.
            $activity['activity_description'] = null;
            if ($intromd !== '') {
                $activity['activity_content'] = $intromd;
            }
        } else if ($modname === 'page' && isset($instance->content)) {
            $activity['activity_description'] = $intromd !== '' ? $intromd : null;
            $contentmd = self::format_text_to_plaintext(
                (string) $instance->content,
                (int) ($instance->contentformat ?? FORMAT_HTML),
                $cmcontext
            );
            if ($contentmd !== '') {
                $activity['activity_content'] = $contentmd;
            }
        } else {
            $activity['activity_description'] = $intromd !== '' ? $intromd : null;
        }

        if ($completionenabled) {
            $activity['completion_mode'] = match ((int) $cm->completion) {
                COMPLETION_TRACKING_MANUAL => 'manual_by_student',
                COMPLETION_TRACKING_AUTOMATIC => 'criteria_based',
                default => 'disabled',
            };
            if ($activity['completion_mode'] === 'criteria_based' && self::can_view_activity_completion_criteria($cm)) {
                $criteria = self::completion_criteria_to_plaintext($cm, (int) $USER->id);
                if (!empty($criteria)) {
                    $activity['completion_criteria'] = $criteria;
                }
            }
            if ($activity['completion_mode'] === 'disabled') {
                unset($activity['completion_mode']);
            }
        }

        if ($availabilityenabled && self::can_view_activity_availability_criteria($cm)) {
            $ci = new \core_availability\info_module($cm);
            $criteria = self::availability_to_plaintext($ci->get_full_information(), $cm->course);
            if (!empty($criteria)) {
                $activity['conditional_access_criteria'] = "# Students requirements:\n\n" . $criteria;
            }
        }

        if ($tagsenabled) {
            $tags = \core_tag_tag::get_item_tags_array(
                'core',
                'course_modules',
                $cm->id,
                \core_tag_tag::BOTH_STANDARD_AND_NOT,
                0,
                false,
            );
            $activity['tags'] = array_values($tags);
            if (empty($activity['tags'])) {
                unset($activity['tags']);
            }
        }

        return $activity;
    }

    /**
     * Whether the current user can view full section availability criteria.
     *
     * @param \context $context Course context.
     * @return bool
     */
    protected static function can_view_section_availability_criteria(context $context): bool {
        return has_capability('moodle/course:viewhiddensections', $context);
    }

    /**
     * Whether the current user can view full activity availability criteria.
     *
     * @param \cm_info $cm Course module.
     * @return bool
     */
    protected static function can_view_activity_availability_criteria(\cm_info $cm): bool {
        return has_capability('moodle/course:viewhiddenactivities', $cm->context);
    }

    /**
     * Whether the current user can view activity completion criteria.
     *
     * @param \cm_info $cm Course module.
     * @return bool
     */
    protected static function can_view_activity_completion_criteria(\cm_info $cm): bool {
        return has_capability('moodle/course:manageactivities', $cm->context);
    }

    /**
     * Return activity completion criteria descriptions as plain text.
     *
     * @param \cm_info $cm Course module.
     * @param int $userid User ID.
     * @return array
     */
    protected static function completion_criteria_to_plaintext(\cm_info $cm, int $userid): array {
        $completiondetails = \core_completion\cm_completion_details::get_instance($cm, $userid, true);
        return array_values(array_filter(array_map(function ($detail): string {
            return trim((string) ($detail->description ?? ''));
        }, $completiondetails->get_details())));
    }
}
