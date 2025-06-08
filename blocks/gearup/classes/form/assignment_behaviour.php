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

namespace block_gearup\form;

use block_gearup\form\radiogroup;
use block_gearup\local\availability\plugin_required_info;
use block_gearup\local\mission\mission;

defined('MOODLE_INTERNAL') || die();

/**
 * Form field.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_behaviour extends radiogroup {

    const FIELD_NAME = 'block_gearup_assignment_behaviour';

    /**
     * Constructor.
     */
    public function __construct() {
        $options = [
            [
                'value' => 0,
                'label' => get_string('compulsoryquest', 'block_gearup'),
                'description' => get_string('compulsoryquestdesc', 'block_gearup'),
            ],
            [
                'value' => 1,
                'label' => get_string('optionalquest', 'block_gearup'),
                'description' => get_string('optionalquestdesc', 'block_gearup'),
            ],
            [
                'value' => 2,
                'label' => get_string('discoverablequest', 'block_gearup'),
                'description' => get_string('discoverablequestdesc', 'block_gearup'),
                'availability' => new plugin_required_info('filter_shortcodes', get_string('shortcodes', 'block_gearup')),
            ],
        ];
        parent::__construct('assignbehaviour', get_string('assignmentbehaviour', 'block_gearup'), $options);
    }

    /**
     * Export the value.
     *
     * @param array $submitValues The values.
     * @param bool $assoc Something...
     * @return array|null
     */
    public function exportValue(&$submitValues, $assoc = false) { // @codingStandardsIgnoreLine
        $value = parent::exportValue($submitValues, $assoc); // @codingStandardsIgnoreLine
        if ($value === null) {
            return $value;
        }

        $currentvalue = (int) $value[$this->getName()];

        $visibility = null;
        $startmode = null;
        if ($currentvalue === 0) {
            $visibility = mission::VISIBLE_ALWAYS;
            $startmode = mission::START_ALWAYS;
        } else if ($currentvalue === 1) {
            $visibility = mission::VISIBLE_ALWAYS;
            $startmode = mission::START_OPTIN;
        } else if ($currentvalue === 2) {
            $visibility = mission::VISIBLE_SECRET;
            $startmode = mission::START_OPTIN;
        }

        return [
            'visibility' => $visibility,
            'startmode' => $startmode,
        ];
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element.
     *
     * @param string $event Name of event
     * @param mixed $arg event arguments
     * @param object $caller calling object
     * @return bool
     */
    function onQuickFormEvent($event, $arg, &$caller) { // @codingStandardsIgnoreLine
        parent::onQuickFormEvent($event, $arg, $caller); // @codingStandardsIgnoreLine

        switch ($event) {
            case 'updateValue':

                $visibility = $caller->_constantValues['visibility']
                        ?? ($caller->isSubmitted() ? $caller->_submitValues['visibility'] ?? null : null)
                        ?? $caller->_defaultValues['visibility']
                        ?? null;
                $startmode = $caller->_constantValues['startmode']
                        ?? ($caller->isSubmitted() ? $caller->_submitValues['startmode'] ?? null : null)
                        ?? $caller->_defaultValues['startmode']
                        ?? null;

                $finalval = null;
                if ($visibility === mission::VISIBLE_ALWAYS && $startmode === mission::START_ALWAYS) {
                    $finalval = 0;
                } else if ($visibility === mission::VISIBLE_ALWAYS && $startmode === mission::START_OPTIN) {
                    $finalval = 1;
                } else if ($visibility === mission::VISIBLE_SECRET && $startmode === mission::START_OPTIN) {
                    $finalval = 2;
                }

                if ($finalval !== null) {
                    $this->setValue(['assignbehaviour' => $finalval]);
                }

                break;

            default:
                return parent::onQuickFormEvent($event, $arg, $caller);
        }
    }

    /**
     * Register the type.
     *
     * @return string
     */
    public static function register(): string {
        return static::FIELD_NAME;
    }

}

// The class was namespaced and renamed, so we keep the old alias name.
class_alias(assignment_behaviour::class, 'block_gearup_form_assignment_behaviour');
\MoodleQuickForm::registerElementType(assignment_behaviour::FIELD_NAME, __FILE__, assignment_behaviour::class);
