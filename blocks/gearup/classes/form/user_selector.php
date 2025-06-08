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
use block_gearup\external\search_users;
use core_user\fields;

/**
 * Form field.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/form/autocomplete.php');

/**
 * Form field.
 *
 * This is heavily inspired by the JS core_user/form_user_selector.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_selector extends \MoodleQuickForm_autocomplete {

    /** The field name. */
    const FIELD_NAME = 'block_gearup_user_selector';

    /**
     * Constructor.
     */
    public function __construct($name = null, $label = null, $attributes = []) {

        // The constructor is called twice, ignore when params are empty.
        if (!empty($name)) {
            if (!isset($attributes['context'])) {
                throw new \coding_exception('Context mission from attributes');
            }

            $output = di::get('renderer');
            $context = $attributes['context'];
            unset($attributes['context']);

            $attributes = array_merge([
                'multiple' => false,
                'ajax' => 'block_gearup/form_user_selector',
                'valuehtmlcallback' => function($userid) use ($context, $output) {
                    $fields = fields::for_identity($context, false)->with_name();
                    $record = \core_user::get_user($userid, $fields, MUST_EXIST);
                    $user = search_users::prepare_user_from_record($record, fields::get_identity_fields($context, false));
                    return $output->render_from_template('block_gearup/form_user_selector_item', $user);
                },
            ], $attributes, [
                'data-contextid' => $context->id,
            ]);
        }

        parent::__construct($name, $label, [], $attributes);
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
class_alias(user_selector::class, 'block_gearup_form_user_selector');
\MoodleQuickForm::registerElementType(user_selector::FIELD_NAME, __FILE__, user_selector::class);
