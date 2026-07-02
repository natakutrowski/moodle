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


namespace local_xp\local\reason;

use block_xp\local\reason\reason;
use block_xp\local\reason\reason_rule_trait;
use block_xp\local\reason\reason_tracking_trait;
use block_xp\local\reason\reason_with_rule;
use block_xp\local\reason\reason_with_tracking;
use context;
use context_course;

/**
 * Section completion reason.
 *
 * @package    local_xp
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_completion_reason implements
    reason,
    reason_with_location,
    reason_with_rule,
    reason_with_short_description,
    reason_with_tracking {
    use reason_rule_trait;
    use reason_tracking_trait;

    /**
     * @var int The course ID.
     * @deprecated Since XP+ 20
     */
    protected $courseid = 0;
    /**
     * @var int The section num.
     * @deprecated Since XP+ 20
     */
    protected $sectionnum = 0;

    /**
     * Constructor.
     *
     * @param int $courseid The course ID. Deprecated.
     * @param int|null $sectionnum The section. Deprecated.
     */
    public function __construct($courseid = 0, $sectionnum = null) {
        if ($courseid) {
            $ctx = context_course::instance($courseid, IGNORE_MISSING);
            if ($ctx) {
                $this->set_env_id((int) $ctx->id);
            }
        }
        if ($sectionnum !== null) {
            $this->set_object_id((int) $sectionnum);
        }
    }

    /**
     * Get the context.
     *
     * @return context|null
     */
    protected function get_context() {
        return context::instance_by_id($this->get_env_id() ?? 0, IGNORE_MISSING) ?: null;
    }

    /**
     * Get the course ID.
     *
     * @return int
     */
    protected function get_course_id(): int {
        $ctx = $this->get_context();
        return $ctx ? (int) $ctx->instanceid : 0;
    }

    /**
     * Get modinfo.
     *
     * @return \course_modinfo|null Null if the course no longer exists.
     */
    protected function get_modinfo() {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        try {

            return get_fast_modinfo($this->get_course_id());
        } catch (\moodle_exception $e) {
            return null;
        }
    }

    /**
     * Get the location name.
     *
     * @return string
     */
    public function get_location_name() {
        $modinfo = $this->get_modinfo();
        $section = $modinfo ? $modinfo->get_section_info($this->get_section_num()) : null;
        $name = $section ? get_section_name($modinfo->courseid, $section) : '';
        return $name !== '' ? $name : get_string('unknownsectiona', 'local_xp', $this->get_section_num());
    }

    /**
     * Get the location URL.
     *
     * @return moodle_url|null
     */
    public function get_location_url() {
        $modinfo = $this->get_modinfo();
        return $modinfo ? course_get_url($modinfo->courseid, $this->get_section_num()) : null;
    }

    /**
     * Get the section number.
     *
     * @return int
     */
    protected function get_section_num(): int {
        return $this->get_object_id() ?? 0;
    }

    public function get_signature() {
        return $this->get_course_id() . ':' . $this->get_section_num();
    }

    public function get_short_description() {
        return get_string('sectioncompleted', 'local_xp');
    }

    /**
     * @deprecated Since XP+ 20
     */
    public static function get_type() {
        return __CLASS__;
    }

    /**
     * @deprecated Since XP+ 20
     */
    public static function from_signature($signature) {
        [$courseid, $sectionnum] = explode(':', $signature);
        return new static($courseid, $sectionnum);
    }

    public static function from_event(\local_xp\event\section_completed $e) {
        $context = context_course::instance($e->courseid, IGNORE_MISSING);
        $reason = new static();
        $reason->set_env_id($context ? (int) $context->id : 0);
        $reason->set_object_id((int) $e->other['sectionnum']);
        return $reason;
    }

}
