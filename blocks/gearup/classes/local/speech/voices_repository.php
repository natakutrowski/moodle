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

use block_gearup\local\http\api_client;
use cache;
use moodle_url;

/**
 * Repository.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class voices_repository {

    /** @var api_client */
    protected $apiclient;
    /** @var cache */
    protected $cache;
    /** @var voices[]|null */
    protected $voices;

    public function __construct(cache $cache, api_client $apiclient) {
        $this->cache = $cache;
        $this->apiclient = $apiclient;
    }

    /**
     * Get the voics.
     *
     * @return static_voice[]
     */
    public function get_voices(): array {
        $this->load();
        return array_values($this->voices);
    }

    /**
     * Load the voices.
     */
    protected function load() {
        if (!isset($this->voices)) {
            $this->voices = array_reduce($this->cache->get('voices'), function ($carry, $voice) {
                $gender = $voice->gender === 'female' ? voice::GENDER_FEMALE : voice::GENDER_MALE;
                $sampleurl = ($voice->sample_url ?? null) ? new moodle_url($voice->sample_url) : null;
                $carry[$voice->id] = new static_voice(
                    $voice->id,
                    $voice->name,
                    $gender,
                    $voice->language,
                    $sampleurl,
                );
                return $carry;
            }, []);
        }
    }

}
