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
 * Backup.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_gearup\local\backup\shortcode_questtracker_decode_rule;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/gearup/backup/moodle2/backup_gearup_stepslib.php');
require_once($CFG->dirroot . '/blocks/gearup/backup/moodle2/restore_gearup_shortcode_questdiscovery_decode_rule.php');

/**
 * Backup.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_gearup_block_task extends backup_block_task {

    /**
     * Define settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Define steps.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_gearup_block_structure_step('gearup', 'gearup.xml'));
    }

    /**
     * File areas.
     * @return array
     */
    public function get_fileareas() {
        return [];
    }

    /**
     * Config data encoded attributes.
     */
    public function get_configdata_encoded_attributes() {
    }

    /**
     * Encode content links.
     *
     * @param $content string The content.
     * @return string
     */
    public static function encode_content_links($content) {
        $content = restore_gearup_shortcode_questdiscovery_decode_rule::encode_content($content);
        $content = shortcode_questtracker_decode_rule::encode_content($content);
        return $content;
    }

}
