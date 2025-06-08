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
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\exporter;

use block_gearup\di;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\has_availability_info_for_context;
use block_gearup\local\availability\has_availability_info_for_user;
use renderer_base;

/**
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objective_type_exporter extends \core\external\exporter {

    /** @var type The type. */
    private $type;

    /**
     * Constructor.
     *
     * @param type $type The type.
     * @param array $related The related objects.
     */
    public function __construct($type, $related = []) {
        $this->type = $type;
        parent::__construct([], $related);
    }


    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'name' => [
                'type' => PARAM_RAW,
            ],
            'label' => [
                'type' => PARAM_TEXT,
            ],
            'description' => [
                'type' => PARAM_RAW, // May contain some HTML.
            ],
            'isavailable' => [
                'type' => PARAM_BOOL,
            ],
            'unavailablereasons' => [
                'type' => PARAM_TEXT,
                'multiple' => true,
            ],
        ];
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'context' => 'context',
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        global $USER;

        // TODO Should we pass the user as related instead?
        $type = $this->type;
        $context = $this->related['context'];

        // TODO Get this from related instead?
        $otr = di::get('objective_type_resolver');

        $desc = strip_tags(markdown_to_html($type->get_short_description()), '<a><em>');

        $isavailable = true;
        $unavailablereasons = [];
        if (is_subclass_of($type, has_availability_info::class)) {
            $info = $type->get_availability_info();
            if (!$info->is_available()) {
                $isavailable = false;
                $unavailablereasons = array_merge($unavailablereasons, $info->get_reasons());
            }
        }
        if ($isavailable) {
            if (is_subclass_of($type, has_availability_info_for_context::class)) {
                $info = $type->get_availability_info_for_context($context);
                if (!$info->is_available()) {
                    $isavailable = false;
                    $unavailablereasons = array_merge($unavailablereasons, $info->get_reasons());
                }
            }
        }
        if ($isavailable) {
            if (is_subclass_of($type, has_availability_info_for_user::class)) {
                $info = $type->get_availability_info_for_user($USER->id, $context);
                if (!$info->is_available()) {
                    $isavailable = false;
                    $unavailablereasons = array_merge($unavailablereasons, $info->get_reasons());
                }
            }
        }

        return [
            'name' => $otr->get_type_name($type),
            'label' => (string) $type->get_display_name(),
            'description' => (string) $desc,
            'isavailable' => $isavailable,
            'unavailablereasons' => array_map(function($r) {
                return (string) $r;
            }, $unavailablereasons),
        ];
    }

}
