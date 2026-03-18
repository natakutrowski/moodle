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

namespace block_gearup\local\course;

use block_gearup\local\utils\course_utils;

/**
 * Section completion checker.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_completion_checker {

    /** @var int */
    protected $courseid;
    /** @var int */
    protected $userid;

    /** @var \completion_info */
    protected $completioninfo;
    /** @var \course_modinfo */
    protected $modinfo;

    /** @var bool Whether to ignore unavailable modules. */
    protected $ignoreunavailable = false;

    /**
     * Constructor.
     *
     * @param int $courseid The course ID.
     * @param int $userid The user ID.
     */
    public function __construct(int $courseid, int $userid) {
        $this->courseid = $courseid;
        $this->userid = $userid;
    }

    /**
     * Get the completion info.
     *
     * @return \completion_info|false
     */
    protected function get_completion_info() {
        if ($this->completioninfo === null) {
            $this->completioninfo = course_utils::get_completion_info($this->courseid) ?: false;
        }
        return $this->completioninfo;
    }

    /**
     * Get the modinfo.
     *
     * @return \course_modinfo|false
     */
    protected function get_modinfo() {
        if ($this->modinfo === null) {
            $this->modinfo = course_utils::get_modinfo($this->courseid, $this->userid) ?: false;
        }
        return $this->modinfo;
    }

    /**
     * Is the section completed?
     *
     * @param int $sectionnum
     * @return bool
     */
    public function is_completed(int $sectionnum) {
        global $CFG;

        $modinfo = $this->get_modinfo();
        $completioninfo = $this->get_completion_info();

        // Check that we have the modinfo and completion info.
        if (!$modinfo || !$completioninfo) {
            return false;
        }

        // Check that completion is enabled in site and course.
        if (!$completioninfo->is_enabled()) {
            return false;
        }

        // Check if the section exists.
        $sections = $modinfo->get_sections();
        if (empty($sections[$sectionnum])) {
            return false;
        }

        $loadwholecourse = true;
        $cmswithcompletioninsection = 0;
        $cmscompletedinsection = 0;
        $cmids = $sections[$sectionnum];
        foreach ($cmids as $cmid) {
            $cm = $modinfo->get_cm($cmid);

            // Always ignore activities that have been deleted.
            if ($cm->deletioninprogress ?? false) {
                continue;
            }

            // Check whether completion is enabled.
            $isenabled = $completioninfo->is_enabled($cm) != COMPLETION_TRACKING_NONE;
            if (!$isenabled) {
                continue;
            }

            // If the activity is not available to the user, we do not count it.
            if ($this->ignoreunavailable && !$cm->uservisible) {
                continue;
            }

            $cmswithcompletioninsection++;

            // Check whether activity is complete.
            $deprecatedfourtharg = $CFG->branch >= 400 ? null : $modinfo;
            $data = $completioninfo->get_data($cm, $loadwholecourse, $modinfo->get_user_id(), $deprecatedfourtharg);
            $loadwholecourse = false;
            $iscompleted = $data->completionstate != COMPLETION_INCOMPLETE;
            if (!$iscompleted) {
                continue;
            }
            $cmscompletedinsection++;
        }

        return $cmswithcompletioninsection > 0 && $cmswithcompletioninsection <= $cmscompletedinsection;
    }

    /**
     * Set the completion info.
     *
     * @param \completion_info $completioninfo
     */
    public function set_completion_info(\completion_info $completioninfo): void {
        if ($completioninfo->course_id != $this->courseid) {
            throw new \coding_exception('Course ID mismatch.');
        }
        $this->completioninfo = $completioninfo;
    }

    /**
     * Set the modinfo.
     *
     * @param \course_modinfo $modinfo
     */
    public function set_modinfo(\course_modinfo $modinfo): void {
        if ($modinfo->courseid != $this->courseid) {
            throw new \coding_exception('Course ID mismatch.');
        } else if ($modinfo->userid != $this->userid) {
            throw new \coding_exception('User ID mismatch.');
        }
        $this->modinfo = $modinfo;
    }

    /**
     * Set whether to ignore unavailable modules.
     *
     * When enabled, the modules that are not available to the user will not be expected to be completed.
     *
     * @param bool $ignoreunavailable
     */
    public function set_ignore_unavailable(bool $ignoreunavailable): void {
        $this->ignoreunavailable = $ignoreunavailable;
    }

}
