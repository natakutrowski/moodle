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

use block_xp\local\reason\reason;
use block_xp\local\reason\reason_tracking_trait;
use block_xp\local\reason\reason_with_tracking;

/**
 * Manual reason.
 *
 * @package    local_xp
 * @copyright  2020 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manual_reason implements reason, reason_with_short_description, reason_with_tracking {
    use reason_tracking_trait;

    /**
     * @var int The author of the manual reward.
     * @deprecated Since XP+ 20
     */
    protected $userid;

    /**
     * Constructor.
     *
     * @param int $userid The user id offering the reward. Deprecated.
     */
    public function __construct($userid = 0) {
        if ($userid) {
            $this->set_object_id((int) $userid);
        }
    }

    /**
     * @deprecated Since XP+ 20
     */
    public function get_signature() {
        return $this->get_object_id() ?? 0;
    }

    public function get_short_description() {
        return get_string('manuallyawarded', 'local_xp');
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
        return new static((int) $signature);
    }

    /**
     * From user.
     *
     * @param object $user A user.
     * @return static
     */
    public static function from_user($user) {
        $reason = new static();
        $reason->set_object_id((int) $user->id);
        return $reason;
    }

}
