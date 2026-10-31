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

/**
 * State store points entry.
 *
 * @package    local_xp
 * @copyright  2020 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\provider;

use coding_exception;

/**
 * An entry containing a state_store_points.
 *
 * @package    local_xp
 * @copyright  2020 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_state_store_points_entry implements entry {
    /** @var string|number The reference. */
    protected $reference;
    /** @var state_store_points The object. */
    protected $object;
    /** @var array|null The errors. */
    protected $errors;

    /**
     * Constructor.
     */
    public function __construct($reference, ?user_state_store_points $object = null, ?array $errors = null) {
        $this->reference = $reference;
        $this->object = $object;
        $this->errors = $errors;
    }


    /**
     * Get errors.
     *
     * @return array[] Keys are error codes, and strings are human-readable errors.
     */
    public function get_errors() {
        return $this->errors ? $this->errors : [];
    }

    /**
     * Get the underlying object designed.
     *
     * @throws moodle_exception When not valid.
     * @return string|number
     */
    public function get_object() {
        if (!$this->is_valid()) {
            throw new coding_exception('The entry is invalid.');
        } else if (!$this->object) {
            throw new coding_exception('Expected an object but none were found.');
        }
        return $this->object;
    }

    /**
     * An arbitrary reference from the provider.
     *
     * @return string|number
     */
    public function get_reference() {
        return $this->reference;
    }

    /**
     * Whether the entry is valid.
     *
     * @return bool
     */
    public function is_valid() {
        return empty($this->errors);
    }
}
