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
 * Drop collected reason.
 *
 * @package    local_xp
 * @copyright  2022 Branch Up Pty Ltd
 * @author     Peter Dias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class drop_collected_reason implements reason, reason_with_location, reason_with_short_description, reason_with_tracking {
    use reason_tracking_trait;

    /** @var \stdClass|null */
    protected $drop = null;

    /**
     * @var int The drop ID.
     * @deprecated Since XP+ 20
     */
    protected $dropid;

    /**
     * Constructor.
     *
     * @param int $dropid The id of the drop. Deprecated.
     */
    public function __construct($dropid = 0) {
        if ($dropid) {
            $this->set_object_id($dropid);
        }
    }

    /**
     * Get drop.
     *
     * @return string|null
     */
    protected function get_drop(): ?\stdClass {
        if ($this->drop === null) {
            $db = \block_xp\di::get('db');
            $this->drop = $db->get_record('local_xp_drops', ['id' => (int) $this->get_object_id()], '*', IGNORE_MISSING);
        }
        return $this->drop ?: null;
    }

    public function get_location_name() {
        $drop = $this->get_drop();
        return $drop->name ?? null;
    }

    public function get_location_url() {
        $drop = $this->get_drop();
        if (!$drop) {
            return null;
        }
        $url = \block_xp\di::get('url_resolver')->reverse('drops', ['courseid' => $drop->courseid ?? 0]);
        $url->param('dropid', $drop->id ?? 0);
        return $url;
    }

    public function get_short_description() {
        return get_string('dropcollected', 'local_xp');
    }

    /**
     * @deprecated Since XP+ 20
     */
    public function get_signature() {
        return $this->get_object_id() ?? 0;
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

}
