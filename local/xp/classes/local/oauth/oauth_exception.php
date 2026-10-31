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

namespace local_xp\local\oauth;

/**
 * OAuth exception.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth_exception extends \moodle_exception {
    /** @var string The description. */
    protected $description;
    /** @var int HTTP code. */
    protected $httpcode;

    /**
     * Constructor.
     *
     * @param string $errorcode
     * @param int $httpcode
     * @param string $description
     */
    public function __construct(string $errorcode, int $httpcode, string $description = '') {
        $this->httpcode = $httpcode;
        $this->description = $description;
        parent::__construct($errorcode, 'local_xp', '', null, $description);
    }

    /**
     * HTTP code.
     *
     * @return int
     */
    public function get_http_code(): int {
        return $this->httpcode;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description;
    }
}
