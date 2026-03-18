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

namespace block_gearup\local\visual;

use moodle_url;

/**
 * Visual.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class static_visual implements visual {

    /** @var string */
    protected $alt;
    /** @var string|\lang_string */
    protected $id;
    /** @var moodle_url */
    protected $url;

    /**
     * Constructor.
     *
     * @param string $id The ID.
     * @param moodle_url|string $url The URL.
     * @param string|\lang_string $alt The alt.
     */
    public function __construct(string $id, $url, $alt = '') {
        $this->alt = $alt;
        $this->id = $id;
        $this->url = !is_object($url) ? new moodle_url($url) : $url;
    }

    /**
     * Get an alternative text for the visual.
     *
     * @return string
     */
    public function get_alt(): string {
        return (string) $this->alt;
    }

    /**
     * Get an unique identifier for the visual.
     *
     * @return string
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Get the URL.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        return $this->url;
    }

}
