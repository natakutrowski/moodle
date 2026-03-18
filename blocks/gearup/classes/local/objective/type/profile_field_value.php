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

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\action\profile_updated;
use block_gearup\local\action\user_modified;
use block_gearup\local\form\extender;
use block_gearup\local\form\extender_with_default_data;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\utils\user_utils;
use context_system;
use core_text;
use lang_string;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_value implements type, type_with_state_initialisation {

    /** Int. */
    const VALUE_TYPE_INT = 'int';
    /** Text. */
    const VALUE_TYPE_TEXT = 'text';
    /** Bool. */
    const VALUE_TYPE_BOOL = 'bool';

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        $userid = $missioninst->get_subject_id();
        $value = $this->get_tracked_counter_value($instance, $userid, true);
        if ($value > 0) {
            $instance->increment_counter($value);
        }
    }

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        $config = $this->get_normalised_config($instance->get_objective());
        $userid = $missioninst->get_subject_id();
        $keepbest = $config->keepbest ?? false;

        $value = $this->get_tracked_counter_value($instance, $userid);
        if ($value <= 0 && $instance->get_counter() > 0) {
            if (!$keepbest) {
                $instance->reset_counter();
            }

        } else if ($value >= 1) {
            if ($config->track) {
                if (!$keepbest || $instance->get_counter() < $value) {
                    $instance->reset_counter();
                    $instance->increment_counter($value);
                }
            } else {
                // If we do not track, it should just be 1, but in case of weird setup we use the objective count needed.
                $instance->increment_counter($instance->get_objective()->get_count_needed());
            }
        }
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        $config = $this->get_normalised_config($objective ? $objective : null);
        return new profile_field_value_config_form_extender($mission->get_context(), $config->f ?? null);
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typeprofilefieldvalue', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new \lang_string('typeprofilefieldvaluedesc', 'block_gearup');
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof user_modified || $action instanceof profile_updated;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {
        return true;
    }

    /**
     * Get the normalised conf.
     *
     * @param objective|null $objective
     * @return stdClass
     */
    protected function get_normalised_config(?objective $objective): stdClass {
        $config = $objective ? $objective->get_type_config() : null;

        $defaultvalues = ['f' => null, 't' => static::VALUE_TYPE_INT, 'c' => 'eq', 'v' => 1, 'track' => false, 'keepbest' => false];
        if (!$config) {
            return (object) $defaultvalues;
        }

        $config->t ??= static::VALUE_TYPE_INT;
        if (!in_array($config->t, [static::VALUE_TYPE_INT, static::VALUE_TYPE_TEXT, static::VALUE_TYPE_BOOL])) {
            $config->t = static::VALUE_TYPE_INT;
        }

        if ($config->t === static::VALUE_TYPE_INT) {
            $config->c ??= 'eq';
            $config->v = (int) ($config->v ?? 1);
            $config->track = ($objective && $objective->get_count_needed() > 1)
                && ($config->track ?? false)
                && in_array($config->c, ['eq', 'gte'])
                && $config->v > 1;
            $config->keepbest = $config->track && $config->keepbest ?? false;

        } else if ($config->t === static::VALUE_TYPE_TEXT) {
            $config->c ??= 'eq';
            $config->v = $config->v ?? '';

        } else if ($config->t === static::VALUE_TYPE_BOOL) {
            $config->c ??= 'true';
        }

        $config->track ??= false;
        $config->keepbest ??= false;

        return $config;
    }

    /**
     * Whether the user has completed their profile.
     *
     * @param objective_instance $instance The objective instance.
     * @param bool $loadedfaults Whether the user fields must loaded with defaults.
     * @return bool
     */
    protected function get_tracked_counter_value(objective_instance $instance, bool $loadedfaults = false): int {
        $userid = $instance->get_subject_id();
        $config = $this->get_normalised_config($instance->get_objective());
        $type = $config->t ?? static::VALUE_TYPE_INT;
        $condition = $config->c ?? 'eq';

        // We do not have a field, we should not be here.
        $field = $config->f ?? null;
        if (empty($field)) {
            return 0;
        }

        // We have no fields to select, or they're invalid fields.
        $user = user_utils::get_user_with_fields($userid, [$field], $loadedfaults);
        if (empty($user)) {
            return 0;
        }

        $fieldvalue = $user->{$field} ?? null;

        // We don't have a value at all, do nothing.
        if ($fieldvalue === null) {
            return 0;
        }

        switch ($type) {
            case static::VALUE_TYPE_INT:
                $value = (int) $fieldvalue;
                $testvalue = (int) ($config->v ?? 1);

                $passes = false;
                if ($condition === 'eq') {
                    $passes = $value === $testvalue ? true : false;
                    if ($config->track && $testvalue > 0 && $value <= $testvalue) {
                        return max(0, min($testvalue, $value));
                    }
                    return $passes ? 1 : 0;

                } else if ($condition === 'gte') {
                    $passes = $value >= $testvalue ? true : false;
                    if ($config->track && $testvalue > 0) {
                        return max(0, min($testvalue, $value));
                    }
                    return $passes ? 1 : 0;

                } else if ($condition === 'gt') {
                    return $value > $testvalue ? 1 : 0;
                } else if ($condition === 'lt') {
                    return $value < $testvalue ? 1 : 0;
                } else if ($condition === 'lte') {
                    return $value <= $testvalue ? 1 : 0;
                } else if ($condition === 'neq') {
                    return $value !== $testvalue ? 1 : 0;
                }

                return 0;

            case static::VALUE_TYPE_TEXT:
                $value = trim((string) $fieldvalue);
                $testvalue = trim((string) ($config->v ?? ''));

                if ($condition === 'has') {
                    return strpos(core_text::strtolower($value), core_text::strtolower($testvalue)) !== false ? 1 : 0;
                } else if ($condition === 'nhas') {
                    return strpos(core_text::strtolower($value), core_text::strtolower($testvalue)) !== false ? 0 : 1;
                } else if ($condition === 'neq') {
                    return $value !== $testvalue ? 1 : 0;
                }
                return $value === $testvalue ? 1 : 0;

            case static::VALUE_TYPE_BOOL:
                $truthy = ['yes', 'true', '1', 'on', 'enabled'];
                $istrue = in_array(core_text::strtolower($fieldvalue), $truthy);
                if ($condition === 'true') {
                    return $istrue ? 1 : 0;
                }
                return $istrue ? 0 : 1;
        }

        return 0;
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
class profile_field_value_config_form_extender implements extender, extender_with_default_data {

    /** @var \context */
    protected $context;
    /** @var ?string */
    protected $currentfield;

    /**
     * Constructor.
     *
     * @param \context $context
     */
    public function __construct(\context $context, ?string $currentfield = null) {
        $this->context = $context;
        $this->currentfield = $currentfield;
    }

    public function definition($mform): array {
        global $DB;

        $els = [];

        $optgroups = [];

        $fieldcategories = $DB->get_records('user_info_category', null, 'sortorder ASC');
        foreach ($fieldcategories as $category) {
            $options = [];

            $fields = $DB->get_records_select('user_info_field',
                'categoryid = ? AND datatype IN (?, ?)',
                [$category->id, 'text', 'checkbox'],
                'sortorder ASC'
            );
            foreach ($fields as $fieldrecord) {

                // Only allow tracking of the value when the user could see others value, else this could leak data.
                $anycapability = [];
                if ($fieldrecord->visible == PROFILE_VISIBLE_ALL) {
                    $anycapability = $anycapability;
                } else if ($fieldrecord->visible == PROFILE_VISIBLE_TEACHERS) {
                    $anycapability[] = 'moodle/site:viewuseridentity';
                    $anycapability[] = 'moodle/user:viewalldetails';
                } else if ($fieldrecord->visible == PROFILE_VISIBLE_PRIVATE) {
                    $anycapability[] = 'moodle/user:viewalldetails';
                } else {
                    $anycapability[] = 'moodle/user:viewalldetails';
                }
                if (!empty($anycapability) && !has_any_capability($anycapability, $this->context)) {
                    continue;
                }

                // Construct name of field.
                $component = 'profilefield_' . $fieldrecord->datatype;
                $classname = "\\$component\\helper";
                $fieldname = format_string($fieldrecord->name, true, ['context' => context_system::instance()]);
                if (class_exists($classname) && method_exists($classname, 'get_fieldname')) {
                    $fieldname = $classname::get_fieldname($fieldrecord->name);
                }

                $options["profile_field_{$fieldrecord->shortname}"] = $fieldname;
            }

            if (!empty($options)) {
                $optgroups[format_string($category->name)] = $options;
            }
        }

        // Append the current value if there is one and it's not found in the options.
        $hascurrentvalue = false;
        foreach ($optgroups as $options) {
            if (array_key_exists($this->currentfield, $options)) {
                $hascurrentvalue = true;
                break;
            }
        }
        if (!$hascurrentvalue && $this->currentfield) {
            $optgroups[get_string('error', 'core')] = [$this->currentfield =>
                get_string('unknownvalue', 'block_gearup', $this->currentfield), ];
        }

        // If empty, show no results.
        if (empty($optgroups)) {
            $optgroups = [get_string('error', 'core') => [get_string('noresults', 'core')]];
        }

        $els[] = $mform->addElement('selectgroups', 'cd_f', get_string('profilefield', 'core_admin'), $optgroups);

        $els[] = $mform->addElement('select', 'cd_t', get_string('treatvalueas', 'block_gearup'), [
            profile_field_value::VALUE_TYPE_INT => get_string('number', 'block_gearup'),
            profile_field_value::VALUE_TYPE_TEXT => get_string('text', 'block_gearup'),
            profile_field_value::VALUE_TYPE_BOOL => get_string('boolean', 'block_gearup'),
        ]);

        // Text.
        $groupels = [
            $mform->createElement('select', 'cd_cond_text', get_string('condition', 'block_gearup'), [
                'eq' => get_string('isexactly', 'block_gearup'),
                'neq' => get_string('isdifferentthan', 'block_gearup'),
                'has' => get_string('contains', 'block_gearup'),
                'nhas' => get_string('doesnotcontain', 'block_gearup'),
            ]),
            $mform->createElement('text', 'cd_value_text', get_string('value', 'block_gearup')),
        ];
        $mform->setType('cd_value_text', PARAM_RAW);
        $els[] = $mform->addElement('group', 'cd_group_text', get_string('value', 'block_gearup'), $groupels, ' ', false);
        $mform->hideIf('cd_group_text', 'cd_t', 'neq', profile_field_value::VALUE_TYPE_TEXT);

        // Number.
        $groupels = [
            $mform->createElement('select', 'cd_cond_number', get_string('condition', 'block_gearup'), [
                'eq' => get_string('equalsto', 'block_gearup'),
                'gte' => get_string('isgreaterthanorequalto', 'block_gearup'),
                'lte' => get_string('islessthanorequalto', 'block_gearup'),
                'neq' => get_string('isdifferentthan', 'block_gearup'),
            ]),
            $mform->createElement('text', 'cd_value_number', get_string('value', 'block_gearup'), ['size' => 10]),
        ];
        $mform->setType('cd_value_number', PARAM_INT);
        $els[] = $mform->addElement('group', 'cd_group_number', get_string('value', 'block_gearup'), $groupels, ' ', false);
        $els[] = $mform->addElement('checkbox', 'cd_track', get_string('trackprogressfromzero', 'block_gearup'));
        $els[] = $mform->addElement('checkbox', 'cd_keepbest', get_string('keepbestvaluerecorded', 'block_gearup'));
        $mform->hideIf('cd_group_number', 'cd_t', 'neq', profile_field_value::VALUE_TYPE_INT);
        $mform->hideIf('cd_track', 'cd_t', 'neq', profile_field_value::VALUE_TYPE_INT);
        $mform->hideIf('cd_track', 'cd_cond_number', 'in', ['neq', 'gt', 'lt', 'lte']);
        $mform->hideIf('cd_keepbest', 'cd_t', 'neq', profile_field_value::VALUE_TYPE_INT);
        $mform->hideIf('cd_keepbest', 'cd_cond_number', 'in', ['neq', 'lt', 'lte']);
        $mform->disabledIf('cd_keepbest', 'cd_track', 'notchecked');

        // Boolean.
        $els[] = $mform->addElement('select', 'cd_cond_bool', get_string('value', 'block_gearup'), [
            'true' => get_string('istrue', 'block_gearup'),
            'false' => get_string('isfalse', 'block_gearup'),
        ]);
        $mform->hideIf('cd_cond_bool', 'cd_t', 'neq', 'bool');

        $mform->removeElement('countneeded', true);

        return $els;
    }

    public function get_data($data) {
        $data->countneeded = 1;
        $data->cd_c = 'eq';
        $data->cd_v = 1;

        if ($data->cd_t === profile_field_value::VALUE_TYPE_INT) {
            $data->cd_c = $data->cd_cond_number;
            $data->cd_v = (int) ($data->cd_value_number ?? 1);
            $data->cd_track = !empty($data->cd_track);
            $data->cd_keepbest = $data->cd_track && !empty($data->cd_keepbest);

            // If we're tracking, we set the count needed to the value, if positive.
            if ($data->cd_track && in_array($data->cd_cond_number, ['eq', 'gte'])) {
                $data->countneeded = max(1, $data->cd_v);
            }

        } else if ($data->cd_t === profile_field_value::VALUE_TYPE_TEXT) {
            $data->cd_c = $data->cd_cond_text;
            $data->cd_v = $data->cd_value_text ?? '';

        } else if ($data->cd_t === profile_field_value::VALUE_TYPE_BOOL) {
            $data->cd_c = $data->cd_cond_bool;
        }

        unset($data->cd_value_number);
        unset($data->cd_value_text);
        unset($data->cd_cond_text);
        unset($data->cd_cond_number);
        unset($data->cd_cond_bool);

        return $data;
    }

    public function get_default_data($data) {
        $data->cd_t = $data->cd_t ?? null;

        if ($data->cd_t === profile_field_value::VALUE_TYPE_INT) {
            $data->cd_cond_number = $data->cd_c ?? null;
            $data->cd_value_number = $data->cd_v ?? null;

        } else if ($data->cd_t === profile_field_value::VALUE_TYPE_TEXT) {
            $data->cd_cond_text = $data->cd_c ?? null;
            $data->cd_value_text = $data->cd_v ?? null;

        } else if ($data->cd_t === profile_field_value::VALUE_TYPE_BOOL) {
            $data->cd_cond_bool = $data->cd_c;
        }

        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty($data->cd_f)) {
            $errors['cd_f'] = get_string('required');
        }
        if ($data->cd_t === 'text' && empty($data->cd_value_text)) {
            $errors['cd_value_text'] = get_string('invaliddata', 'core_error');
        }
        if ($data->cd_t === profile_field_value::VALUE_TYPE_INT && !empty($data->cd_track) && $data->cd_value_number <= 0) {
            $errors['cd_track'] = get_string('invaliddata', 'core_error');
        }

        return $errors;
    }

}
