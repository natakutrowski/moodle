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
 * Mission persistent.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\model;

use block_gearup\local\mission\achievement;
use block_gearup\local\mission\challenge;
use block_gearup\local\mission\mission as mission_interface;
use block_gearup\local\mission\quest;
use block_gearup\local\mission\streak;
use core\persistent;

/**
 * Mission persistent.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission extends persistent {

    const TABLE = 'block_gearup_mission';

    const TYPE_ACHIEVEMENT = 0;
    const TYPE_QUEST = 1;
    const TYPE_CHALLENGE = 2;
    const TYPE_STREAK = 4;

    /**
     * Properties.
     *
     * @return array
     */
    public static function define_properties() {
        return [
            'contextid' => [
                'type' => PARAM_INT,
            ],
            'state' => [
                'type' => PARAM_INT,
                'choices' => [
                    mission_interface::STATE_WIZARD,
                    mission_interface::STATE_ACTIVE,
                    mission_interface::STATE_ARCHIVED,
                ],
                'default' => mission_interface::STATE_WIZARD,
            ],
            'type' => [
                'type' => PARAM_INT,
                'choices' => [
                    self::TYPE_ACHIEVEMENT,
                    self::TYPE_QUEST,
                    self::TYPE_CHALLENGE,
                    self::TYPE_STREAK,
                ],
            ],
            'title' => [
                'type' => PARAM_TEXT,
            ],
            'description' => [
                'type' => PARAM_RAW,
                'default' => '',
            ],
            'instructions' => [
                'type' => PARAM_RAW,
                'default' => '',
            ],
            'feedback' => [
                'type' => PARAM_RAW,
                'default' => '',
            ],
            'repeatcount' => [
                'type' => PARAM_INT,
                'choices' => [
                    mission_interface::REPEAT_NEVER,
                    mission_interface::REPEAT_ALWAYS,
                ],
                'default' => mission_interface::REPEAT_NEVER,
            ],
            'secret' => [
                'type' => PARAM_ALPHANUM,
                'default' => function () {
                    return substr(bin2hex(random_bytes(5)), 0, 10);
                },
            ],
            // TODO We could use secrecy as a bitwise operator to set whether name, outcomes, objectives are shown.
            // 'secrecy' => [
            // 'type' => PARAM_INT,
            // 'choices' => [
            // 0,  // None, this is visible.
            // 1,  // Secret. For achievements, this means that it is visible but details unknown.
            // ]
            // ],
            'startmode' => [
                'type' => PARAM_INT,
                'choices' => [
                    mission_interface::START_ALWAYS, // Automatically started when assigned.
                    mission_interface::START_OPTIN, // Manually, use opt-in to accept.
                ],
                'default' => mission_interface::START_ALWAYS,
            ],
            'timelimit' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'visibility' => [
                'type' => PARAM_INT,
                'choices' => [
                    mission_interface::VISIBLE_ALWAYS, // Always visible once assigned.
                    mission_interface::VISIBLE_SECRET, // Secret, visible through shortcode or direct link.
                ],
                'default' => mission_interface::VISIBLE_ALWAYS,
            ],
            'visual' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'voiceid' => [
                'type' => PARAM_ALPHANUMEXT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }

    /**
     * Is it an achievement?
     *
     * @return boolean
     */
    public function is_achievement() {
        return $this->get('type') == self::TYPE_ACHIEVEMENT;
    }

    /**
     * Is it a challenge?
     *
     * @return boolean
     */
    public function is_challenge() {
        return $this->get('type') == self::TYPE_CHALLENGE;
    }

    /**
     * Is it a quest?
     *
     * @return boolean
     */
    public function is_quest() {
        return $this->get('type') == self::TYPE_QUEST;
    }

    /**
     * Is it a streak?
     *
     * @return boolean
     */
    public function is_streak() {
        return $this->get('type') == self::TYPE_STREAK;
    }

    /**
     * Validation.
     *
     * @param mixed $value The value.
     * @return bool|\lang_string
     */
    protected function validate_repeatcount($value) {
        if ($this->is_streak()
                && $this->get('state') == mission_interface::STATE_ACTIVE
                && $value != mission_interface::REPEAT_ALWAYS
        ) {
            return new \lang_string('invaliddata', 'core_error');
        }
        return true;
    }

    /**
     * Validation.
     *
     * @param mixed $value The value.
     * @return bool|\lang_string
     */
    protected function validate_startmode($value) {
        if ($this->is_achievement() && $value != mission_interface::START_ALWAYS) {
            return new \lang_string('invaliddata', 'core_error');
        }
        return true;
    }

    /**
     * Validation.
     *
     * @param mixed $value The value.
     * @return bool|\lang_string
     */
    protected function validate_timelimit($value) {
        if ($this->is_streak()
                && $this->get('state') == mission_interface::STATE_ACTIVE
                && !$value
        ) {
            return new \lang_string('invaliddata', 'core_error');
        }
        return true;
    }

    /**
     * Validation.
     *
     * @param mixed $value The value.
     * @return bool|\lang_string
     */
    protected function validate_visibility($value) {
        if ($this->is_achievement() && $value != mission_interface::VISIBLE_ALWAYS) {
            return new \lang_string('invaliddata', 'core_error');
        }
        return true;
    }

    /**
     * Convert internal type.
     *
     * Convert the internal type to the class name.
     *
     * @param int $type The internal type.
     * @return string The type as its interface.
     */
    public static function convert_internal_type(int $type) {
        switch ($type) {
            case self::TYPE_ACHIEVEMENT:
                return achievement::class;
            case self::TYPE_QUEST:
                return quest::class;
            case self::TYPE_CHALLENGE:
                return challenge::class;
            case self::TYPE_STREAK:
                return streak::class;
            default:
                throw new \coding_exception('Unknown type');
        }
    }

    /**
     * Get the internal type.
     *
     * The internal type is how we locally save the type of a mission, as a number, see TYPE_* constants.
     *
     * @param string $class
     */
    public static function get_internal_type(string $class) {
        switch ($class) {
            case achievement::class:
                return self::TYPE_ACHIEVEMENT;
            case quest::class:
                return self::TYPE_QUEST;
            case challenge::class:
                return self::TYPE_CHALLENGE;
            case streak::class:
                return self::TYPE_STREAK;
            default:
                throw new \coding_exception('Unknown type class');
        }
    }

}
