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

/**
 * Host only.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\http;

use core\files\curl_security_helper;

/**
 * Host only.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class host_only extends curl_security_helper {

    /** @var string */
    protected $host;
    /** @var int */
    protected $port = 443;

    public function __construct($host) {
        $port = null;
        if (strpos($host, ':') !== false) {
            [$host, $port] = explode(':', $host, 2);
        }
        $this->host = $host;
        $this->port = $port ?? $this->port;
    }

    public function is_enabled() {
        return true;
    }

    protected function host_is_blocked($host) {
        return $host !== $this->host;
    }

    protected function port_is_blocked($port) {
        return $port != $this->port;
    }

}
