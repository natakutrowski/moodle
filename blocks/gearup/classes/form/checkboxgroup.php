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
class checkboxgroup extends \MoodleQuickForm_group {

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
        parent::__construct($elementname, $elementlabel, null, null, true);
    }

    /**
     * Override of standard quickforms method to create this element.
     *
     * @return void
     */
    public function _createElements() { // @codingStandardsIgnoreLine
        $elements = [];
        foreach ($this->options as $option) {
            $element = $this->createFormElement('hidden', $option['value']);
            $element->setType(PARAM_INT);
            $elements[] = $element;
        }
        $this->_elements = $elements;
    }

    /**
     * Export the value.
     *
     * @param array $submitValues The values.
     * @param bool $assoc Something...
     * @return array|null
     */
    public function exportValue(&$submitValues, $assoc = false) { // @codingStandardsIgnoreLine
        $values = parent::exportValue($submitValues, false); // @codingStandardsIgnoreLine
        if ($values === null) {
            return $values;
        }

        return [$this->getName() => array_keys(array_filter($values))];
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
        $selected = $value ? array_keys(array_filter($value)) : [];
        $context['elements'] = [
            'separator' => '',
            'html' => $renderer->checkbox_group($this->options, $this->getName(), $selected),
        ];
        return $context;
    }

    /**
     * Register the field.
     *
     * @return string Element type name.
     */
    public static function register() {
        $name = 'block_gearup_checkboxgroup';
        \MoodleQuickForm::registerElementType($name, __FILE__, static::class);
        return $name;
    }

}
