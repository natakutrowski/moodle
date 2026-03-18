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

use block_gearup\di;
use block_gearup\local\visual\visual;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/form/group.php');

/**
 * Form field.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_gearup_form_imagegroup extends MoodleQuickForm_group {

    /** @var object[] Sections objects [label, options] with options [value, src]. */
    protected $options;
    /** @var string[] Of valid values. */
    protected $validvalues;

    /**
     * Constructor.
     *
     * @param string $elementname The name.
     * @param string $elementlabel The label.
     * @param visual[]|object[] $options Options containing [value, src], or array of objects [label, options].
     * @param array $attributes Attributes.
     */
    public function __construct($elementname = null, $elementlabel = null, $options = []) {

        // Normalise in sections.
        $firstoption = reset($options);
        $hassections = (!$firstoption instanceof visual && is_object($firstoption));
        if (!$hassections) {
            $options = [
                (object) [
                    'label' => null,
                    'options' => $options,
                ],
            ];
        }
        $this->options = $options;

        $alloptionvalues = [];
        foreach ($options as $option) {
            $alloptionvalues = array_merge($alloptionvalues, $option->options);
        }

        // TODO If availability_info then remove from valid options. Although this may cause
        // previous values that become unavailable from being included.
        $this->validvalues = array_map(function ($option) {
            return $option instanceof visual ? $option->get_id() : (string) $option['value'];
        }, $alloptionvalues);

        parent::__construct($elementname, $elementlabel, null, null, false);
    }

    /**
     * Override of standard quickforms method to create this element.
     *
     * @return void
     */
    public function _createElements() { // @codingStandardsIgnoreLine
        $element = $this->createFormElement('hidden', $this->getName());
        $element->setType(PARAM_RAW);
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
    public function export_for_template(renderer_base $output) {
        $context = parent::export_for_template($output);
        $renderer = di::get('renderer');
        $value = $this->getValue();
        $context['elements'] = [
            'separator' => '',
            'html' => $renderer->image_group($this->options, $this->getName(), $value ? reset($value) : null),
        ];
        return $context;
    }

}

MoodleQuickForm::registerElementType('block_gearup_imagegroup', __FILE__, 'block_gearup_form_imagegroup');
