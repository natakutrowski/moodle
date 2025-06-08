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
 * Decode rule.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_gearup\local\model\mission;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/util/helper/restore_decode_rule.class.php');

/**
 * Decode rule.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_gearup_shortcode_questdiscovery_decode_rule extends restore_decode_rule {

    /**
     * Constructor.
     */
    public function __construct($placeholder = 'BLOCKGEARUPSHORTCODEQUESTDISCOVERY') {
        parent::__construct($placeholder, '', 'block_gearup_mission');
    }

    /**
     * Nasty override to get things done.
     *
     * @param string $content The content.
     * @return string
     */
    public function decode($content) {
        if (preg_match_all($this->cregexp, $content, $matches) === 0) {
            return $content;
        }

        foreach ($matches[0] as $key => $tosearch) {
            $mission = false;
            foreach ($this->mappings as $mappingkey => $mappingsource) {
                $oldid = $matches[$mappingkey][$key];
                $newid = $this->get_mapping('block_gearup_mission', $oldid);
                if ($newid) {
                    $mission = mission::get_record(['id' => $newid]);
                }
            }

            $shortcode = "[questdiscovery id=0 secret=unknown]";
            if ($mission) {
                $shortcode = "[questdiscovery id={$mission->get('id')} secret="
                    . substr($mission->get('secret') ?? 'invalid', 0, 7) . "]";
            }
            $content = str_replace($tosearch, $shortcode, $content);
        }

        return $content;
    }

    /**
     * Encodes the content.
     *
     * @param string $content The content.
     * @return string The content.
     */
    public static function encode_content($content) {
        global $CFG;

        if (!class_exists('filter_shortcodes\shortcodes')) {
            return $content;
        }

        require_once($CFG->dirroot . '/filter/shortcodes/lib/helpers.php');
        $content = filter_shortcodes_process_text($content, function($tag) {
            if ($tag !== 'questdiscovery') {
                return null;
            }
            return (object) ['hascontent' => false, 'contentprocessor' => function($args, $content) {
                return '$@BLOCKGEARUPSHORTCODEQUESTDISCOVERY*' . ($args['id'] ?? '0') . '@$';
            }];
        });

        return $content;
    }

    /**
     * Bypass the validation.
     *
     * @param string $linkname The link name.
     * @param string $urltemplate The URL template.
     * @param string $mappings The mapping.
     * @return array
     */
    protected function validate_params($linkname, $urltemplate, $mappings) {
        return ['1' => $mappings];
    }

}
