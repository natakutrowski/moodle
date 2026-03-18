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
 * Form.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\form;

use block_gearup\local\model\objective;
use coding_exception;
use stdClass;

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class persistent_dynamic_form extends improved_dynamic_form {

    /** @var string Peristent class. */
    protected static $persistentclass = objective::class;

    /** @var array Foreign fields. */
    private static $foreignfields = [];
    /** @var array Fields to remove. */
    private static $fieldstoremove = ['submitbutton'];

    /** @var \core\persistent The persistent. */
    private $persistent;

    /**
     * After definition hook.
     *
     * @see \core\form\persistent::after_definition
     * @return void
     */
    protected function after_definition() {
        parent::after_definition();

        $mform = $this->_form;

        $class = static::$persistentclass;
        $properties = $class::properties_definition();
        $fieldstoremove = $this->get_fields_to_remove();
        $foreignfields = $this->get_foreign_fields();

        foreach ($mform->_elements as $element) {
            $name = $element->getName();

            if (isset($mform->_types[$name])) {
                // We already have a PARAM_* type for this field.
                continue;

            } else if (!isset($properties[$name]) || in_array($name, $fieldstoremove)
                    || in_array($name, $foreignfields)
            ) {
                // Ignoring foreign and unknown fields.
                continue;
            }

            // Set the type on the element.
            switch ($element->getType()) {
                case 'hidden':
                case 'text':
                case 'url':
                    $mform->setType($name, $properties[$name]['type']);
                    break;
            }
        }
    }


    /**
     * Convert some fields.
     *
     * @param  stdClass $data The whole data set.
     * @return stdClass The amended data set.
     */
    protected function convert_fields(stdClass $data) {
        $class = static::$persistentclass;
        $properties = $class::get_formatted_properties();

        foreach ($data as $field => $value) {
            // Replace formatted properties.
            if (isset($properties[$field])) {
                $formatfield = $properties[$field];
                $data->$formatfield = $data->{$field}['format'];
                $data->$field = $data->{$field}['text'];
            }
        }

        return $data;
    }

    /**
     * Define custom validation mechanims.
     *
     * @param  stdClass $data Data to validate.
     * @param  array $files Array of files.
     * @param  array $errors Currently reported errors.
     * @return array of additional errors, or overridden errors.
     */
    final protected function custom_validation($data, $files, array &$errors) {
        $data = $this->get_submitted_data();

        // Only validate compatible fields.
        $persistentdata = $this->filter_data_for_persistent($data);
        $persistent = $this->get_persistent();
        $recordbeforevalidation  = $persistent->to_record();
        $persistent->from_record((object) $persistentdata);
        $errors = array_merge($errors, $persistent->get_errors());

        // Restore the record as it was.
        $persistent->from_record($recordbeforevalidation);

        return parent::custom_validation($data, $files, $errors);
    }

    /**
     * Filter the data to what the persistent understands.
     *
     * @param stdClass $data The data to filter the fields out of.
     * @return stdClass.
     */
    protected function filter_data_for_persistent($data) {
        return (object) array_diff_key((array) $data, array_flip((array) $this->get_foreign_fields()));
    }

    /**
     * Get form data.
     *
     * Conveniently removes non-desired properties and add the ID property.
     *
     * @return object|null
     */
    public function get_data() {
        $data = parent::get_data();
        if (is_object($data)) {
            foreach ($this->get_fields_to_remove() as $field) {
                unset($data->{$field});
            }
            $data = $this->convert_fields($data);

            // Ensure that the ID is set.
            $data->id = $this->persistent->get('id');
        }
        return $data;
    }

    /**
     * Get the default data.
     *
     * This is the data that is prepopulated in the form at it loads, we automatically
     * fetch all the properties of the persistent however some needs to be converted
     * to map the form structure.
     *
     * Extend this class if you need to add more conversion.
     *
     * @return stdClass
     */
    protected function get_default_data() {
        $data = $this->get_persistent()->to_record();
        $class = static::$persistentclass;
        $properties = $class::get_formatted_properties();
        $allproperties = $class::properties_definition();

        foreach ($data as $field => $value) {
            // Clean data if it is to be displayed in a form.
            if (isset($allproperties[$field]['type'])) {
                $data->$field = clean_param($data->$field, $allproperties[$field]['type']);
            }

            if (isset($properties[$field])) {
                $data->$field = [
                    'text' => $data->$field,
                    'format' => $data->{$properties[$field]},
                ];
                unset($data->{$properties[$field]});
            }
        }

        return $data;
    }

    /**
     * Get the foreign fields.
     *
     * Those are the fields that do not belong to the persistent.
     *
     * @return array
     */
    protected function get_foreign_fields() {
        return static::$foreignfields;
    }

    /**
     * Get the fields to remove.
     *
     * Those are not returned by the form, but they are included for
     * in the extra validation.
     *
     * @return array
     */
    protected function get_fields_to_remove() {
        return static::$fieldstoremove;
    }

    /**
     * Return the persistent object associated with this form instance.
     *
     * @return \core\persistent
     */
    final protected function get_persistent() {
        if (!$this->persistent) {
            $this->initialise_persistent();
        }
        return $this->persistent;
    }

    /**
     * Get the submitted form data.
     *
     * Conveniently removes non-desired properties.
     *
     * @return object|null
     */
    public function get_submitted_data() {
        $data = parent::get_submitted_data();
        if (is_object($data)) {
            foreach ($this->get_fields_to_remove() as $field) {
                unset($data->{$field});
            }
            $data = $this->convert_fields($data);
        }
        return $data;
    }

    /**
     * Initialise the persistent.
     *
     * @return void
     */
    final protected function initialise_persistent() {
        if (!array_key_exists('persistent', $this->_dynamicdata)) {
            throw new coding_exception('The dynamic data \'persistent\' key must be set, even if it is null.');
        }

        // Make a copy of the persistent passed, this ensures validation and object reference issues.
        $persistendata = new stdClass();
        $persistent = isset($this->_dynamicdata['persistent']) ? $this->_dynamicdata['persistent'] : null;
        if ($persistent) {
            if (!($persistent instanceof static::$persistentclass)) {
                throw new coding_exception('Invalid persistent');
            }
            $persistendata = $persistent->to_record();
            unset($persistent);
        }

        $this->persistent = new static::$persistentclass();
        $this->persistent->from_record($persistendata);

        unset($this->_dynamicdata['persistent']);
    }

}
