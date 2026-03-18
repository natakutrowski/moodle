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
class static_voice implements voice {

    /** @var string */
    protected $id;
    /** @var string */
    protected $name;
    /** @var string */
    protected $gender;
    /** @var string */
    protected $languagecode;
    /** @var \moodle_url|null */
    protected $sampleurl;
    /**
     * Constructor.
     *
     * @param string $id The voice ID.
     * @param string $name The voice name.
     * @param string $languagecode The language code.
     * @param \moodle_url|null $sampleurl The sample URL.
     */
    public function __construct(string $id, string $name, string $gender, string $languagecode, ?\moodle_url $sampleurl = null) {
        $this->id = $id;
        $this->name = $name;
        $this->gender = $gender;
        $this->languagecode = $languagecode;
        $this->sampleurl = $sampleurl;

        if (!in_array($gender, [static::GENDER_FEMALE, static::GENDER_MALE])) {
            throw new \coding_exception('Invalid voice gender.');
        }
    }

    /**
     * Get the ID of the voice.
     *
     * @return string
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Get the name of the voice.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get the name of the voice.
     *
     * @return string
     */
    public function get_gender(): string {
        return $this->gender;
    }

    /**
     * Get the language code.
     *
     * @return string Code "en-US", "fr-FR", etc.
     */
    public function get_language_code(): string {
        return $this->languagecode;
    }

    /**
     * Get the sample URL.
     *
     * @return \moodle_url|null
     */
    public function get_sample_url(): ?\moodle_url {
        return $this->sampleurl;
    }

}
