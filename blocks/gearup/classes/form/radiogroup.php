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

use block_gearup\di;
use block_gearup\local\availability\static_info;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/form/group.php');

/**
 * Form field.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class radiogroup extends \MoodleQuickForm_group {

    /** The field name. */
    const FIELD_NAME = 'block_gearup_radiogroup';

    /** @var array Of options. */
    protected $options;
    /** @var string[] Of valid values. */
    protected $validvalues;

    /**
     * Constructor.
     *
     * @param string $elementname The name.
     * @param string $elementlabel The label.
     * @param array $options Options containing [value, label, description].
     * @param array $attributes Attributes.
     */
    public function __construct($elementname = null, $elementlabel = null, $options = []) {
        $this->options = array_values(array_map(function($option) {
            if (!isset($option['availability'])) {
                $option['availability'] = new static_info(true);
            }
            return $option;
        }, $options));
        $this->validvalues = array_map(function($option) {
            return (string) $option['value'];
        }, array_filter($this->options, function($option) {
            return $option['availability']->is_available();
        }));
        parent::__construct($elementname, $elementlabel, null, null, false);
    }

    /**
     * Override of standard quickforms method to create this element.
     *
     * @return void
     */
    public function _createElements() { // @codingStandardsIgnoreLine
        $element = $this->createFormElement('hidden', $this->getName());
        $element->setType(PARAM_INT);
        $this->_elements = [$element];
    }

    /**
     * Export the value.
     *
     * @param array $submitValues The values.
     * @param bool $assoc Something...
     * @return array|null
     */
    public function exportValue(&$submitValues, $assoc = false) { // @codingStandardsIgnoreLine
        $value = parent::exportValue($submitValues, false); // @codingStandardsIgnoreLine
        if ($value === null) {
            return $value;
        }
        $currentvalue = (string) $value[$this->getName()];
        if (!in_array($currentvalue, $this->validvalues)) {
            return null;
        }
        return $value;
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output The renderer.
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $context = parent::export_for_template($output);
        $renderer = di::get('renderer');
        $value = $this->getValue();
        $selected = is_array($value) ? reset($value) : $value;
        $context['elements'] = [
            'separator' => '',
            'html' => $renderer->radio_group($this->options, $this->getName(), $selected),
        ];
        return $context;
    }

    /**
     * Register the field.
     *
     * @return string Element type name.
     */
    public static function register() {
        return static::FIELD_NAME;
    }

}

// The class was namespaced and renamed, so we keep the old alias name.
class_alias(radiogroup::class, 'block_gearup_form_radiogroup');
\MoodleQuickForm::registerElementType(radiogroup::FIELD_NAME, __FILE__, radiogroup::class);
