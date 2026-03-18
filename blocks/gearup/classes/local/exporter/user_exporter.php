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
use block_gearup\local\mission\mission;
use block_gearup\local\outcome\type\user_facing_type;
use block_gearup\local\utils\user_utils;
use renderer_base;

/**
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_exporter extends \core\external\exporter {

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
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'id' => [
                'type' => PARAM_INT,
            ],
            'name' => [
                'type' => PARAM_RAW,
            ],
            'picurl' => [
                'type' => PARAM_URL,
            ],
            'profileurl' => [
                'type' => PARAM_URL,
            ],
            'identityfields' => [
                'type' => [
                    'name' => [ // The name of the identity field.
                        'type' => PARAM_ALPHANUMEXT,
                    ],
                    'label' => [ // The label for the identity field.
                        'type' => PARAM_RAW,
                    ],
                    'value' => [ // The value of the identity field.
                        'type' => PARAM_RAW,
                    ],
                ],
                'multiple' => true,
            ],
            'hasidentityfields' => [
                'type' => PARAM_BOOL,
            ],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param \block_gearup\output\renderer $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $user = $this->data;
        $context = $this->related['context'];

        $profileurl = new \moodle_url('/user/profile.php', ['id' => $user->id]);
        if ($coursecontext = $context->get_course_context(false)) {
            $profileurl = new \moodle_url('/user/view.php', [
                'id' => $user->id,
                'course' => $coursecontext->instanceid]);
        }
        $identityfields = [];
        foreach (user_utils::get_visible_identity_fields($context) as $field => $label) {
            if (empty($user->{$field})) {
                continue;
            }
            $identityfields[] = ['name' => $field, 'label' => $label, 'value' => $user->{$field}];
        }

        return [
            'name' => fullname($user),
            'hasidentityfields' => !empty($identityfields),
            'identityfields' => $identityfields,
            'picurl' => $output->get_user_picture($user)->out(false),
            'profileurl' => $profileurl ? $profileurl->out(false) : null,
        ];
    }

}
