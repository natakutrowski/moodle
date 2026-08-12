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
 * Contains the default section availability output class.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_courseformat\output\local\content\section;

use context_course;
use core_availability_multiple_messages;
use core\output\named_templatable;
use core_availability\info;
use core_availability\info_section;
use core_courseformat\base as course_format;
use core_courseformat\output\local\courseformat_named_templatable;
use renderable;
use section_info;
use stdClass;

/**
 * Base class to render section availability.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability implements named_templatable, renderable {

    use courseformat_named_templatable;

    /** @var course_format the course format class */
    protected $format;

    /** @var section_info the section object */
    protected $section;

    /** @var string the has availability attribute name */
    protected $hasavailabilityname;

    /** @var stdClass|null the instance export data */
    protected $data = null;

    /** @var int Availability excerpt text max size treshold */
    protected const AVAILABILITY_EXCERPT_MAXSIZE = 100;

    /**
     * Constructor.
     *
     * @param course_format $format the course format
     * @param section_info $section the section info
     */
    public function __construct(course_format $format, section_info $section) {
        $this->format = $format;
        $this->section = $section;
        $this->hasavailabilityname = 'hasavailability';
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return stdClass|null data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): ?stdClass {
        $this->build_export_data($output);
        return $this->data;
    }

    /**
     * Returns if the output has availability info to display.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return bool if the element has availability data to display
     */
    public function has_availability(\renderer_base $output): bool {
        $this->build_export_data($output);
        $attributename = $this->hasavailabilityname;
        return $this->data->$attributename;
    }

    /**
     * Protected method to build the export data.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     */
    protected function build_export_data(\renderer_base $output) {
        if (!empty($this->data)) {
            return;
        }

        $data = (object) $this->get_info($output);

        $this->add_campus_section_restriction_context($data);
        $this->add_campus_unlock_context($data);

        $attributename = $this->hasavailabilityname;
        $data->$attributename = !empty($data->info);

        $this->data = $data;

    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * If section is not visible, display the message about that ('Not available
     * until...', that sort of thing). Otherwise, returns blank.
     *
     * For users with the ability to view hidden sections, it shows the
     * information even though you can view the section and also may include
     * slightly fuller information (so that teachers can tell when sections
     * are going to be unavailable etc). This logic is the same as for
     * activities.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return stdclass data context for a mustache template
     */
    protected function get_info(\renderer_base $output): array {
        global $CFG, $USER;

        $section = $this->section;
        $context = context_course::instance($section->course);

        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context, $USER);

        $editurl = new \moodle_url(
            '/course/editsection.php',
            ['id' => $this->section->id, 'showonly' => 'availabilityconditions']
        );
        $info = ['editurl' => $editurl->out(false)];

        if ($section->is_orphan()) {
            $info['editing'] = false;
        }

        if (!$section->visible) {
            return [];
        } else if (!$section->uservisible) {
            if ($section->availableinfo) {
                // Note: We only get to this function if availableinfo is non-empty,
                // so there is definitely something to print.
                $info['info'] = $this->get_availability_data($output, $section->availableinfo, 'isrestricted');
            }
        } else if ($canviewhidden && !empty($CFG->enableavailability)) {
            // Check if there is an availability restriction.
            $ci = new info_section($section);
            $fullinfo = $ci->get_full_information();
            if ($fullinfo) {
                $info['info'] = $this->get_availability_data($output, $fullinfo, 'isrestricted isfullinfo');
            }
        }

        return $info;
    }

    /**
     * Get the basic availability information data.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @param string|core_availability_multiple_messages $availabilityinfo the avalability info
     * @param string $additionalclasses additional css classes
     * @return stdClass the availability information data
     */
    protected function get_availability_data($output, $availabilityinfo, $additionalclasses = ''): stdClass {
        // At this point, availabilityinfo is either a string or a renderable. We need to handle both cases in a different way.
        if (is_string($availabilityinfo)) {
            $data = $this->availability_info_from_string($output, $availabilityinfo);
        } else {
            $data = $this->availability_info_from_output($output, $availabilityinfo);
        }

        $data->classes = $additionalclasses;

        $additionalclasses = array_filter(explode(' ', $additionalclasses));
        if (in_array('ishidden', $additionalclasses)) {
            $data->ishidden = 1;
        } else if (in_array('isstealth', $additionalclasses)) {
            $data->isstealth = 1;
        } else if (in_array('isrestricted', $additionalclasses)) {
            $data->isrestricted = 1;
            if (in_array('isfullinfo', $additionalclasses)) {
                $data->isfullinfo = 1;
            }
        }

        return $data;
    }

    /**
     * Generate the basic availability information data from a string.
     * Just shorten availability text to generate the excerpt text.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @param string $availabilityinfo the avalability info
     * @return stdClass the availability information data
     */
    protected function availability_info_from_string(\renderer_base $output, string $availabilityinfo): stdClass {
        $course = $this->format->get_course();

        $text = info::format_info($availabilityinfo, $course);
        $data = ['text' => $text];

        if (strlen(html_to_text($text, 0, false)) > self::AVAILABILITY_EXCERPT_MAXSIZE) {
            $data['excerpt'] = shorten_text($text, self::AVAILABILITY_EXCERPT_MAXSIZE);
        }

        return (object) $data;
    }

    /**
     * Generate the basic availability information data from a renderable.
     * Use the header and the first item to generate the excerpt text.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @param core_availability_multiple_messages $availabilityinfo the avalability info
     * @return stdClass the availability information data
     */
    protected function availability_info_from_output(
        \renderer_base $output,
        core_availability_multiple_messages $availabilityinfo
    ): stdClass {
        $course = $this->format->get_course();

        $renderable = new \core_availability\output\availability_info($availabilityinfo);
        // We need to export_for_template() instead of directly render, to reuse the info for both 'text' and 'excerpt'.
        $info = $renderable->export_for_template($output);

        $text = $output->render_from_template('core_availability/availability_info', $info);
        $data = ['text' => info::format_info($text, $course)];

        if (!empty($info->items)) {
            $excerpttext = $info->header . ' ' . $info->items[0]->header;
            $data['excerpt'] = info::format_info($excerpttext, $course);
        }

        return (object) $data;
    }


    /**
     * Add CampusFR pedagogical section restriction context.
     *
     * Completion-only restrictions get a concise previous-step message.
     * Date-only restrictions get a generic "available soon" message only
     * when a future start-date condition is actually blocking the section.
     * Mixed restriction trees keep Moodle's native availability explanation.
     *
     * @param stdClass $data
     * @return void
     */
    private function add_campus_section_restriction_context(stdClass $data): void {
        if (
            empty($data->info)
            || $this->section->uservisible
            || empty($this->section->availability)
        ) {
            return;
        }

        $availability = json_decode($this->section->availability);
        if (!$availability) {
            return;
        }

        $conditiontypes = $this->extract_condition_types($availability);
        $payload = null;

        if ($conditiontypes === ['completion']) {
            $sectionnumber = (int)$this->section->section;
            $previousstep = max(0, $sectionnumber - 1);

            $payload = [
                'hascampussectionrestriction' => true,
                'campussectionrestrictiontype' => 'completion',
                'campussectionrestrictiontext' => get_string(
                    'coursesection_complete_previous',
                    'theme_edly',
                    $previousstep
                ),
            ];
        } else if (
            $conditiontypes === ['date']
            && $this->has_future_start_date_condition($availability)
        ) {
            $payload = [
                'hascampussectionrestriction' => true,
                'campussectionrestrictiontype' => 'date',
                'campussectionrestrictiontext' => get_string(
                    'coursesection_available_soon',
                    'theme_edly'
                ),
            ];
        }

        if ($payload === null) {
            return;
        }

        // A restricted section does not render its activities for the current
        // user, so the usual promoted Text & Media cover disappears with them.
        // Resolve the first Text & Media image directly from section data and
        // expose it to the availability template instead.
        $coverurl = $this->get_first_text_media_image_url();
        if ($coverurl !== null) {
            $payload['campussectioncoverurl'] = $coverurl;
            $payload['hascampussectioncover'] = true;
        }

        if (is_array($data->info)) {
            foreach ($data->info as &$item) {
                if (is_object($item)) {
                    foreach ($payload as $key => $value) {
                        $item->$key = $value;
                    }
                }
            }
            unset($item);
        } else if (is_object($data->info)) {
            foreach ($payload as $key => $value) {
                $data->info->$key = $value;
            }
        }
    }

    /**
     * Return the first image used by a Text & Media block in this section.
     *
     * Restricted sections do not expose their activities to the normal course
     * renderer, therefore the CampusFR promoted cover has to be resolved from
     * the module data itself. We deliberately inspect only mod_label (Moodle's
     * Text & Media activity) and only return the first image found.
     *
     * @return string|null
     */
    private function get_first_text_media_image_url(): ?string {
        global $DB;

        $modinfo = get_fast_modinfo((int)$this->section->course);
        $sectionnumber = (int)$this->section->section;
        $cmids = $modinfo->sections[$sectionnumber] ?? [];

        foreach ($cmids as $cmid) {
            if (empty($modinfo->cms[$cmid])) {
                continue;
            }

            $cm = $modinfo->cms[$cmid];
            if ($cm->modname !== 'label') {
                continue;
            }

            $intro = $DB->get_field('label', 'intro', ['id' => $cm->instance], IGNORE_MISSING);
            if (!$intro) {
                continue;
            }

            // Do not emit the normal mod_label pluginfile URL here. When the
            // parent section is unavailable, Moodle can reject that file via
            // the course-module access check together with the hidden label.
            // The dedicated theme endpoint re-validates the course + section
            // and serves only this promoted first image.
            if (preg_match('/<img\b[^>]*\bsrc=["\']([^"\']+)["\']/i', $intro)) {
                return (new \moodle_url('/theme/edly/section_cover.php', [
                    'sectionid' => (int)$this->section->id,
                ]))->out(false);
            }
        }

        return null;
    }

    /**
     * Return whether a restriction tree contains a future start-date condition.
     *
     * Moodle date availability conditions use "d" for the comparison direction
     * and "t" for the Unix timestamp. Only >= / > conditions can represent a
     * future opening date. End-date restrictions intentionally keep Moodle's
     * native explanation.
     *
     * @param mixed $node
     * @return bool
     */
    private function has_future_start_date_condition($node): bool {
        if (is_object($node)) {
            if (($node->type ?? '') === 'date') {
                $direction = (string)($node->d ?? $node->direction ?? '');
                $timestamp = (int)($node->t ?? $node->time ?? 0);

                if (
                    in_array($direction, ['>=', '>'], true)
                    && $timestamp > time()
                ) {
                    return true;
                }
            }

            foreach (get_object_vars($node) as $value) {
                if ($this->has_future_start_date_condition($value)) {
                    return true;
                }
            }
        } else if (is_array($node)) {
            foreach ($node as $value) {
                if ($this->has_future_start_date_condition($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add CampusFR custom unlock context based on role restrictions.
     *
     * @param stdClass $data
     * @return void
     */
    private function add_campus_unlock_context(stdClass $data): void {
        if (empty($data->info) || empty($this->section->availability)) {
            return;
        }

        $availability = json_decode($this->section->availability);
        if (!$availability) {
            return;
        }

        // CampusFR commerce messaging is only valid for a restriction tree
        // made exclusively of role conditions. Completion, date, grade, group
        // and mixed trees must keep Moodle's native availability explanation.
        $conditiontypes = $this->extract_condition_types($availability);
        if ($conditiontypes !== ['role']) {
            return;
        }

        $rules = $this->extract_role_rules_from_availability($availability);
        $allowed = $rules['allowed'] ?? [];
        $forbidden = $rules['forbidden'] ?? [];

        if ($allowed === [] && $forbidden === []) {
            return;
        }

        // Restriction semantics are independent from the number of products.
        // A generic Trial exclusion must never be presented as an upgrade.
        if (in_array('grammarstudent', $allowed, true)) {
            $restrictiontype = 'grammar';
        } else if (in_array('student', $allowed, true)) {
            $restrictiontype = 'full';
        } else if (
            in_array('trialstudent', $forbidden, true)
            || (
                in_array('guest', $forbidden, true)
                && in_array('trialstudent', $forbidden, true)
            )
        ) {
            $restrictiontype = 'subscriber';
        } else if (in_array('grammarstudent', $forbidden, true)) {
            $restrictiontype = 'full';
        } else {
            return;
        }

        $resolverclass =
            '\local_subscriptions\commerce\course\storefront\\'
            . 'CommerceCourseStorefrontTargetResolver';
        $resolver = class_exists($resolverclass)
            ? $resolverclass::create()
            : null;
        $courseids = $this->related_course_ids();

        $grammarcount = $resolver === null
            ? 0
            : $resolver->count_offers($courseids, 'grammar');
        $fullcount = $resolver === null
            ? 0
            : $resolver->count_offers($courseids, 'full');
        $allcount = $resolver === null
            ? 0
            : $resolver->count_offers($courseids, 'subscriber');

        if ($restrictiontype === 'grammar') {
            $titlekey = 'unlock_grammar_title';
            $textkey = 'unlock_grammar_text';
            $buttonkey = 'unlock_grammar_button';
            $urllevel = 'grammar';
        } else if ($restrictiontype === 'full') {
            $titlekey = 'unlock_full_title';
            $textkey = 'unlock_full_text';
            $buttonkey = 'unlock_full_button';
            $urllevel = 'full';
        } else if ($allcount === 1) {
            // A1: one purchasable course, no customer-facing "formula" concept.
            $titlekey = 'unlock_course_title';
            $textkey = 'unlock_course_text';
            $buttonkey = 'unlock_course_button';
            $urllevel = $grammarcount === 1 && $fullcount === 0
                ? 'grammar'
                : 'full';
        } else {
            // A2: Grammar + Full are two genuine customer choices.
            $titlekey = 'unlock_subscriber_title';
            $textkey = 'unlock_subscriber_text';
            $buttonkey = 'unlock_subscriber_button';
            $urllevel = 'subscriber';
        }

        $payload = [
            'hascampusunlock' => true,
            'campusunlocktype' => $restrictiontype,
            'campusunlocktitle' => get_string($titlekey, 'local_subscriptions'),
            'campusunlocktext' => get_string($textkey, 'local_subscriptions'),
            'campusunlockbutton' => get_string($buttonkey, 'local_subscriptions'),
            'campusunlockurl' => $resolver === null
                ? (new \moodle_url('/boutique'))->out(false)
                : $resolver->url(
                    $courseids,
                    $urllevel,
                    $this->has_active_trial_conversion()
                )->out(false),
        ];

        if ($this->restriction_debug_enabled()) {
            $payload['campusunlockdebug'] = json_encode([
                'sectionid' => (int)$this->section->id,
                'courseid' => (int)$this->section->course,
                'availability' => $availability,
                'conditiontypes' => $conditiontypes,
                'allowed' => $allowed,
                'forbidden' => $forbidden,
                'restrictiontype' => $restrictiontype,
                'courseids' => $courseids,
                'grammarcount' => $grammarcount,
                'fullcount' => $fullcount,
                'allcount' => $allcount,
                'titlekey' => $titlekey,
                'textkey' => $textkey,
                'buttonkey' => $buttonkey,
                'urllevel' => $urllevel,
                'diagnostic' => $resolver === null
                    ? null
                    : $resolver->diagnose($courseids, $urllevel),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        if (is_array($data->info)) {
            foreach ($data->info as &$item) {
                if (is_object($item)) {
                    foreach ($payload as $key => $value) {
                        $item->$key = $value;
                    }
                }
            }
            unset($item);
        } else if (is_object($data->info)) {
            foreach ($payload as $key => $value) {
                $data->info->$key = $value;
            }
        }
    }

    private function has_active_trial_conversion(): bool {
        global $USER;

        return isloggedin()
            && !isguestuser()
            && class_exists('\local_subscriptions\trial_manager')
            && \local_subscriptions\trial_manager::user_has_active_trial(
                (int)$USER->id
            ) !== null
            && \local_subscriptions\trial_manager::is_discount_window_open(
                (int)$USER->id
            );
    }

    private function restriction_debug_enabled(): bool {
        return optional_param('campusrestrictionsdebug', 0, PARAM_BOOL)
            && has_capability(
                'moodle/site:config',
                \context_system::instance()
            );
    }

    /**
     * Extract the availability condition types used by a restriction tree.
     *
     * @param mixed $node
     * @return string[]
     */
    private function extract_condition_types($node): array {
        $types = [];

        if (is_object($node)) {
            if (!empty($node->type) && is_string($node->type)) {
                $types[] = strtolower($node->type);
            }
            foreach (get_object_vars($node) as $value) {
                $types = array_merge($types, $this->extract_condition_types($value));
            }
        } else if (is_array($node)) {
            foreach ($node as $value) {
                $types = array_merge($types, $this->extract_condition_types($value));
            }
        }

        $types = array_values(array_unique(array_filter($types)));
        sort($types);
        return $types;
    }

    /**
     * Extract Moodle role shortnames from section availability JSON.
     *
     * @param mixed $node
     * @return array
     */
    private function extract_role_rules_from_availability($node, bool $parentnegation = false): array {
        global $DB;

        $rules = [
            'allowed' => [],
            'forbidden' => [],
        ];

        $currentnegation = $parentnegation;

        if (is_object($node) && !empty($node->op) && is_string($node->op)) {
            if (str_starts_with($node->op, '!')) {
                $currentnegation = !$currentnegation;
            }
        }

        if (is_object($node)) {
            if (($node->type ?? '') === 'role') {
                $roleid = 0;

                if (!empty($node->id)) {
                    $roleid = (int)$node->id;
                } else if (!empty($node->roleid)) {
                    $roleid = (int)$node->roleid;
                }

                if ($roleid > 0) {
                    $shortname = $DB->get_field('role', 'shortname', ['id' => $roleid], IGNORE_MISSING);

                    if ($shortname) {
                        if (!empty($node->n) || $currentnegation) {
                            $rules['forbidden'][] = $shortname;
                        } else {
                            $rules['allowed'][] = $shortname;
                        }
                    }
                }
            }

            foreach (get_object_vars($node) as $value) {
                $child = $this->extract_role_rules_from_availability($value, $currentnegation);
                $rules['allowed'] = array_merge($rules['allowed'], $child['allowed']);
                $rules['forbidden'] = array_merge($rules['forbidden'], $child['forbidden']);
            }

        } else if (is_array($node)) {
            foreach ($node as $value) {
                $child = $this->extract_role_rules_from_availability($value, $currentnegation);
                $rules['allowed'] = array_merge($rules['allowed'], $child['allowed']);
                $rules['forbidden'] = array_merge($rules['forbidden'], $child['forbidden']);
            }
        }

        $rules['allowed'] = array_values(array_unique($rules['allowed']));
        $rules['forbidden'] = array_values(array_unique($rules['forbidden']));

        return $rules;
    }

    /** @return int[] */
    private function related_course_ids(): array {
        $courseids = [(int)$this->section->course];

        foreach (['realcourseid', 'trialcourseid'] as $fieldshortname) {
            $mappedid = $this->get_course_customfield_int(
                (int)$this->section->course,
                $fieldshortname
            );
            if ($mappedid > 0) {
                $courseids[] = $mappedid;
            }
        }

        return array_values(array_unique(array_filter($courseids)));
    }

    private function get_course_customfield_int(int $courseid, string $shortname): int {
        global $DB;

        $value = $DB->get_field_sql("
            SELECT d.value
            FROM {customfield_data} d
            JOIN {customfield_field} f ON f.id = d.fieldid
            JOIN {customfield_category} c ON c.id = f.categoryid
            WHERE d.instanceid = :courseid
            AND f.shortname = :shortname
            AND c.component = 'core_course'
            AND c.area = 'course'
            LIMIT 1
        ", [
            'courseid' => $courseid,
            'shortname' => $shortname,
        ]);

        return $value ? (int)$value : 0;
    }

    /**
     * @deprecated since Moodle 4.3 MDL-78204. Please use {@see self::get_availability_data} instead.
     */
    #[\core\attribute\deprecated('get_availability_data()', since: '4.3', mdl: 'MDL-78489', final: true)]
    protected function availability_info() {
        \core\deprecation::emit_deprecation([self::class, __FUNCTION__]);
    }
}
