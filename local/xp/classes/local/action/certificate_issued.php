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

namespace local_xp\local\action;

use block_xp\local\action\static_action;

/**
 * Certificate issued action.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_issued extends static_action {

    /**
     * Constructor.
     *
     * @param \context $context The module context.
     * @param int $recipientid The user the certificate was issued to.
     * @param int $issueid The customcert issue row id.
     */
    public function __construct(\context $context, int $recipientid, int $issueid) {
        parent::__construct('certificate_issued', $context, $recipientid, $issueid);
    }

    /**
     * Get the issue ID..
     *
     * @return int|null
     */
    public function get_issue_id(): ?int {
        return $this->objectid;
    }

}
