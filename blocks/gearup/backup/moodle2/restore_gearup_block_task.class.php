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
 * Restore.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_gearup\local\backup\shortcode_questtracker_decode_rule;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/gearup/backup/moodle2/restore_gearup_stepslib.php');
require_once($CFG->dirroot . '/blocks/gearup/backup/moodle2/restore_gearup_shortcode_questdiscovery_decode_rule.php');

/**
 * Restore.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_gearup_block_task extends restore_block_task {

    /**
     * Return the course context.
     *
     * @return context_course
     */
    public function get_course_context() {
        return context_course::instance($this->get_courseid());
    }

    /**
     * Return the course context ID.
     *
     * @return int
     */
    public function get_course_contextid() {
        return $this->get_course_context()->id;
    }

    /**
     * Return the old course context ID.
     *
     * @return int
     */
    public function get_old_course_contextid() {
        return $this->plan->get_info()->original_course_contextid;
    }

    /**
     * Define my settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Define my steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_gearup_block_structure_step('gearup', 'gearup.xml'));
    }

    /**
     * File areas.
     *
     * @return array
     */
    public function get_fileareas() {
        return [];
    }

    /**
     * Config data.
     */
    public function get_configdata_encoded_attributes() {
    }

    /**
     * Define decode contents.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('block_gearup_objective', ['supportingurl'], 'block_gearup_objective'),
        ];
    }

    /**
     * Define decode rules.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [
            new restore_gearup_shortcode_questdiscovery_decode_rule(),
            new shortcode_questtracker_decode_rule(),
        ];
    }

}
