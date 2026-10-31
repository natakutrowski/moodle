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
 * Basic request.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\routing;

use block_xp\local\routing\request;
use block_xp\local\routing\url;
use moodle_url;

/**
 * Basic request.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class basic_request implements request {
    /** @var string The HTTP method. */
    protected $method;

    /** @var url The URL. */
    protected $url;

    /**
     * Constructor.
     *
     * @param moodle_url $url The URL.
     */
    public function __construct(moodle_url $url) {
        $this->method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        $this->url = new url($url);
    }

    /**
     * Get the HTTP method.
     *
     * @return string
     */
    public function get_method() {
        return $this->method;
    }

    /**
     * Get the URL.
     *
     * @return url
     */
    public function get_url() {
        return $this->url;
    }
}
