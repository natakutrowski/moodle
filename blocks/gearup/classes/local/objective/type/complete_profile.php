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
 * Complete profile.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\action\profile_updated;
use block_gearup\local\form\extender;
use block_gearup\local\form\extender_with_supporting_url_modes;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\utils\user_utils;
use context_system;
use core_user\fields;
use lang_string;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Complete profile.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class complete_profile implements type, type_with_state_initialisation, type_with_supporting_url {

    /** URL mode edit profile. */
    const URLMODE_EDIT_PROFILE = 1;

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        $userid = $missioninst->get_subject_id();
        if (!$this->has_completed_profile($instance, $userid)) {
            return;
        }
        $instance->increment_counter(1);
    }

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$this->has_completed_profile($instance, $action->get_user_id())) {
            return;
        }
        $instance->increment_counter(1);
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        $currentfields = $objective ? $objective->get_type_config()->f ?? [] : [];
        return new complete_profile_config_form_extender($currentfields);
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typecompleteprofile', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new \lang_string('typecompleteprofiledesc', 'block_gearup');
    }

    public function get_supporting_url(objective $objective, $urltype): ?moodle_url {
        if ($urltype === static::URLMODE_EDIT_PROFILE) {
            return new moodle_url('/user/edit.php');
        }
        return null;
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof profile_updated;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {
        return true;
    }

    /**
     * Get the user fields.
     *
     * @param int $userid The user ID.
     * @param array $fields The list of fields.
     * @return false|object False means that the query did not return anything.
     */
    protected function get_user_with_fields($userid, array $fields) {
        global $DB;

        if (empty($fields)) {
            return (object) [];
        }

        // Load the data from the database, we cannot use the $USER object as it's not up to date.
        $userfields = fields::empty();
        $userfields->including(...$fields);
        $sqlparts = $userfields->get_sql('u', true, '', '', false);

        // Note that when a no longer existing custom profile field is included, this will return false.
        $sql = "SELECT u.id, $sqlparts->selects FROM {user} u $sqlparts->joins WHERE u.id = :userid";

        try {
            return $DB->get_record_sql($sql, array_merge(['userid' => $userid], $sqlparts->params));
        } catch (\moodle_exception $e) {
            // This can happen when a database field no longer exists.
            return false;
        }
    }

    /**
     * Whether the user has completed their profile.
     *
     * @param int $userid The user ID.
     * @return bool
     */
    protected function has_completed_profile(objective_instance $instance, int $userid): bool {
        global $DB;

        $config = $instance->get_objective()->get_type_config();
        $fields = $config->f ?? [];
        $user = user_utils::get_user_with_fields($userid, $fields, false);

        // After the second attempt, if still false, we bail.
        if (empty($user)) {
            return false;
        }

        // Check each of the fields we require.
        $completed = 0;
        $nfields = 0;
        foreach ($fields as $field) {
            $nfields++;
            if (!property_exists($user, $field)) {
                continue;
            }

            if ($field === 'description') {
                if (empty(strip_tags($user->description ?? ''))) {
                    continue;
                }
                $completed++;
                continue;
            }

            $val = $user->$field;
            if ($val != '0' && empty($val)) {
                continue;
            }
            $completed++;
        }

        return $completed >= $nfields;
    }

    /**
     * Construct the field.
     *
     * @param object $fieldrecord The field record from the database.
     * @return null|\profile_field_base
     */
    public static function construct_field($fieldrecord) {
        global $CFG;
        if (!\core_component::get_component_directory('profilefield_' . $fieldrecord->datatype)) {
            return null;
        }
        require_once($CFG->dirroot . '/user/profile/field/' . $fieldrecord->datatype . '/field.class.php');
        $classname = 'profile_field_' . $fieldrecord->datatype;
        return new $classname($fieldrecord->id, 0, $fieldrecord);
    }
}

/**
 * Config form extender.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class complete_profile_config_form_extender implements extender, extender_with_supporting_url_modes {

    /** @var array */
    protected $currentfields;

    public function __construct(array $currentfields = []) {
        $this->currentfields = $currentfields;
    }

    public function definition($mform): array {
        global $DB;

        $els = [];

        $mform->removeElement('countneeded', true);

        $els[] = $mform->addElement('hidden', 'countneeded');
        $mform->setType('countneeded', PARAM_INT);
        $mform->setConstant('countneeded', 1);

        $options = [
            'description' => get_string('description', 'core'),
            'city' => get_string('city', 'core'),
            'country' => get_string('country', 'core'),
            'institution' => get_string('institution', 'core'),
            'department' => get_string('department', 'core'),
            'phone1' => get_string('phone1', 'core'),
            'phone2' => get_string('phone2', 'core'),
            'address' => get_string('address', 'core'),
        ];

        $namefields = array_merge(useredit_get_enabled_name_fields(), array_values(useredit_get_disabled_name_fields()));
        foreach ($namefields as $addname) {
            $options[$addname] = get_string($addname, 'core');
        }

        $fieldcategories = $DB->get_records('user_info_category', null, 'sortorder ASC');
        foreach ($fieldcategories as $category) {
            $fields = $DB->get_records('user_info_field', ['categoryid' => $category->id], 'sortorder ASC');
            foreach ($fields as $fieldrecord) {

                // We cannot check visibility using the field class itself as its logic is too vague.
                if ($fieldrecord->visible != PROFILE_VISIBLE_ALL) {
                    continue;
                }

                // Skip required and locked fields. We make a clone because the object changes the data.
                $field = complete_profile::construct_field((object) (array) $fieldrecord);
                if (!$field) {
                    continue;
                } else if ($field->is_required() || $field->is_locked()) {
                    continue;
                }

                // Construct name of field.
                $component = 'profilefield_' . $fieldrecord->datatype;
                $classname = "\\$component\\helper";
                $fieldname = format_string($fieldrecord->name, true, ['context' => context_system::instance()]);
                if (class_exists($classname) && method_exists($classname, 'get_fieldname')) {
                    $fieldname = $classname::get_fieldname($fieldrecord->name);
                }

                $options["profile_field_{$field->get_shortname()}"] = $fieldname;
            }
        }

        // Add the fields that no longer exist or something.
        foreach ($this->currentfields as $field) {
            if (!isset($options[$field])) {
                $options[$field] = get_string('unknownvalue', 'block_gearup', $field);
            }
        }

        $els[] = $mform->addElement('autocomplete', 'cd_f', get_string('requiredfields', 'block_gearup'), $options, [
            'casesensitive' => false,
            'multiple' => true,
        ]);

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function get_supporting_url_modes(): array {
        return [
            complete_profile::URLMODE_EDIT_PROFILE => get_string('editprofilepage', 'block_gearup'),
        ];
    }

    public function validation($data, $files) {
        $errors = [];
        if (empty($data->cd_f) || count($data->cd_f) <= 0) {
            $errors['cd_f'] = get_string('atleastonemustbeselected', 'block_gearup');
        }
        return $errors;
    }

}
