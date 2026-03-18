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
use coding_exception;
use context;
use core_form\dynamic_form;
use stdClass;

// phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found

/**
 * Form.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class improved_dynamic_form extends dynamic_form {

    /** @var array Equivalent to customdata but for dynamic forms (made by us). */
    protected $_dynamicdata = [];

    /**
     * After definition hook.
     *
     * @return void
     */
    protected function after_definition() {
        $mform = $this->_form;

        // Add the page context ID as a hidden field.
        $mform->addElement('hidden', 'gupagectxid');
        $mform->setType('gupagectxid', PARAM_INT);
        $mform->setConstant('gupagectxid', $this->_dynamicdata['pagecontext']->id);

        // When we support deletion, and the current form is loaded with an item
        // that can be deleted, then we notify the parent by setting a special
        // data attribute on the form.
        if ($this->is_deletion_supported() && $this->can_delete()) {
            $mform->updateAttributes(['data-gu-supports-delete' => 1]);
        }
    }

    /**
     * Whether we can delete the current item.
     *
     * Use this space to validate whether we should be able to activate
     * a deletion. For instance, while a form could supports deletion
     * if it's being used to create an object it cannot delete anything.
     *
     * Also return false if the current user cannot delete the object,
     * or if the object is in a state where it cannot be deleted, etc.
     *
     * @return bool
     */
    protected function can_delete() {
        return false;
    }

    /**
     * Internal definition of external validation.
     *
     * This should only be used by extending code. If you're implementing a form
     * that needs extra validation, use {@link self::extra_validation()}.
     *
     * If you implement a custom validation, please be sure to call
     * {@link self::extra_validation()} or the full validation stack won't occur.
     *
     * @param stdClass $data Data to validate.
     * @param array $files Array of files.
     * @param array $errors Currently reported errors.
     * @return array of additional errors, or overridden errors.
     */
    protected function custom_validation($data, $files, array &$errors) {
        return $this->extra_validation($data, $files, $errors);
    }

    /**
     * Define extra validation mechanims.
     *
     * @param stdClass $data Data to validate.
     * @param array $files Array of files.
     * @param array $errors Currently reported errors.
     * @return array of additional errors, or overridden errors.
     */
    protected function extra_validation($data, $files, array &$errors) {
        return [];
    }

    /**
     * Get form data.
     *
     * @return object|null
     */
    public function get_data() {
        return parent::get_data();
    }

    /**
     * Get the default data.
     *
     * @return stdClass
     */
    protected function get_default_data() {
        return (object) [];
    }

    /**
     * Get the URL resolver.
     *
     * @return \block_gearup\local\routing\url_resolver
     */
    protected function get_url_resolver() {
        if (!isset($this->_dynamicdata['urlresolver'])) {
            throw new \coding_exception('To early call to URL resolver.');
        }
        return $this->_dynamicdata['urlresolver'];
    }

    /**
     * Whether deletion is requested.
     *
     * @return bool
     */
    final protected function is_deletion_requested() {
        return $this->is_deletion_supported()
            && $this->can_delete()
            && $this->optional_param('__gu_do_delete', false, PARAM_BOOL)
            && confirm_sesskey();
    }

    /**
     * Whether this dynamic form supports deletion.
     *
     * Override to specify that this is true.
     *
     * @return bool
     */
    protected function is_deletion_supported(): bool {
        return false;
    }

    /**
     * Form validation.
     *
     * If you need extra validation, use {@link self::extra_validation()}. The
     * {@link self::custom_validation()} is reserved to internal implementations.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    final public function validation($data, $files) {
        // Bypass the validation when deletion is requested. We validate this in self::can_delete().
        if ($this->is_deletion_requested()) {
            return [];
        }

        // Apply the default validation.
        $errors = parent::validation($data, $files);
        $data = (object) $data;

        // Apply the custom validation, which is essentially self::extra_validation().
        $extraerrors = $this->custom_validation($data, $files, $errors);
        $errors = array_merge($errors, (array) $extraerrors);

        return $errors;
    }

    /**
     * Initialise for dynamic submission.
     *
     * Do all the things that need to be done to initialise what needs to
     * be done for dynamic submission. This typically replaces the logic
     * that is typically performed in the constructor, to fill customdata.
     *
     * This method is expected to define $this->_dynamicdata['context].
     *
     * @return void
     */
    abstract protected function initialise_for_dynamic_submission(): void;

    /**
     * Returns context where this form is used.
     *
     * @return context
     */
    final protected function get_context_for_dynamic_submission(): \context {
        $this->initialise_for_dynamic_submission();
        if (!isset($this->_dynamicdata['context'])) {
            throw new coding_exception('You must set the context in $this->_dynamicdata[\'context\'].');
        }

        // Re-implementation of the page context handling in our base route.
        $contextid = $this->optional_param('gupagectxid', 0, PARAM_INT);
        $pagecontext = $contextid ? context::instance_by_id($contextid) : $this->_dynamicdata['context'];
        if ($pagecontext instanceof \context_user) {
            $pagecontext = $pagecontext->get_parent_context();
        }
        $this->_dynamicdata['pagecontext'] = $pagecontext;

        $this->_dynamicdata['urlresolver'] = di::get('url_resolver_factory')->get_resolver_for_context(
            $this->_dynamicdata['context'],
            $this->_dynamicdata['pagecontext']
        );

        return $pagecontext;
    }

    /**
     * Process a dynamic deletion.
     *
     * This method needs to validate that the deletion is allowed. Note
     * that a deletion should only be triggered when the hidden field
     * __gu_supports_delete is defined. You may share the same logic
     * between to validate the deletion here and the display of the field.
     *
     * @return void
     */
    protected function process_dynamic_deletion() {
        throw new \coding_exception('Deletion not implemented');
    }

    /**
     * Process a dynamic submission save.
     *
     * @return void
     */
    abstract protected function process_dynamic_save();

    /**
     * Process the form submission.
     *
     * @return mixed
     */
    final public function process_dynamic_submission() {
        if ($this->is_deletion_requested()) {
            return $this->process_dynamic_deletion();
        }
        return $this->process_dynamic_save();
    }

    /**
     * Load in existing data as form defaults.
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data($this->get_default_data());
    }

}
