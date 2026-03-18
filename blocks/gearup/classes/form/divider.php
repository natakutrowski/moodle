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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/pear/HTML/QuickForm/html.php');

/**
 * Form field.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class divider extends \HTML_QuickForm_html {

    const FIELD_NAME = 'block_gearup_divider';

    /**
     * Constructor.
     *
     * @param string $elementname The name.
     * @param string $elementlabel The label.
     * @param visual[]|object[] $options Options containing [value, src], or array of objects [label, options].
     * @param array $attributes Attributes.
     */
    public function __construct($elementname = null) {
        $hr = \html_writer::empty_tag('hr', ['class' => 'gu-border-0 gu-my-4 gu-border-t gu-border-gray-200 gu-opacity-100']);
        parent::__construct($hr);
        $this->setName($elementname);
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
class_alias(divider::class, 'block_gearup_form_divider');
\MoodleQuickForm::registerElementType(divider::FIELD_NAME, __FILE__, divider::class);
