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
// See <https://levelup.plus>.

/**
 * Mission.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\mission;

use block_gearup\local\objective\objective;
use block_gearup\local\visual\visual;
use context;

/**
 * Mission interface.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface mission {
    // Const SECRECY_NONE = 0;
    // const SECRECY_SECRET = 1;.

    /** Repeat never. */
    const REPEAT_NEVER = 0;
    /** Repeat always. */
    const REPEAT_ALWAYS = -1;

    /** State wizard. */
    const STATE_WIZARD = 0;
    /** State active. */
    const STATE_ACTIVE = 1;
    /** State archived. */
    const STATE_ARCHIVED = 2;

    /** Start always. */
    const START_ALWAYS = 0;
    /** Start optin. */
    const START_OPTIN = 1;

    /** Visible always. */
    const VISIBLE_ALWAYS = 0;
    /** Visible secret. */
    const VISIBLE_SECRET = 1;
    /** Visible never. */
    const VISIBLE_NEVER = 2;

    /**
     * Get id.
     *
     * @return int
     */
    public function get_id(): int;
    /**
     * Get context.
     *
     * @return context
     */
    public function get_context(): context;

    /**
     * Get the state.
     *
     * At this point, this is used to store whether or not the wizard was finished.
     * But in the future we could add other states such as hidden, or inactive, etc.
     *
     * @return int Constant STATE_*.
     */
    public function get_state(): int;

    /**
     * Get title.
     *
     * @return string
     */
    public function get_title(): string;
    /**
     * Get description.
     *
     * @return string
     */
    public function get_description(): string;
    /**
     * Get visual.
     *
     * @return visual|null
     */
    public function get_visual(): ?visual;

    /**
     * Get the feedback.
     *
     * The feedback is the final story that is given to the
     * user after they completed all the objectives of a quest.
     *
     * @return string
     */
    public function get_feedback(): string;

    /**
     * Get the instructions.
     *
     * The are the piece of story when the objectives are ongoing.
     *
     * @return string
     */
    public function get_instructions(): string;

    /**
     * Get an objective.
     *
     * @return objective
     * @throws \moodle_exception When the objective is not found.
     */
    public function get_objective(int $id): objective;

    /**
     * Get objectives.
     *
     * @return array
     */
    public function get_objectives(): array;

    /**
     * Get the repeat setting.
     *
     * At the momoent, this is meant to be one of the self::REPEAT_ constants value.
     *
     * @return integer
     */
    public function get_repeat_count(): int;

    /**
     * Get the secret.
     *
     * @return string
     */
    public function get_secret(): string;

    /**
     * Get the start mode.
     *
     * The start mode defines whether a mission should start when is is assigned. This
     * method should return one of the self::START_ constants value.
     *
     * @return integer
     */
    public function get_start_mode(): int;

    /**
     * Get the time limit.
     *
     * @return integer
     */
    public function get_time_limit(): int;

    /**
     * Get the time modified.
     *
     * @return integer
     */
    public function get_time_modified(): int;

    /**
     * Get the visibility.
     *
     * The visibility defines when assigned users can see the mission, and how. This method
     * should return one of the self::VISIBLE_* constants value.
     *
     * @return integer
     */
    public function get_visibility(): int;

    /**
     * Get the voice ID.
     *
     * @return string|null
     */
    public function get_voice_id(): ?string;
}
