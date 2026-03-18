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

use HTML_QuickForm_html;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/pear/HTML/QuickForm/html.php');

/**
 * Form field.
 *
 * Support lazily loading an arbitrary HTML value.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class html extends HTML_QuickForm_html {

    /** The field name. */
    const FIELD_NAME = 'block_gearup_html';

    /** @var object|string|callable The lazy string. */
    protected $content;

    /**
     * Constructor.
     *
     * @param string|null $elementname The name.
     * @param object|string|callable|null $content The lazy HTML.
     */
    public function __construct($elementname = null, $content = null) {
        $this->content = $content;
        parent::__construct('');
        if (!empty($elementname)) {
            $this->setName($elementname);
        }
    }

    public function toHtml() { // @codingStandardsIgnoreLine
        $content = $this->content;
        if (is_callable($content)) {
            $content = $content();
        }
        if (is_object($content)) {
            return (string) $content;
        }
        return (string) ($content ?? '');
    }

    /**
     * Undocumented function
     */
    public static function register() {
        // Auto-registered on loading, so nothing extra to do.
        return static::FIELD_NAME;
    }

}

// The class was namespaced and renamed, so we keep the old alias and registration here.
class_alias(html::class, 'block_gearup_form_html');
\MoodleQuickForm::registerElementType(html::FIELD_NAME, __FILE__, html::class);
