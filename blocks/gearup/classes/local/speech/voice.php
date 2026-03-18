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

namespace block_gearup\local\speech;

/**
 * Voice.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface voice {

    /** Female voice. */
    const GENDER_FEMALE = 'f';
    /** Male voice. */
    const GENDER_MALE = 'm';

    /**
     * Get the ID of the voice.
     *
     * @return string
     */
    public function get_id(): string;

    /**
     * Get the name of the voice.
     *
     * @return string
     */
    public function get_name(): string;

    /**
     * Get the gender name of the voice.
     *
     * @return string As per GENDER_* constant.
     */
    public function get_gender(): string;

    /**
     * Get the language code.
     *
     * @return string Code "en-US", "fr-FR", etc.
     */
    public function get_language_code(): string;

    /**
     * Get the sample URL.
     *
     * @return \moodle_url|null
     */
    public function get_sample_url(): ?\moodle_url;

}
