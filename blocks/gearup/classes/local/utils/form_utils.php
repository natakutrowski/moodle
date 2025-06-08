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
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\utils;

use block_gearup\form\html;
use block_gearup\local\visual\repository;
use block_gearup\local\visual\repository_with_context;
use context;

/**
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form_utils {

    /**
     * Add an image group from a visual repository.
     *
     * @param object $mform The form.
     * @param string $name The name of the field.
     * @param string $label The label.
     * @param repository $repo The visual repository.
     * @param context $context The context.
     * @return object The element.
     */
    public static function add_image_group_from_repository($mform, $name, $label, repository $repo, ?context $context = null) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/gearup/classes/form/imagegroup.php');

        $visuals = $repo->get_visuals();
        $ctxvisuals = [];
        if ($context && $repo instanceof repository_with_context) {
            $ctxvisuals = $repo->get_visuals_in_context($context);
        }

        $options = [];
        if (!empty($ctxvisuals)) {
            $options[] = (object) [
                'label' => get_string('fromlibrary', 'block_gearup'),
                'options' => $ctxvisuals,
            ];
        }

        $options[] = (object) [
            'label' => get_string('frombuiltin', 'block_gearup'),
            'options' => $visuals,
        ];

        return $mform->addElement('block_gearup_imagegroup', $name, $label, $options);
    }

    /**
     * Add a JS AMD call in forms.
     *
     * Directly doing it in the form definition does not work with dynamic forms, so we are using
     * a custom element that registers the AMD call when the field is being rendered instead.
     *
     * @param object $mform The form.
     * @param string $module The AMD module.
     * @param string $function The AMD function name.
     * @param array $args The AMD function arguments.
     * @param string|null $fieldname The name of the field.
     * @return object The element.
     */
    public static function add_js_amd_call($mform, $module, $function, $args, $fieldname = null) {
        return $mform->addElement(html::register(), $fieldname, function() use ($module, $function, $args) {
            global $PAGE;
            $PAGE->requires->js_call_amd($module, $function, $args);
        });
    }
}
