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
 * User resolver.
 *
 * @package    local_xp
 * @copyright  2020 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\provider;

use context;
use core_user;
use moodle_database;
use block_xp\local\utils\user_utils;

/**
 * User resolver.
 *
 * Specific implementation allowing to resolve a user from a set of arbitrary
 * fields while ensuring that permissions would allow the user to be known.
 *
 * @package    local_xp
 * @copyright  2020 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_resolver {
    /** @var moodle_database $db The database. */
    protected $db;
    /** @var context|null The context. */
    protected $context;
    /** @var array The user fields to fetch. */
    protected $fields;

    /**
     * Constructor.
     *
     * @param moodle_database $db The database.
     * @param context|null $context The context.
     */
    public function __construct(moodle_database $db, ?context $context = null) {
        $this->db = $db;
        $this->context = $context;
        $this->fields = user_utils::picture_fields();
    }

    /**
     * Resolve a user.
     *
     * @param string|int $data Arbitrary data to resolve the user with.
     * @return object|null User object containing enough fields for display purposes.
     */
    public function resolve($data) {
        global $CFG;

        $data = (string) $data;
        $fields = $this->fields;
        $user = null;

        // Search by user ID.
        if (ctype_digit($data)) {
            $user = core_user::get_user((int) $data, $fields);
        }

        // Search by email.
        if (!$user && strpos($data, '@')) {
            $user = $this->db->get_record('user', ['email' => $data, 'mnethostid' => $CFG->mnet_localhost_id], $fields);
        }

        // Search by username.
        if (!$user) {
            $user = core_user::get_user_by_username($data, $fields);
        }

        if (!$user || ($this->context && !user_utils::is_valid_target($this->context, $user->id))) {
            return null;
        }

        return $user;
    }
}
