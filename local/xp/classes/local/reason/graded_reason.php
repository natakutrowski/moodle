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
// https://levelup.plus

namespace local_xp\local\reason;

use grade_item;
use moodle_url;
use block_xp\local\reason\reason;
use block_xp\local\reason\reason_tracking_trait;
use block_xp\local\reason\reason_with_tracking;

/**
 * Graded reason.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class graded_reason implements reason, reason_with_location, reason_with_short_description, reason_with_tracking {
    use reason_tracking_trait;

    /** @var grade_item The grade item. */
    protected $gradeitem;

    /**
     * @var int The grade item ID.
     * @deprecated Since XP+ 20
     */
    protected $itemid = 0;
    /**
     * @var int The related user ID.
     * @deprecated Since XP+ 20
     */
    protected $relateduserid = 0;

    /**
     * Constructor.
     *
     * @param int $itemid The item ID. Deprecated.
     * @param int $relateduserid The related user ID. Deprecated.
     */
    public function __construct($itemid = 0, $relateduserid = 0) {
        if ($itemid) {
            $this->set_object_id((int) $itemid);
        }
        if ($relateduserid) {
            $this->set_parent_id($relateduserid); // To maintain compatibility with older signatures.
        }
    }

    protected function get_grade_item() {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        if ($this->gradeitem === null) {
            $this->gradeitem = grade_item::fetch(['id' => $this->get_object_id()]);
        }
        return $this->gradeitem;
    }

    /**
     * Get the location name.
     *
     * @return string|null
     */
    public function get_location_name() {
        $gradeitem = $this->get_grade_item();
        if (!$gradeitem) {
            return '';
        }
        return $gradeitem->get_name(true);
    }

    /**
     * Get the location URL.
     *
     * @return moodle_url|null
     */
    public function get_location_url() {
        $gradeitem = $this->get_grade_item();
        if (!$gradeitem) {
            return null;
        }
        return new moodle_url('/grade/report/singleview/index.php', [
            'id' => $gradeitem->courseid,
            'itemid' => $gradeitem->id,
            'item' => 'grade',
        ]);
    }

    public function get_short_description() {
        return get_string('gradereceived', 'local_xp');
    }

    /**
     * @deprecated Since XP+ 20
     */
    public function get_signature() {
        return $this->get_object_id() . ':' . $this->get_parent_id();
    }

    /**
     * @deprecated Since XP+ 20
     */
    public static function get_type() {
        return __CLASS__;
    }

    /**
     * From signature.
     *
     * @param string $signature.
     * @return static
     * @deprecated Since XP+ 20
     */
    public static function from_signature($signature) {
        [$itemid, $relateduserid] = explode(':', $signature);
        return new static((int) $itemid, (int) $relateduserid);
    }

    /**
     * From event.
     *
     * @param \core\event\user_graded $e
     * @return static
     */
    public static function from_event(\core\event\user_graded $e) {
        $reason = new static();
        $reason->set_env_id((int) $e->contextid);
        $reason->set_object_id((int) $e->other['itemid']);
        $reason->set_parent_id($e->relateduserid);
        return $reason;
    }

}
