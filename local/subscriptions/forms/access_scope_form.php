<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');
class access_scope_form extends moodleform {
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $default_courses = explode(',', $this->_customdata['course_ids'] ?? '');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('scopename', 'local_subscriptions'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $courses = $DB->get_records_menu('course', null, 'fullname ASC', 'id, fullname');
        $mform->addElement('select', 'course_ids', get_string('includedcourses', 'local_subscriptions'), $courses, [
            'multiple' => 'multiple',
            'size' => 10,
            'class' => 'select2'
        ]);

        $mform->setDefault('course_ids', $default_courses);
        $mform->addRule('course_ids', null, 'required');

        $this->add_action_buttons(true, get_string('save', 'local_subscriptions'));
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $name = trim($data['name']);
        $id = (int)($data['id'] ?? 0);

        // Vérifie si un autre scope avec ce nom existe déjà (autre que celui en cours d'édition).
        $params = ['name' => $name];
        $existing = $DB->get_record_sql(
            "SELECT * FROM {subscription_access_scope} WHERE LOWER(name) = LOWER(:name)",
            $params
        );

        if ($existing && $existing->id != $id) {
            $errors['name'] = get_string('error_scope_name_exists', 'local_subscriptions');
        }

        return $errors;
    }
}