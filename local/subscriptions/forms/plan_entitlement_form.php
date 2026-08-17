<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');

class plan_entitlement_form extends moodleform {

    public function definition() {
        global $DB;

        $mform = $this->_form;

        $planid = (int)($this->_customdata['planid'] ?? 0);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'planid');
        $mform->setType('planid', PARAM_INT);
        $mform->setDefault('planid', $planid);

        $courses = [];
        $records = $DB->get_records_sql("
            SELECT id, fullname
              FROM {course}
             WHERE id <> :siteid
          ORDER BY fullname ASC
        ", ['siteid' => SITEID]);

        foreach ($records as $course) {
            $courses[$course->id] = format_string($course->fullname);
        }

        $mform->addElement('autocomplete', 'courseid', get_string('entitlement_course', 'local_subscriptions'), $courses, [
            'multiple' => false,
            'noselectionstring' => get_string('none'),
        ]);
        $mform->setType('courseid', PARAM_INT);
        $mform->addRule('courseid', null, 'required');

        $accesslevels = [
            'trial' => get_string('accesslevel_trial', 'local_subscriptions'),
            'grammar' => get_string('accesslevel_grammar', 'local_subscriptions'),
            'full' => get_string('accesslevel_full', 'local_subscriptions'),
        ];

        $mform->addElement('select', 'accesslevel', get_string('entitlement_accesslevel', 'local_subscriptions'), $accesslevels);
        $mform->setType('accesslevel', PARAM_ALPHANUMEXT);
        $mform->addRule('accesslevel', null, 'required');

        $roles = [];
        $rolerecords = $DB->get_records('role', null, 'shortname ASC', 'id, shortname, name');

        foreach ($rolerecords as $role) {
            $label = $role->name ? $role->name . ' (' . $role->shortname . ')' : $role->shortname;
            $roles[$role->shortname] = $label;
        }

        $mform->addElement('autocomplete', 'roleshortname', get_string('entitlement_role', 'local_subscriptions'), $roles, [
            'multiple' => false,
            'noselectionstring' => get_string('none'),
        ]);
        $mform->setType('roleshortname', PARAM_ALPHANUMEXT);
        $mform->setDefault('roleshortname', 'student');
        $mform->addRule('roleshortname', null, 'required');

        $mform->addElement('text', 'groupname', get_string('entitlement_groupname', 'local_subscriptions'));
        $mform->setType('groupname', PARAM_TEXT);
        $mform->addHelpButton('groupname', 'entitlement_groupname', 'local_subscriptions');

        $mform->addElement('text', 'priority', get_string('entitlement_priority', 'local_subscriptions'));
        $mform->setType('priority', PARAM_INT);
        $mform->setDefault('priority', 100);
        $mform->addHelpButton('priority', 'entitlement_priority', 'local_subscriptions');

        $this->add_action_buttons(true, get_string('saveentitlement', 'local_subscriptions'));
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        $id = (int)($data['id'] ?? 0);

        $params = [
            'planid' => (int)$data['planid'],
            'courseid' => (int)$data['courseid'],
            'accesslevel' => $data['accesslevel'],
        ];

        $existing = $DB->get_record('subscription_plan_entitlement', $params);

        if ($existing && (int)$existing->id !== $id) {
            $errors['accesslevel'] = get_string('entitlement_already_exists', 'local_subscriptions');
        }

        return $errors;
    }
}