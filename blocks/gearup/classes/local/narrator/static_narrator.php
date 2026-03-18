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

namespace block_gearup\local\narrator;

use block_gearup\local\visual\static_visual;

/**
 * Narrator.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class static_narrator extends static_visual implements narrator_with_speaking_frames {

    /** @var moodle_url[] */
    protected $speakingframes = [];


    public function get_speaking_frames(): array {
        return $this->speakingframes ?? [];
    }

    public function has_speaking_frames(): bool {
        return !empty($this->speakingframes);
    }

    /**
     * Set the speaking frames.
     *
     * @param moodle_url[] $frames The speaking frames.
     */
    public function set_speaking_frames(array $frames): void {
        $this->speakingframes = array_values($frames);
    }

}
